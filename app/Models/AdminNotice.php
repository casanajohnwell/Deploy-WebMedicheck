<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $message
 * @property Carbon|null $read_at
 */
class AdminNotice extends Model
{
    protected $fillable = [
        'user_id',
        'message',
        'read_at',
    ];

    public static function registrationMessage(User $admin): string
    {
        $center = $admin->healthCenter() ?? 'a barangay health center';

        return "{$admin->name} created an administrator account for {$center}. Approve or decline it.";
    }

    public static function resolveRegistration(User $admin): void
    {
        static::query()
            ->whereNull('read_at')
            ->where('message', static::registrationMessage($admin))
            ->update(['read_at' => now()]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }
}
