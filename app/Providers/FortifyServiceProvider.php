<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Controllers\PasswordResetRequestController;
use App\Models\DeclinedAdmin;
use App\Models\User;
use App\Support\AdminPositions;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::authenticateUsing(function (Request $request) {
            $user = User::query()
                ->where(Fortify::username(), $request->input(Fortify::username()))
                ->first();

            if (! $user || ! Hash::check((string) $request->password, $user->password)) {
                if (! $user) {
                    $email = Str::lower((string) $request->input(Fortify::username()));
                    $declined = DeclinedAdmin::query()
                        ->where('email', $email)
                        ->latest('declined_at')
                        ->first();

                    if (
                        $declined?->password
                        && Hash::check((string) $request->password, $declined->password)
                    ) {
                        throw ValidationException::withMessages([
                            Fortify::username() => 'Your created account is declined.',
                        ]);
                    }
                }

                return null;
            }

            if ($user->isSuperAdmin()) {
                throw ValidationException::withMessages([
                    Fortify::username() => 'Super admin signs in at /superadmin.',
                ]);
            }

            if (! $user->isAdmin()) {
                throw ValidationException::withMessages([
                    Fortify::username() => 'This web portal is for administrators. Please sign in through the MediCheck mobile app.',
                ]);
            }

            if (! $user->is_approved) {
                throw ValidationException::withMessages([
                    Fortify::username() => 'Your administrator account is waiting for MHO approval.',
                ]);
            }

            if (! $user->is_active) {
                throw ValidationException::withMessages([
                    Fortify::username() => 'This account is turned off. Ask the super admin to turn it on.',
                ]);
            }

            $notice = $user->notices()->whereNull('read_at')->latest()->first();

            if ($notice !== null) {
                $message = $notice->message;
                $user->notices()->whereNull('read_at')->update(['read_at' => now()]);

                throw ValidationException::withMessages([
                    'approved' => $message,
                ]);
            }

            return $user;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('AdminLogin', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
            'approvalWarning' => $request->session()->get('approval_warning'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(function (Request $request) {
            $email = $request->session()->get(PasswordResetRequestController::SESSION_EMAIL);

            if ($request->query('for') === 'superadmin') {
                $request->session()->put(PasswordResetRequestController::SESSION_PORTAL, 'superadmin');
            } elseif ($request->query('for') === 'admin' || ($request->query->count() === 0 && ! is_string($email) && ! $request->session()->has('errors') && ! $request->session()->has('status'))) {
                $request->session()->forget(PasswordResetRequestController::SESSION_PORTAL);
            }

            $forSuperAdmin = $request->session()->get(PasswordResetRequestController::SESSION_PORTAL) === 'superadmin';
            $verifiedAt = $request->session()->get(PasswordResetRequestController::SESSION_VERIFIED);
            $verified = is_int($verifiedAt)
                && $verifiedAt >= now()->subMinutes(PasswordResetRequestController::VERIFIED_MINUTES)->getTimestamp();

            return Inertia::render('auth/ForgotPassword', [
                'status' => $request->session()->get('status'),
                'step' => ! is_string($email) ? 'email' : ($verified ? 'password' : 'code'),
                'email' => is_string($email) ? $email : null,
                'codeMinutes' => PasswordResetRequestController::CODE_MINUTES,
                'passwordRules' => Password::defaults()->toPasswordRulesString(),
                'forSuperAdmin' => $forSuperAdmin,
                'loginUrl' => $forSuperAdmin ? route('superadmin.login', absolute: false) : route('login', absolute: false),
            ]);
        });

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/Register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'positions' => AdminPositions::all(),
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return [
                Limit::perMinute(3)->by($throttleKey),
                Limit::perHour(10)->by($request->ip()),
            ];
        });

        RateLimiter::for('passkeys', function (Request $request) {
            return Limit::perMinute(10)->by(
                ($request->input('credential.id') ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });

        RateLimiter::for('api-auth', function (Request $request) {
            $identity = Str::lower((string) $request->input('email', $request->ip()));

            return [
                Limit::perMinute(5)->by($identity.'|'.$request->ip()),
                Limit::perHour(30)->by($request->ip()),
            ];
        });
    }
}
