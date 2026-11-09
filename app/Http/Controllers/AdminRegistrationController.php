<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use App\Models\AdminNotice;
use App\Models\User;
use App\Support\AdminPositions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AdminRegistrationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/Register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'positions' => AdminPositions::all(),
        ]);
    }

    public function store(Request $request, CreateNewUser $creator): RedirectResponse
    {
        if (config('fortify.lowercase_usernames') && $request->filled('email')) {
            $request->merge([
                'email' => strtolower((string) $request->input('email')),
            ]);
        }

        $user = $creator->create($request->all());
        User::query()
            ->where('role', User::ROLE_SUPER_ADMIN)
            ->where('is_active', true)
            ->each(function (User $super) use ($user): void {
                $super->notices()->create([
                    'message' => AdminNotice::registrationMessage($user),
                ]);
            });

        return redirect()
            ->route('login')
            ->with('status', 'Account created. Your administrator account is waiting for MHO approval.');
    }
}
