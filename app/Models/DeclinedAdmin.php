<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $password
 * @property string|null $position
 * @property string|null $barangay
 * @property string|null $city
 * @property string|null $province
 * @property string|null $health_center
 * @property string|null $previous_status
 * @property string|null $reason
 * @property Carbon $declined_at
 */
class DeclinedAdmin extends Model
{
    protected $hidden = [
        'password',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'position',
        'barangay',
        'city',
        'province',
        'health_center',
        'previous_status',
        'reason',
        'declined_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'encrypted',
            'barangay' => 'encrypted',
            'city' => 'encrypted',
            'province' => 'encrypted',
            'reason' => 'encrypted',
            'declined_at' => 'datetime',
        ];
    }
}
