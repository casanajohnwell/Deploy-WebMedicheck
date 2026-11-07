<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PasswordResetCodes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordResetRequestController extends Controller
{
    public const CODE_MINUTES = PasswordResetCodes::CODE_MINUTES;

    public const VERIFIED_MINUTES = 15;

    public const SESSION_EMAIL = 'password_reset.email';

    public const SESSION_VERIFIED = 'password_reset.verified_at';

    public const SESSION_PORTAL = 'password_reset.portal';

    public function sendCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower($validated['email']);
        $account = $this->findAccount($email);

        if ($account !== null) {
            PasswordResetCodes::send($account, 'The code could not be emailed right now. Please try again in a few minutes.');
        }

        $request->session()->put(self::SESSION_EMAIL, $email);
        $request->session()->forget(self::SESSION_VERIFIED);

        return to_route('password.request')->with(
            'status',
            'If that email belongs to a MediCheck account, a 6-digit code was sent. Check your Gmail inbox and spam folder.',
        );
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $email = $request->session()->get(self::SESSION_EMAIL);

        if (! is_string($email)) {
            return to_route('password.request');
        }

        $validated = $request->validate(
            ['code' => ['required', 'digits:6']],
            ['code.digits' => 'Enter the 6-digit code from your email.'],
        );

        PasswordResetCodes::check($email, $validated['code']);
        $request->session()->put(self::SESSION_VERIFIED, now()->getTimestamp());

        return to_route('password.request')->with('status', 'Code confirmed. Create your new password.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $email = $request->session()->get(self::SESSION_EMAIL);
        $verifiedAt = $request->session()->get(self::SESSION_VERIFIED);

        if (! is_string($email) || ! is_int($verifiedAt) || now()->subMinutes(self::VERIFIED_MINUTES)->getTimestamp() > $verifiedAt) {
            $request->session()->forget(self::SESSION_VERIFIED);

            return to_route('password.request')->withErrors([
                'code' => 'Your confirmation expired. Send a new code.',
            ]);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        $account = $this->findAccount($email);

        if ($account === null || ! PasswordResetCodes::exists($email)) {
            $request->session()->forget([self::SESSION_EMAIL, self::SESSION_VERIFIED]);

            return to_route('password.request')->withErrors([
                'email' => 'Send a new code to reset your password.',
            ]);
        }

        $account->forceFill([
            'password' => $validated['password'],
            'password_reset_requested_at' => null,
        ])->save();

        PasswordResetCodes::forget($email);
        $request->session()->forget([self::SESSION_EMAIL, self::SESSION_VERIFIED, self::SESSION_PORTAL]);

        $status = 'Your password was changed. Sign in with your new password.';

        return $account->isSuperAdmin()
            ? to_route('superadmin.login')->with('status', $status)
            : to_route('login')->with('status', $status);
    }

    public function restart(Request $request): RedirectResponse
    {
        $request->session()->forget([self::SESSION_EMAIL, self::SESSION_VERIFIED]);

        return $request->session()->get(self::SESSION_PORTAL) === 'superadmin'
            ? to_route('password.request', ['for' => 'superadmin'])
            : to_route('password.request');
    }

    private function findAccount(string $email): ?User
    {
        return User::query()
            ->where('email', $email)
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])
            ->first();
    }
}
