<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\SmsMessage;

class SmsNotifier
{
    /**
     * SMS type to pre-select on the manual SMS form for a booking's current status.
     */
    public function typeFor(Booking $booking): string
    {
        return match ($booking->status) {
            Booking::STATUS_APPROVED => 'approval',
            Booking::STATUS_DECLINED => 'decline',
            Booking::STATUS_CLAIMED => 'claimed',
            Booking::STATUS_UNCLAIMED => 'unclaimed',
            default => 'approval',
        };
    }

    public function messageFor(Booking $booking): ?string
    {
        $claimDays = $booking->claim_days;
        $claimBy = $booking->claim_by?->timezone('Asia/Manila')->format('M j, Y');
        $claimNotice = $claimDays
            ? ' Claim it within '.$claimDays.' '.($claimDays === 1 ? 'day' : 'days').($claimBy ? " (until {$claimBy})" : '').' at '.$booking->health_center.'.'
            : " Claim it at {$booking->health_center}.";

        return match ($booking->status) {
            Booking::STATUS_APPROVED => "MediCheck: Your request for {$booking->medicine_name} was approved.{$claimNotice}",
            Booking::STATUS_DECLINED => "MediCheck: Your request for {$booking->medicine_name} was declined by {$booking->health_center}.",
            Booking::STATUS_CLAIMED => "MediCheck: Your request for {$booking->medicine_name} was claimed at {$booking->health_center}.",
            Booking::STATUS_UNCLAIMED => "MediCheck: Your request for {$booking->medicine_name} was unclaimed at {$booking->health_center}.",
            Booking::STATUS_PENDING => "MediCheck: Your request for {$booking->medicine_name} was received and is waiting for approval.",
            default => null,
        };
    }

    public function manual(string $healthCenter, string $recipient, string $phone, string $type, string $message, ?int $userId = null, ?int $bookingId = null, string $status = 'sent'): SmsMessage
    {
        return SmsMessage::query()->create([
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'health_center' => $healthCenter,
            'recipient' => $recipient,
            'phone' => $phone,
            'mode' => SmsMessage::MODE_MANUAL,
            'type' => $type,
            'message' => $message,
            'status' => $status,
        ]);
    }
}
