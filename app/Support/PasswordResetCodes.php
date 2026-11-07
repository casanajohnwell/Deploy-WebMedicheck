<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

class PasswordResetCodes
{
    public const CODE_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public static function send(User $account, string $failureMessage): void
    {
        $email = strtolower($account->email);
        $existing = DB::table('password_reset_tokens')->where('email', $email)->first();

        if ($existing !== null && now()->subMinute()->lt($existing->created_at)) {
            throw ValidationException::withMessages([
                'email' => 'A code was sent less than a minute ago. Please wait, then try again.',
            ]);
        }

        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($code), 'created_at' => now()],
        );
        RateLimiter::clear(self::attemptKey($email));

        try {
            $account->notify(new PasswordResetCode($code, self::CODE_MINUTES));
        } catch (Throwable $exception) {
            Log::warning('Password reset code email failed.', ['error' => $exception->getMessage()]);
            self::forget($email);

            throw ValidationException::withMessages(['email' => $failureMessage]);
        }
    }

    public static function check(string $email, string $code): void
    {
        $email = strtolower($email);
        $key = self::attemptKey($email);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            self::forget($email);

            throw ValidationException::withMessages([
                'code' => 'Too many wrong codes. Send a new code.',
            ]);
        }

        $row = DB::table('password_reset_tokens')->where('email', $email)->first();

        if ($row === null || now()->subMinutes(self::CODE_MINUTES)->gt($row->created_at)) {
            throw ValidationException::withMessages([
                'code' => 'This code has expired. Send a new code.',
            ]);
        }

        if (! Hash::check($code, $row->token)) {
            RateLimiter::hit($key, self::CODE_MINUTES * 60);

            throw ValidationException::withMessages([
                'code' => 'The code is incorrect.',
            ]);
        }

        RateLimiter::clear($key);
    }

    public static function exists(string $email): bool
    {
        return DB::table('password_reset_tokens')->where('email', strtolower($email))->exists();
    }

    public static function forget(string $email): void
    {
        DB::table('password_reset_tokens')->where('email', strtolower($email))->delete();
    }

    private static function attemptKey(string $email): string
    {
        return 'password-reset-code|'.$email;
    }
}
