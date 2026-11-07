<?php

namespace App\Http\Controllers;

use App\Models\AdminNotice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminNoticeController extends Controller
{
    public function read(Request $request, AdminNotice $notice): RedirectResponse
    {
        abort_unless($notice->user_id === $request->user()?->id, 404);

        $notice->update(['read_at' => now()]);

        return back();
    }
}
