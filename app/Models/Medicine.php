<?php

namespace App\Models;

use Database\Factories\MedicineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $category
 * @property string $health_center
 * @property int $quantity
 * @property string $unit
 * @property Carbon $expiration
 * @property string $status
 */
#[Fillable(['name', 'category', 'health_center', 'quantity', 'unit', 'expiration', 'status'])]
class Medicine extends Model
{
    /** @use HasFactory<MedicineFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'expiration' => 'date:Y-m-d',
        ];
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function syncStockStatus(): void
    {
        $this->status = self::stockStatusFor($this->quantity);
    }

    public static function stockStatusFor(int $quantity): string
    {
        if ($quantity <= 0) {
            return 'out_of_stock';
        }

        return $quantity <= 20 ? 'low' : 'available';
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeWithReserved(Builder $query): void
    {
        $query->withSum(['bookings as reserved_quantity' => fn (Builder $bookings) => $bookings
            ->where('status', Booking::STATUS_APPROVED)
            ->where('stock_reserved', false)], 'quantity');
    }

    /**
     * Pieces promised to approved requests that are still waiting for pickup.
     * Stock is only deducted at claim, so these are still counted in $quantity.
     */
    public function reservedQuantity(): int
    {
        if (array_key_exists('reserved_quantity', $this->attributes)) {
            return (int) $this->attributes['reserved_quantity'];
        }

        return (int) $this->bookings()
            ->where('status', Booking::STATUS_APPROVED)
            ->where('stock_reserved', false)
            ->sum('quantity');
    }

    public function availableQuantity(): int
    {
        return max(0, $this->quantity - $this->reservedQuantity());
    }

    /**
     * Keyword => image file in public/images/medicines. First match wins.
     *
     * @var array<string, string>
     */
    public const CATEGORIES = [
        'Vitamins',
        'Antibiotic',
        'Maintenance',
        'Pain & Fever',
        'Cough & Colds',
        'First Aid',
    ];

    private const IMAGE_KEYWORDS = [
        'amox' => 'amoxicillin',
        'mefenamic' => 'mefenamic',
        'paracetamol' => 'paracetamol',
        'biogesic' => 'paracetamol',
        'metformin' => 'metformin',
        'multivit' => 'multivitamins',
        'ascorbic' => 'ascorbic-acid',
        'vitamin c' => 'ascorbic-acid',
        'tuseran' => 'cough-cold',
        'cough' => 'cough-cold',
        'alaxan' => 'pain-reliever',
        'ibuprofen' => 'pain-reliever',
        'jocel' => 'iron',
        'ferrous' => 'iron',
        'iron' => 'iron',
    ];

    public function imagePath(): string
    {
        $name = strtolower((string) $this->name);

        foreach (self::IMAGE_KEYWORDS as $keyword => $file) {
            if (str_contains($name, $keyword)) {
                return "/images/medicines/{$file}.svg";
            }
        }

        $file = match (strtolower((string) $this->category)) {
            'vitamin', 'vitamins', 'supplement' => 'multivitamins',
            'analgesic', 'pain & fever' => 'paracetamol',
            'antibiotic' => 'amoxicillin',
            'maintenance' => 'metformin',
            'cough & colds' => 'cough-cold',
            default => 'default',
        };

        return "/images/medicines/{$file}.svg";
    }

    public function toFrontend(): array
    {
        return [
            'id' => $this->id,
            'image' => $this->imagePath(),
            'name' => $this->name,
            'category' => $this->category,
            'healthCenter' => $this->health_center,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'expiration' => $this->expiration?->format('Y-m-d') ?? '',
            'expiresInDays' => $this->expiration ? (int) today()->diffInDays($this->expiration, false) : null,
            'status' => $this->isExpired() ? 'expired' : ($this->status ?: 'available'),
            'stockStatus' => $this->status ?: 'available',
            'reserved' => $this->reservedQuantity(),
            'available' => $this->availableQuantity(),
        ];
    }

    /**
     * Mobile view: seekers see only what can still be requested.
     *
     * @return array<string, mixed>
     */
    public function toSeekerArray(): array
    {
        $available = $this->availableQuantity();

        return [
            ...$this->toFrontend(),
            'quantity' => $available,
            'status' => self::stockStatusFor($available),
        ];
    }

    public function isExpired(): bool
    {
        return $this->expiration !== null && $this->expiration->lt(today());
    }
}
