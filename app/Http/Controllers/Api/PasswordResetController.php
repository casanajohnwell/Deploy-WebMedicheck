<?php

namespace App\Http\Controllers\Api;

use App\Concerns\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PasswordResetCodes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    use PasswordValidationRules;

    public function sendCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $account = $this->findSeeker($validated['email']);

        if ($account !== null) {
            PasswordResetCodes::send($account, 'The code could not be emailed right now. Please try again later.');
        }

        return response()->json([
            'message' => 'If that email belongs to a MediCheck account, a 6-digit code was sent. Check your Gmail inbox and spam folder.',
            'expires_in_minutes' => PasswordResetCodes::CODE_MINUTES,
        ]);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $validated = $this->validateCode($request);

        PasswordResetCodes::check($validated['email'], $validated['code']);

        return response()->json(['message' => 'Code confirmed. Create your new password.']);
    }

    public function reset(Request $request): JsonResponse
    {
        $validated = $this->validateCode($request, [
            'password' => $this->passwordRules(),
        ]);

        PasswordResetCodes::check($validated['email'], $validated['code']);

        $account = $this->findSeeker($validated['email']);

        if ($account === null) {
            throw ValidationException::withMessages([
                'code' => 'This code has expired. Send a new code.',
            ]);
        }

        $account->forceFill(['password' => $validated['password']])->save();
        $account->tokens()->delete();
        PasswordResetCodes::forget($account->email);

        return response()->json(['message' => 'Your password was changed. Log in with your new password.']);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function validateCode(Request $request, array $extra = []): array
    {
        return $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'digits:6'],
            ...$extra,
        ], ['code.digits' => 'Enter the 6-digit code from your email.']);
    }

    private function findSeeker(string $email): ?User
    {
        return User::query()
            ->where('email', strtolower($email))
            ->where('role', User::ROLE_SEEKER)
            ->first();
    }
}
