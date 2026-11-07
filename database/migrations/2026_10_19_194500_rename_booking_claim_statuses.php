<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('bookings')->where('status', 'released')->update(['status' => 'claimed']);
        DB::table('bookings')->where('status', 'cancelled')->update(['status' => 'unclaimed']);
    }

    public function down(): void
    {
        DB::table('bookings')->where('status', 'claimed')->update(['status' => 'released']);
        DB::table('bookings')->where('status', 'unclaimed')->update(['status' => 'cancelled']);
    }
};
