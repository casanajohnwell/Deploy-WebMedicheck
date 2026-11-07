<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('phone')->nullable()->after('email');
            $table->text('position')->nullable()->after('phone');
            $table->text('region')->nullable()->after('position');
            $table->text('province')->nullable()->after('region');
            $table->text('city')->nullable()->after('province');
            $table->text('barangay')->nullable()->after('city');
            $table->text('street')->nullable()->after('barangay');
            $table->text('postal_code')->nullable()->after('street');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'position',
                'region',
                'province',
                'city',
                'barangay',
                'street',
                'postal_code',
            ]);
        });
    }
};
