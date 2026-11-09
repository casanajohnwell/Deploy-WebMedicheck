<?php

use App\Models\AdminNotice;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $pending = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('is_approved', false)
            ->get()
            ->map(fn (User $admin) => AdminNotice::registrationMessage($admin))
            ->all();

        AdminNotice::query()
            ->whereNull('read_at')
            ->where('message', 'like', '% created an administrator account for %. Approve or decline it.')
            ->whereNotIn('message', $pending)
            ->update(['read_at' => now()]);
    }

    public function down(): void
    {
        //
    }
};
