<?php

namespace App\Http\Controllers\Api;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    use PasswordValidationRules, ProfileValidationRules;

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            ...$this->profileRules(),
            ...$this->seekerInfoRules(),
            'password' => $this->passwordRules(),
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $station = User::matchingStation($validated['barangay'], $validated['city'], $validated['province']);

        if ($station === null) {
            throw ValidationException::withMessages([
                'barangay' => 'No barangay health center is open in that location yet. Choose the same lugar the administrator set.',
            ]);
        }

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => User::ROLE_SEEKER,
            'phone' => $validated['phone'],
            'region' => $station['region'] ?? $validated['region'],
            'province' => $station['province'] ?? $validated['province'],
            'city' => $station['city'] ?? $validated['city'],
            'barangay' => $station['barangay'],
            'street' => $validated['street'] ?? '',
            'postal_code' => $station['postal_code'] ?? $validated['postal_code'] ?? null,
        ]);

        return $this->tokenResponse($user, $request, $request->input('device_name'), 'Account created.', 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        return $this->tokenResponse($user, $request, $request->input('device_name'), 'Logged in.');
    }

    public function logout(Request $request): JsonResponse
    {
        $bearer = $request->bearerToken();

        if ($bearer) {
            PersonalAccessToken::findToken($bearer)?->delete();
        } else {
            $token = $request->user()?->currentAccessToken();

            if ($token instanceof PersonalAccessToken) {
                $token->delete();
            }
        }

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        return response()->json([
            'user' => $user ? $this->userPayload($user, $request) : null,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:30'],
            'region' => ['sometimes', 'nullable', 'string', 'max:255'],
            'province' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'barangay' => ['sometimes', 'nullable', 'string', 'max:255'],
            'street' => ['sometimes', 'nullable', 'string', 'max:255'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $user->fill($validated);
        $user->save();

        return response()->json([
            'message' => 'Profile updated.',
            'user' => $this->userPayload($user, $request),
        ]);
    }

    public function avatar(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $path = $request->file('avatar')?->store('avatars', 'public');

        if (! is_string($path) || $path === '') {
            throw ValidationException::withMessages([
                'avatar' => 'Could not save that photo. Try another image.',
            ]);
        }

        if (filled($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->avatar_path = $path;
        $user->save();

        return response()->json([
            'message' => 'Profile photo updated.',
            'user' => $this->userPayload($user, $request),
        ]);
    }

    public function password(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password:sanctum'],
            'password' => $this->passwordRules(),
        ]);

        $request->user()?->update([
            'password' => $validated['password'],
        ]);

        return response()->json([
            'message' => 'Password updated.',
        ]);
    }

    private function tokenResponse(User $user, Request $request, mixed $deviceName, string $message, int $status = 200): JsonResponse
    {
        $token = $user->createToken(
            is_string($deviceName) && $deviceName !== '' ? $deviceName : 'mobile',
        )->plainTextToken;

        return response()->json([
            'message' => $message,
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user, $request),
        ], $status);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user, Request $request): array
    {
        return [
            ...$user->toApiArray(),
            'avatar_url' => $user->avatarUrl($request->getSchemeAndHttpHost()),
        ];
    }
}
