<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use App\Models\Booking;
use App\Models\DeclinedAdmin;
use App\Models\Medicine;
use App\Models\MonthlyReport;
use App\Models\User;
use App\Notifications\AdminAccountApproved;
use App\Notifications\AdminAccountDeclined;
use App\Services\MonthlyReportRecorder;
use App\Support\AdminPositions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SuperAdminController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if ($request->user()?->isSuperAdmin()) {
            return redirect()->route('superadmin.dashboard');
        }

        return Inertia::render('SuperAdminLogin', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = strtolower($validated['email']);
        $user = User::query()->where('email', $email)->first();

        if (
            $user === null
            || ! $user->isSuperAdmin()
            || ! $user->is_active
            || ! Auth::getProvider()->validateCredentials($user, ['password' => $validated['password']])
        ) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match a super admin account.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route('superadmin.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('superadmin.login');
    }

    public function dashboard(Request $request, MonthlyReportRecorder $recorder): Response
    {
        $centers = $this->centers();
        $selected = (string) $request->query('center', '');
        $center = collect($centers)->firstWhere('health_center', $selected);

        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);
        $year = max(2000, min(2100, $year));
        $month = max(1, min(12, $month));

        $detail = null;

        if ($center !== null) {
            $healthCenter = $center['health_center'];
            $selectedMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $report = MonthlyReport::query()
                ->where('health_center', $healthCenter)
                ->where('year', $year)
                ->where('month', $month)
                ->first();

            if ($selectedMonth->isSameMonth(now())) {
                $report = $recorder->syncCurrent($healthCenter);
            } elseif ($report === null) {
                $report = $recorder->syncMonth($healthCenter, $selectedMonth);
            }

            $detail = [
                'health_center' => $healthCenter,
                'medicines' => Medicine::query()
                    ->where('health_center', $healthCenter)
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Medicine $medicine) => [
                        'id' => $medicine->id,
                        'name' => $medicine->name,
                        'category' => $medicine->category,
                        'quantity' => $medicine->quantity,
                        'unit' => $medicine->unit,
                        'status' => $medicine->status,
                        'expiration' => $medicine->expiration?->toDateString(),
                    ])
                    ->all(),
                'report' => $report->toFrontend(),
            ];
        }

        return Inertia::render('SuperAdminDashboard', [
            'metrics' => $this->metrics(),
            'pendingAdmins' => $this->pendingAdmins(),
            'declinedAdmins' => $this->declinedAdmins(),
            'charts' => $this->charts(),
            'centers' => $centers,
            'center' => $detail,
            'year' => $year,
            'month' => $month,
            'navCounts' => $this->navCounts(),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * @return array{pending: int, rejected: int}
     */
    private function navCounts(): array
    {
        return [
            'pending' => User::query()
                ->where('role', User::ROLE_ADMIN)
                ->where('is_approved', false)
                ->count(),
            'rejected' => DeclinedAdmin::query()->count(),
        ];
    }

    public function accounts(Request $request): Response
    {
        $accounts = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('is_approved', true)
            ->latest()
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'position' => $user->position,
                'barangay' => $user->barangay,
                'city' => $user->city,
                'province' => $user->province,
                'health_center' => $user->healthCenter(),
                'is_active' => $user->is_active,
                'walk_in' => $user->registered_via === 'walk_in',
            ])
            ->all();

        $tab = (string) $request->query('tab', 'approved');

        return Inertia::render('SuperAdminAccounts', [
            'accounts' => $accounts,
            'pendingAdmins' => $this->pendingAdmins(),
            'declinedAdmins' => $this->declinedAdmins(),
            'tab' => in_array($tab, ['approved', 'pending', 'rejected'], true) ? $tab : 'approved',
            'navCounts' => $this->navCounts(),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function users(Request $request): Response
    {
        return Inertia::render('SuperAdminUsers', [
            'seekers' => $this->seekers(),
            'navCounts' => $this->navCounts(),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function walkIn(Request $request): Response
    {
        return Inertia::render('SuperAdminWalkIn', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'positions' => AdminPositions::all(),
            'navCounts' => $this->navCounts(),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function storeWalkIn(Request $request, CreateNewUser $creator): RedirectResponse
    {
        $request->merge([
            'email' => strtolower((string) $request->input('email')),
        ]);

        $user = $creator->createWalkIn($request->all());
        $center = $user->healthCenter() ?? 'your barangay health center';

        $user->notices()->create([
            'message' => "Your administrator account for {$center} was created and approved by the MHO. You can sign in now.",
        ]);

        return redirect()
            ->route('superadmin.accounts')
            ->with('status', "Walk-in account for {$user->name} created and approved. The nurse can sign in now.");
    }

    public function updateAccount(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isAdmin(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($user->id)],
            'is_active' => ['required', 'boolean'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'is_active' => $validated['is_active'],
        ]);

        return redirect()
            ->route('superadmin.accounts')
            ->with('status', 'NURSE account updated.');
    }

    public function destroyAccount(User $user): RedirectResponse
    {
        abort_unless($user->isAdmin(), 404);

        $user->delete();

        return redirect()
            ->route('superadmin.accounts')
            ->with('status', 'NURSE account deleted.');
    }

    public function approveAccount(User $user): RedirectResponse
    {
        abort_unless($user->isAdmin() && ! $user->is_approved, 404);

        $user->update(['is_approved' => true]);

        $center = $user->healthCenter() ?? 'your barangay health center';
        $user->notices()->create([
            'message' => "Your administrator account for {$center} was approved. You can sign in now.",
        ]);

        $emailed = $this->emailAccountDecision($user, new AdminAccountApproved($center));

        return back()->with('status', $emailed
            ? 'Barangay administrator approved. An email was sent to let them know.'
            : 'Barangay administrator approved, but the email could not be sent.');
    }

    public function declineAccount(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isAdmin(), 404);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $wasApproved = (bool) $user->is_approved;
        $reason = filled($validated['reason'] ?? null) ? trim($validated['reason']) : null;

        DeclinedAdmin::query()->create([
            'name' => $user->name,
            'email' => $user->email,
            'password' => $user->password,
            'position' => $user->position,
            'barangay' => $user->barangay,
            'city' => $user->city,
            'province' => $user->province,
            'health_center' => $user->healthCenter(),
            'previous_status' => $wasApproved ? 'approved' : 'pending',
            'reason' => $reason,
            'declined_at' => now(),
        ]);

        $this->emailAccountDecision(
            $user,
            new AdminAccountDeclined($user->healthCenter() ?? 'your barangay health center', $reason),
        );

        $user->delete();

        return back()->with(
            'status',
            $wasApproved
                ? 'NURSE account rejected. It is now listed under Rejected accounts.'
                : 'Administrator rejected. The account is listed under Rejected accounts.',
        );
    }

    private function emailAccountDecision(User $user, Notification $notification): bool
    {
        try {
            $user->notify($notification);

            return true;
        } catch (Throwable $exception) {
            Log::warning('Could not email the account decision.', ['user_id' => $user->id, 'error' => $exception->getMessage()]);

            return false;
        }
    }

    public function destroyDeclined(DeclinedAdmin $declinedAdmin): RedirectResponse
    {
        $declinedAdmin->delete();

        return back()->with('status', 'Rejected account record removed.');
    }

    /**
     * @return array{activeBarangays: int, pendingApprovals: int, healthWorkers: int, available: int, lowStock: int, outOfStock: int}
     */
    private function metrics(): array
    {
        $admins = User::query()->where('role', User::ROLE_ADMIN)->get();

        return [
            'activeBarangays' => $admins
                ->filter(fn (User $user) => $user->is_approved && $user->is_active && $user->healthCenter() !== null)
                ->unique(fn (User $user) => $user->healthCenter())
                ->count(),
            'pendingApprovals' => $admins->where('is_approved', false)->count(),
            'healthWorkers' => $admins->where('is_approved', true)->count(),
            'available' => Medicine::query()->where('status', 'available')->count(),
            'lowStock' => Medicine::query()->where('status', 'low')->count(),
            'outOfStock' => Medicine::query()->where('status', 'out_of_stock')->count(),
        ];
    }

    /**
     * @return list<array{id: int, name: string, email: string, position: string|null, barangay: string|null, city: string|null, province: string|null, health_center: string|null}>
     */
    private function pendingAdmins(): array
    {
        return User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('is_approved', false)
            ->latest()
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'position' => $user->position,
                'barangay' => $user->barangay,
                'city' => $user->city,
                'province' => $user->province,
                'health_center' => $user->healthCenter(),
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, email: string, phone: string|null, barangay: string|null, city: string|null, province: string|null, is_active: bool, bookings: int, joined: string|null}>
     */
    private function seekers(): array
    {
        $bookingCounts = Booking::query()
            ->selectRaw('user_id, count(*) as total')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        return User::query()
            ->where('role', User::ROLE_SEEKER)
            ->latest()
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'barangay' => $user->barangay,
                'city' => $user->city,
                'province' => $user->province,
                'is_active' => (bool) $user->is_active,
                'bookings' => (int) ($bookingCounts[$user->id] ?? 0),
                'joined' => $user->created_at?->timezone('Asia/Manila')->format('M j, Y'),
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, email: string, position: string|null, barangay: string|null, city: string|null, province: string|null, health_center: string|null, declined_at: string|null}>
     */
    private function declinedAdmins(): array
    {
        return DeclinedAdmin::query()
            ->latest('declined_at')
            ->get()
            ->map(fn (DeclinedAdmin $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'email' => $account->email,
                'position' => $account->position,
                'barangay' => $account->barangay,
                'city' => $account->city,
                'province' => $account->province,
                'health_center' => $account->health_center,
                'previous_status' => $account->previous_status ?? 'pending',
                'reason' => $account->reason,
                'declined_at' => $account->declined_at?->timezone('Asia/Manila')->format('M j, Y g:i A'),
            ])
            ->all();
    }

    /**
     * @return array{inventory: list<array{label: string, available: int, low: int, out: int}>}
     */
    private function charts(): array
    {
        $medicines = Medicine::query()->get()->groupBy('health_center');

        return [
            'inventory' => $medicines->map(fn ($rows, $center) => [
                'label' => (string) $center,
                'available' => $rows->where('status', 'available')->count(),
                'low' => $rows->where('status', 'low')->count(),
                'out' => $rows->where('status', 'out_of_stock')->count(),
            ])->values()->all(),
        ];
    }

    /**
     * @return list<array{health_center: string, barangay: string|null, city: string|null, province: string|null, available: int, low_stock: int, out_of_stock: int}>
     */
    private function centers(): array
    {
        $fromAdmins = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('is_active', true)
            ->where('is_approved', true)
            ->get()
            ->map(fn (User $user) => $user->toStationArray())
            ->filter();

        $names = $fromAdmins
            ->pluck('health_center')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return $names->map(function (string $healthCenter) use ($fromAdmins) {
            $station = $fromAdmins->first(
                fn (array $row) => $row['health_center'] === $healthCenter,
            );

            return [
                'health_center' => $healthCenter,
                'barangay' => $station['barangay'] ?? null,
                'city' => $station['city'] ?? null,
                'province' => $station['province'] ?? null,
                'available' => Medicine::query()
                    ->where('health_center', $healthCenter)
                    ->where('status', 'available')
                    ->count(),
                'low_stock' => Medicine::query()
                    ->where('health_center', $healthCenter)
                    ->where('status', 'low')
                    ->count(),
                'out_of_stock' => Medicine::query()
                    ->where('health_center', $healthCenter)
                    ->where('status', 'out_of_stock')
                    ->count(),
            ];
        })->all();
    }
}
