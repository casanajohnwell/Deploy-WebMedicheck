<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $booking_id
 * @property string $health_center
 * @property string $recipient
 * @property string $phone
 * @property string $mode
 * @property string $type
 * @property string $message
 * @property string $status
 * @property Carbon|null $read_at
 * @property Carbon|null $hidden_from_log_at
 */
class SmsMessage extends Model
{
    public const MODE_MANUAL = 'manual';

    public const MODE_AUTOMATIC = 'automatic';

    protected $fillable = [
        'user_id',
        'booking_id',
        'health_center',
        'recipient',
        'phone',
        'mode',
        'type',
        'message',
        'status',
        'read_at',
        'hidden_from_log_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'hidden_from_log_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontend(): array
    {
        return [
            'id' => $this->id,
            'bookingId' => $this->booking_id,
            'recipient' => $this->recipient,
            'to' => $this->phone,
            'message' => $this->message,
            'mode' => $this->mode,
            'type' => $this->type,
            'status' => $this->status,
            'sentAt' => $this->created_at?->format('Y-m-d H:i') ?? '',
            'healthCenter' => $this->health_center,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'title' => str($this->type)->replace('_', ' ')->title()->toString(),
            'body' => $this->message,
            'date' => $this->created_at?->toIso8601String(),
            'read' => $this->read_at !== null,
            'related_booking_id' => $this->booking_id ? (string) $this->booking_id : null,
            'mode' => $this->mode,
            'type' => $this->type,
        ];
    }
}
