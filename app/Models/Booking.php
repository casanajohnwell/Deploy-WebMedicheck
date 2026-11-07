<?php

namespace App\Models;

use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $medicine_id
 * @property string $seeker_name
 * @property string $medicine_name
 * @property int $quantity
 * @property string $health_center
 * @property string $phone
 * @property string|null $notes
 * @property string $status
 * @property bool $stock_reserved
 * @property int|null $claim_days
 * @property Carbon|null $claim_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'medicine_id',
    'seeker_name',
    'medicine_name',
    'quantity',
    'health_center',
    'phone',
    'notes',
    'status',
    'stock_reserved',
    'claim_days',
    'claim_by',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    public const MAX_QUANTITY = 20;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_CLAIMED = 'claimed';

    public const STATUS_UNCLAIMED = 'unclaimed';

    public const DEFAULT_CLAIM_DAYS = 3;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'stock_reserved' => 'boolean',
            'claim_days' => 'integer',
            'claim_by' => 'datetime',
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
     * @return BelongsTo<Medicine, $this>
     */
    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function setClaimPeriod(int $claimDays): void
    {
        $days = max(1, min(30, $claimDays));

        $attributes = [
            'claim_days' => $days,
        ];

        if ($this->status === self::STATUS_APPROVED) {
            $attributes['claim_by'] = now('Asia/Manila')
                ->addDays($days)
                ->endOfDay()
                ->utc();
        }

        $this->forceFill($attributes)->save();
        $this->refresh();
    }

    public function approve(?int $claimDays = null): void
    {
        $days = max(1, min(30, $claimDays ?? $this->claim_days ?? self::DEFAULT_CLAIM_DAYS));
        $this->changeStatus(self::STATUS_APPROVED, $days);
    }

    public function decline(): void
    {
        $this->changeStatus(self::STATUS_DECLINED);
    }

    public function claim(): void
    {
        $this->changeStatus(self::STATUS_CLAIMED);
    }

    public function markUnclaimed(): void
    {
        $this->changeStatus(self::STATUS_UNCLAIMED);
    }

    public static function expireOverdue(?string $healthCenter = null, ?int $userId = null): void
    {
        $overdue = static::query()
            ->where('status', self::STATUS_APPROVED)
            ->whereNotNull('claim_by')
            ->where('claim_by', '<', now())
            ->when(filled($healthCenter), fn ($query) => $query->where('health_center', $healthCenter))
            ->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->get();

        foreach ($overdue as $booking) {
            $booking->markUnclaimed();
            $booking->refresh();
            app(\App\Services\MonthlyReportRecorder::class)->syncCurrent($booking->health_center);
        }
    }

    /**
     * @return array{id: int, seeker: string, medicine: string, quantity: int, healthCenter: string, requestedAt: string, status: string, phone: string, notes: string, claimDays: int|null, claimBy: string}
     */
    public function toFrontend(): array
    {
        return [
            'id' => $this->id,
            'seeker' => $this->seeker_name,
            'medicine' => $this->medicine_name,
            'quantity' => $this->quantity,
            'healthCenter' => $this->health_center,
            'requestedAt' => $this->created_at
                ?->timezone('Asia/Manila')
                ->format('M j, Y g:i A') ?? '',
            'status' => $this->status,
            'phone' => $this->phone,
            'notes' => $this->notes ?? '',
            'claimDays' => $this->claim_days,
            'claimBy' => $this->claim_by
                ?->timezone('Asia/Manila')
                ->format('M j, Y') ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'medicine_id' => $this->medicine_id,
            'seeker' => $this->seeker_name,
            'medicine' => $this->medicine_name,
            'quantity' => $this->quantity,
            'health_center' => $this->health_center,
            'phone' => $this->phone,
            'notes' => $this->notes,
            'status' => $this->status,
            'claim_days' => $this->claim_days,
            'claim_by' => $this->claim_by
                ?->timezone('Asia/Manila')
                ->format('M j, Y'),
            'requested_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function changeStatus(string $next, ?int $claimDays = null): void
    {
        DB::transaction(function () use ($next, $claimDays): void {
            /** @var self $booking */
            $booking = static::query()->lockForUpdate()->findOrFail($this->id);
            $current = $booking->status;

            $allowed = match ($next) {
                self::STATUS_APPROVED => [self::STATUS_PENDING],
                self::STATUS_DECLINED => [self::STATUS_PENDING],
                self::STATUS_CLAIMED => [self::STATUS_APPROVED],
                self::STATUS_UNCLAIMED => [self::STATUS_PENDING, self::STATUS_APPROVED],
                default => [],
            };

            if (! in_array($current, $allowed, true)) {
                throw ValidationException::withMessages([
                    'status' => 'That booking status change is not allowed.',
                ]);
            }

            if ($next === self::STATUS_APPROVED) {
                $booking->ensureStockAvailable();
            }

            if ($next === self::STATUS_CLAIMED && ! $booking->stock_reserved) {
                $booking->deductStock();
                $booking->stock_reserved = true;
            }

            $returnsStock = ($next === self::STATUS_DECLINED && $booking->stock_reserved)
                || ($next === self::STATUS_UNCLAIMED && $booking->stock_reserved);

            if ($returnsStock) {
                $booking->restoreStock();
                $booking->stock_reserved = false;
            }

            $attributes = [
                'status' => $next,
                'stock_reserved' => $booking->stock_reserved,
            ];

            if ($next === self::STATUS_APPROVED) {
                $days = max(1, min(30, $claimDays ?? self::DEFAULT_CLAIM_DAYS));
                $attributes['claim_days'] = $days;
                $attributes['claim_by'] = now('Asia/Manila')
                    ->addDays($days)
                    ->endOfDay()
                    ->utc();
            }

            $booking->forceFill($attributes)->save();
            $this->refresh();
        });
    }

    public function holdStock(): void
    {
        if ($this->stock_reserved) {
            return;
        }

        $this->deductStock();
        $this->forceFill(['stock_reserved' => true])->save();
    }

    private function ensureStockAvailable(): void
    {
        if (! $this->medicine_id) {
            return;
        }

        $medicine = Medicine::query()->lockForUpdate()->find($this->medicine_id);

        if (! $medicine) {
            throw ValidationException::withMessages([
                'status' => 'The requested medicine is no longer available.',
            ]);
        }

        if ($medicine->quantity < $this->quantity) {
            throw ValidationException::withMessages([
                'status' => 'Not enough stock to approve this request.',
            ]);
        }
    }

    private function deductStock(): void
    {
        if (! $this->medicine_id) {
            return;
        }

        $medicine = Medicine::query()->lockForUpdate()->find($this->medicine_id);

        if (! $medicine) {
            throw ValidationException::withMessages([
                'status' => 'The requested medicine is no longer available.',
            ]);
        }

        if ($medicine->quantity < $this->quantity) {
            throw ValidationException::withMessages([
                'status' => 'Not enough stock to claim this request.',
            ]);
        }

        $medicine->quantity -= $this->quantity;
        $medicine->syncStockStatus();
        $medicine->save();
    }

    private function restoreStock(): void
    {
        if (! $this->medicine_id) {
            return;
        }

        $medicine = Medicine::query()->lockForUpdate()->find($this->medicine_id);

        if (! $medicine) {
            return;
        }

        $medicine->quantity += $this->quantity;
        $medicine->syncStockStatus();
        $medicine->save();
    }
}
