<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isAdmin() && ! $user->is_approved) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('approval_warning', 'Your administrator account is waiting for MHO approval.');
        }

        if (! $user?->isAdmin() || ! $user->is_active) {
            abort(403, 'This area is for MediCheck administrators only.');
        }

        return $next($request);
    }
}
