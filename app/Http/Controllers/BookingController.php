<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\MonthlyReportRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $healthCenter = $request->user()?->healthCenter();

        abort_unless(filled($healthCenter) && $booking->health_center === $healthCenter, 403);

        $validated = $request->validate([
            'status' => ['nullable', 'in:approved,declined,claimed,unclaimed'],
            'claim_days' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        if (! isset($validated['status'])) {
            if (isset($validated['claim_days'])) {
                $booking->setClaimPeriod((int) $validated['claim_days']);
            }

            return back();
        }

        match ($validated['status']) {
            Booking::STATUS_APPROVED => $booking->approve($validated['claim_days'] ?? null),
            Booking::STATUS_DECLINED => $booking->decline(),
            Booking::STATUS_CLAIMED => $booking->claim(),
            Booking::STATUS_UNCLAIMED => $booking->markUnclaimed(),
            default => throw ValidationException::withMessages([
                'status' => 'That booking status is not supported.',
            ]),
        };

        $booking->refresh();
        app(MonthlyReportRecorder::class)->syncCurrent($healthCenter);

        return back();
    }
}
