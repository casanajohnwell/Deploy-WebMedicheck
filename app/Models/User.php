<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\HealthCenter;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $google_id
 * @property string $role
 * @property bool $is_active
 * @property bool $is_approved
 * @property string|null $phone
 * @property string|null $position
 * @property string|null $region
 * @property string|null $province
 * @property string|null $city
 * @property string|null $barangay
 * @property string|null $street
 * @property string|null $postal_code
 * @property string|null $avatar_path
 * @property string|null $textbee_device_id
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'email',
    'google_id',
    'password',
    'role',
    'is_active',
    'is_approved',
    'registered_via',
    'textbee_device_id',
    'phone',
    'position',
    'region',
    'province',
    'city',
    'barangay',
    'street',
    'postal_code',
    'avatar_path',
])]
#[Hidden([
    'password',
    'textbee_device_id',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'remember_token',
    'google_id',
    'phone',
    'position',
    'region',
    'province',
    'city',
    'barangay',
    'street',
    'postal_code',
])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Barangay health-center administrator.
     * Stored as admin so current accounts and the mobile app keep working.
     */
    public const ROLE_ADMIN = 'admin';

    public const ROLE_BARANGAY_ADMIN = self::ROLE_ADMIN;

    public const ROLE_SEEKER = 'seeker';

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSeeker(): bool
    {
        return $this->role === self::ROLE_SEEKER;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isApproved(): bool
    {
        return (bool) $this->is_approved;
    }

    /**
     * A barangay may have only one active or still-pending administrator.
     */
    public static function barangayAdminSlotTaken(string $barangay, ?string $city = null, ?int $exceptId = null): bool
    {
        return static::query()
            ->where('role', self::ROLE_ADMIN)
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->get()
            ->contains(function (self $user) use ($barangay, $city): bool {
                $occupiesSlot = ! $user->is_approved || $user->is_active;

                return $occupiesSlot && $user->servesStation($barangay, $city);
            });
    }

    /**
     * @return HasMany<AdminNotice, $this>
     */
    public function notices(): HasMany
    {
        return $this->hasMany(AdminNotice::class);
    }

    public function healthCenter(): ?string
    {
        return HealthCenter::forBarangay($this->barangay);
    }

    /**
     * Public station the admin opened, used to connect mobile users.
     *
     * @return array{region: string|null, province: string|null, city: string|null, barangay: string, postal_code: string|null, health_center: string}|null
     */
    public function toStationArray(): ?array
    {
        if (! filled($this->barangay) || ! filled($this->city)) {
            return null;
        }

        $healthCenter = $this->healthCenter();

        if ($healthCenter === null) {
            return null;
        }

        return [
            'region' => $this->region,
            'province' => $this->province,
            'city' => $this->city,
            'barangay' => $this->barangay,
            'postal_code' => $this->postal_code,
            'health_center' => $healthCenter,
        ];
    }

    public function servesStation(?string $barangay, ?string $city = null, ?string $province = null): bool
    {
        if (! HealthCenter::same($this->barangay, $barangay)) {
            return false;
        }

        if (filled($city) && filled($this->city) && ! HealthCenter::same($this->city, $city)) {
            return false;
        }

        return true;
    }

    /**
     * Unique barangays that already have an administrator.
     *
     * @return list<array{region: string|null, province: string|null, city: string|null, barangay: string, postal_code: string|null, health_center: string}>
     */
    public static function servedStations(): array
    {
        return static::query()
            ->where('role', self::ROLE_ADMIN)
            ->where('is_active', true)
            ->where('is_approved', true)
            ->get()
            ->map(fn (self $user) => $user->toStationArray())
            ->filter()
            ->unique(fn (array $station) => implode('|', [
                HealthCenter::normalize($station['city'] ?? ''),
                HealthCenter::normalize($station['barangay']),
            ]))
            ->values()
            ->all();
    }

    /**
     * @return array{region: string|null, province: string|null, city: string|null, barangay: string, postal_code: string|null, health_center: string}|null
     */
    public static function matchingStation(?string $barangay, ?string $city = null, ?string $province = null): ?array
    {
        $matches = collect(static::servedStations())
            ->filter(function (array $station) use ($barangay, $city): bool {
                if (! HealthCenter::same($station['barangay'], $barangay)) {
                    return false;
                }

                return ! filled($city)
                    || ! filled($station['city'])
                    || HealthCenter::same($station['city'], $city);
            });

        if ($matches->isEmpty()) {
            return null;
        }

        if (filled($province)) {
            $byProvince = $matches->first(
                fn (array $station) => HealthCenter::same($station['province'] ?? null, $province),
            );

            if ($byProvince !== null) {
                return $byProvince;
            }
        }

        return $matches->first();
    }

    public static function adminServes(string $barangay, ?string $city = null, ?string $province = null): bool
    {
        return static::matchingStation($barangay, $city, $province) !== null;
    }

    /**
     * Safe fields for Inertia shared auth (no confidential profile data).
     *
     * @return array<string, mixed>
     */
    public function toSafeAuthArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'email_verified_at' => $this->email_verified_at,
            'two_factor_enabled' => $this->hasEnabledTwoFactorAuthentication(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Authenticated payload for the mobile API (owner-only PII).
     *
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'phone' => $this->phone,
            'position' => $this->isAdmin() ? $this->position : null,
            'region' => $this->region,
            'province' => $this->province,
            'city' => $this->city,
            'barangay' => $this->barangay,
            'street' => $this->street,
            'postal_code' => $this->postal_code,
            'health_center' => $this->healthCenter(),
            'avatar_url' => $this->avatarUrl(),
            'email_verified_at' => $this->email_verified_at,
        ];
    }

    public function avatarUrl(?string $origin = null): ?string
    {
        if (! filled($this->avatar_path)) {
            return null;
        }

        $origin = rtrim($origin ?? (string) config('app.url'), '/');

        return $origin.'/storage/'.$this->avatar_path;
    }

    /**
     * Confidential administrator profile, only for the owner's settings page.
     *
     * @return array<string, string|null>
     */
    public function toConfidentialProfile(): array
    {
        return [
            'phone' => $this->phone,
            'position' => $this->position,
            'region' => $this->region,
            'province' => $this->province,
            'city' => $this->city,
            'barangay' => $this->barangay,
            'street' => $this->street,
            'postal_code' => $this->postal_code,
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_reset_requested_at' => 'datetime',
            'password_reset_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_approved' => 'boolean',
            'phone' => 'encrypted',
            'position' => 'encrypted',
            'region' => 'encrypted',
            'province' => 'encrypted',
            'city' => 'encrypted',
            'barangay' => 'encrypted',
            'street' => 'encrypted',
            'postal_code' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
