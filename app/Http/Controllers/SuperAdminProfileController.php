<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\EmailChangeCode;
use App\Support\PasswordResetCodes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SuperAdminProfileController extends Controller
{
    private const SESSION_KEY = 'superadmin_email_change';

    public function show(Request $request): Response
    {
        $pending = $this->pending($request);

        return Inertia::render('SuperAdminProfile', [
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'pendingEmail' => $pending['email'] ?? null,
            'codeMinutes' => PasswordResetCodes::CODE_MINUTES,
        ]);
    }

    public function sendEmailCode(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($user->id),
                Rule::notIn([strtolower($user->email)]),
            ],
            'current_password' => ['required', 'current_password'],
        ], [
            'email.not_in' => 'This is already your email address.',
        ]);

        $email = strtolower($validated['email']);
        $previous = $request->session()->get(self::SESSION_KEY);

        if (
            is_array($previous)
            && $previous['email'] === $email
            && now()->subMinute()->timestamp < $previous['sent_at']
        ) {
            throw ValidationException::withMessages([
                'email' => 'A code was sent less than a minute ago. Please wait, then try again.',
            ]);
        }

        $code = (string) random_int(100000, 999999);

        try {
            Notification::route('mail', $email)
                ->notify(new EmailChangeCode($code, PasswordResetCodes::CODE_MINUTES));
        } catch (Throwable $exception) {
            Log::warning('Email change code failed.', ['error' => $exception->getMessage()]);

            throw ValidationException::withMessages([
                'email' => 'The code could not be sent. Check the email address and the mail settings, then try again.',
            ]);
        }

        $request->session()->put(self::SESSION_KEY, [
            'email' => $email,
            'code' => Hash::make($code),
            'sent_at' => now()->timestamp,
            'attempts' => 0,
        ]);

        return back()->with('status', "We sent a 6-digit code to {$email}.");
    }

    public function verifyEmailCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $pending = $this->pending($request);

        if ($pending === null) {
            throw ValidationException::withMessages([
                'code' => 'This code has expired. Send a new code.',
            ]);
        }

        if ($pending['attempts'] >= PasswordResetCodes::MAX_ATTEMPTS) {
            $request->session()->forget(self::SESSION_KEY);

            throw ValidationException::withMessages([
                'code' => 'Too many wrong codes. Send a new code.',
            ]);
        }

        if (! Hash::check($validated['code'], $pending['code'])) {
            $pending['attempts']++;
            $request->session()->put(self::SESSION_KEY, $pending);

            throw ValidationException::withMessages([
                'code' => 'The code is incorrect.',
            ]);
        }

        if (User::query()->where('email', $pending['email'])->whereKeyNot($request->user()->id)->exists()) {
            $request->session()->forget(self::SESSION_KEY);

            throw ValidationException::withMessages([
                'code' => 'That email is already used by another account.',
            ]);
        }

        $request->user()->forceFill([
            'email' => $pending['email'],
            'email_verified_at' => now(),
        ])->save();

        $request->session()->forget(self::SESSION_KEY);

        return back()->with('status', 'Your email was changed. Use the new email the next time you log in.');
    }

    public function cancelEmailChange(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return back();
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->forceFill([
            'password' => $validated['password'],
        ])->save();

        return back()->with('status', 'Your password was changed.');
    }

    /**
     * @return array{email: string, code: string, sent_at: int, attempts: int}|null
     */
    private function pending(Request $request): ?array
    {
        $pending = $request->session()->get(self::SESSION_KEY);

        if (! is_array($pending)) {
            return null;
        }

        if (now()->subMinutes(PasswordResetCodes::CODE_MINUTES)->timestamp > $pending['sent_at']) {
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        return $pending;
    }
}
