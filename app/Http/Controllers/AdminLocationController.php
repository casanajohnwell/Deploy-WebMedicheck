<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminLocationController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        if ($request->user()?->healthCenter() !== null) {
            return redirect('/admin/dashboard');
        }

        $validated = $request->validate([
            'region' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        $request->user()?->update($validated);

        return redirect('/admin/dashboard');
    }
}
