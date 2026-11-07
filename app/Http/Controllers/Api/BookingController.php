<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Medicine;
use App\Models\User;
use App\Services\MonthlyReportRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $healthCenter = $user->healthCenter();

        Booking::expireOverdue(
            $user->isAdmin() ? $healthCenter : null,
            $user->isAdmin() ? null : $user->id,
        );

        $bookings = Booking::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->where('user_id', $user->id))
            ->when($user->isAdmin() && filled($healthCenter), fn ($query) => $query->where('health_center', $healthCenter))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->get()
            ->map(fn (Booking $booking) => $booking->toApiArray())
            ->values();

        return response()->json([
            'data' => $bookings,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'medicine_id' => ['required', 'integer', 'exists:medicines,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.Booking::MAX_QUANTITY],
            'notes' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:30'],
        ], [
            'quantity.max' => 'You can request at most '.Booking::MAX_QUANTITY.' pieces per request.',
        ]);

        $phone = $validated['phone'] ?? $user->phone;

        if (! $phone) {
            throw ValidationException::withMessages([
                'phone' => 'A contact number is required to request medicine.',
            ]);
        }

        $booking = DB::transaction(function () use ($user, $validated, $phone) {
            $medicine = Medicine::query()->lockForUpdate()->findOrFail($validated['medicine_id']);

            if ($user->isSeeker() && filled($user->healthCenter()) && $medicine->health_center !== $user->healthCenter()) {
                throw ValidationException::withMessages([
                    'medicine_id' => 'This medicine is not available at your barangay health center.',
                ]);
            }

            $requestedToday = Booking::query()
                ->where('user_id', $user->id)
                ->where('medicine_id', $medicine->id)
                ->where('created_at', '>=', now('Asia/Manila')->startOfDay()->utc())
                ->exists();

            if ($requestedToday) {
                throw ValidationException::withMessages([
                    'medicine_id' => 'You already requested this medicine today. You can request it again tomorrow.',
                ]);
            }

            if ($medicine->status === 'out_of_stock' || $medicine->quantity <= 0) {
                throw ValidationException::withMessages([
                    'medicine_id' => 'This medicine is out of stock and cannot be requested.',
                ]);
            }

            if ($medicine->expiration?->isPast() || $medicine->quantity < $validated['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => 'This medicine is not available in the requested quantity.',
                ]);
            }

            $booking = Booking::query()->create([
                'user_id' => $user->id,
                'medicine_id' => $medicine->id,
                'seeker_name' => $user->name,
                'medicine_name' => $medicine->name,
                'quantity' => $validated['quantity'],
                'health_center' => $medicine->health_center,
                'phone' => $phone,
                'notes' => $validated['notes'] ?? null,
                'status' => Booking::STATUS_PENDING,
                'stock_reserved' => false,
            ]);

            return $booking->fresh();
        });

        app(MonthlyReportRecorder::class)->syncCurrent($booking->health_center);

        return response()->json([
            'message' => 'Medicine request submitted.',
            'data' => $booking->toApiArray(),
        ], 201);
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeBooking($request, $booking);

        if ($booking->status === Booking::STATUS_APPROVED && $booking->claim_by?->isPast()) {
            $booking->markUnclaimed();
            $booking->refresh();
            app(MonthlyReportRecorder::class)->syncCurrent($booking->health_center);
        }

        return response()->json([
            'data' => $booking->toApiArray(),
        ]);
    }

    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($booking->user_id === $user->id, 403);

        if ($booking->status !== Booking::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => 'Only a pending request can be marked unclaimed from the mobile app.',
            ]);
        }

        $booking->markUnclaimed();
        $booking->refresh();
        app(MonthlyReportRecorder::class)->syncCurrent($booking->health_center);

        return response()->json([
            'message' => 'Request marked unclaimed.',
            'data' => $booking->fresh()?->toApiArray(),
        ]);
    }

    private function authorizeBooking(Request $request, Booking $booking): void
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->isAdmin()) {
            abort_unless(filled($user->healthCenter()) && $booking->health_center === $user->healthCenter(), 403);

            return;
        }

        abort_unless($booking->user_id === $user->id, 403);
    }
}
