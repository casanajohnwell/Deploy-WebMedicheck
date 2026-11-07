<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_reset_requested_at')->nullable()->after('registered_via');
            $table->timestamp('password_reset_at')->nullable()->after('password_reset_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['password_reset_requested_at', 'password_reset_at']);
        });
    }
};
