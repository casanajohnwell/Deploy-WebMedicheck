<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('declined_admins', function (Blueprint $table) {
            $table->string('previous_status')->nullable()->after('health_center');
            $table->text('reason')->nullable()->after('previous_status');
        });
    }

    public function down(): void
    {
        Schema::table('declined_admins', function (Blueprint $table) {
            $table->dropColumn(['previous_status', 'reason']);
        });
    }
};
