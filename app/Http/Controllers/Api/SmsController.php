<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\SmsNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SmsController extends Controller
{
    private const INBOX_LIMIT = 50;

    public function index(Request $request, SmsNotifier $notifier): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $sent = SmsMessage::query()
            ->where('user_id', $user->id)
            ->whereIn('type', ['approval', 'decline'])
            ->latest()
            ->limit(self::INBOX_LIMIT)
            ->get();

        $messages = $sent
            ->map(fn (SmsMessage $message) => $message->toApiArray())
            ->concat($this->bookingNotices($user, $sent, $notifier))
            ->sortByDesc('date')
            ->take(self::INBOX_LIMIT)
            ->values();

        return response()->json([
            'data' => $messages,
        ]);
    }

    public function read(Request $request, SmsMessage $sms): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($sms->user_id === $user->id, 403);

        $sms->update(['read_at' => now()]);

        return response()->json([
            'data' => $sms->fresh()?->toApiArray(),
        ]);
    }

    /**
     * Approval and decline notices for bookings the nurse has decided on but has not texted yet,
     * so the seeker sees the decision in the app without waiting for the manual SMS.
     *
     * @param  Collection<int, SmsMessage>  $sent
     * @return Collection<int, array<string, mixed>>
     */
    private function bookingNotices(User $user, Collection $sent, SmsNotifier $notifier): Collection
    {
        $alreadySent = $sent
            ->filter(fn (SmsMessage $message) => $message->booking_id !== null)
            ->map(fn (SmsMessage $message) => $message->booking_id.':'.$message->type)
            ->flip();

        return Booking::query()
            ->where('user_id', $user->id)
            ->where(fn ($query) => $query
                ->whereIn('status', [Booking::STATUS_APPROVED, Booking::STATUS_DECLINED])
                ->orWhere(fn ($approved) => $approved
                    ->whereIn('status', [Booking::STATUS_CLAIMED, Booking::STATUS_UNCLAIMED])
                    ->whereNotNull('claim_by')))
            ->latest('updated_at')
            ->limit(self::INBOX_LIMIT)
            ->get()
            ->map(function (Booking $booking) use ($alreadySent, $notifier): ?array {
                $declined = $booking->status === Booking::STATUS_DECLINED;
                $type = $declined ? 'decline' : 'approval';

                if ($alreadySent->has($booking->id.':'.$type)) {
                    return null;
                }

                $decision = $booking->replicate();
                $decision->status = $declined ? Booking::STATUS_DECLINED : Booking::STATUS_APPROVED;

                return [
                    'id' => "booking-{$booking->id}-{$type}",
                    'title' => $declined ? 'Decline' : 'Approval',
                    'body' => (string) $notifier->messageFor($decision),
                    'date' => $booking->updated_at?->toIso8601String(),
                    'read' => false,
                    'related_booking_id' => (string) $booking->id,
                    'mode' => 'app',
                    'type' => $type,
                ];
            })
            ->filter()
            ->values();
    }
}
