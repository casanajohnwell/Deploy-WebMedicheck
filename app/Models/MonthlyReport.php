<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $health_center
 * @property int $year
 * @property int $month
 * @property array<string, mixed> $summary
 */
class MonthlyReport extends Model
{
    protected $fillable = [
        'health_center',
        'year',
        'month',
        'summary',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'summary' => 'array',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontend(): array
    {
        return [
            'id' => $this->id,
            'healthCenter' => $this->health_center,
            'year' => $this->year,
            'month' => $this->month,
            'label' => sprintf('%04d-%02d', $this->year, $this->month),
            'summary' => $this->summary,
        ];
    }
}
