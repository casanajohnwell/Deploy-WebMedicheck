<?php

namespace App\Models;

use Database\Factories\MedicineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
        if ($this->quantity <= 0) {
            $this->status = 'out_of_stock';

            return;
        }

        $this->status = $this->quantity <= 20 ? 'low' : 'available';
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
        ];
    }

    public function isExpired(): bool
    {
        return $this->expiration !== null && $this->expiration->lt(today());
    }
}
