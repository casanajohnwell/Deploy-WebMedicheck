<?php

use App\Models\Medicine;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            if (! Schema::hasColumn('bookings', 'claim_days')) {
                $table->unsignedSmallInteger('claim_days')->nullable()->after('stock_reserved');
            }

            if (! Schema::hasColumn('bookings', 'claim_by')) {
                $table->timestamp('claim_by')->nullable()->after('claim_days');
            }
        });

        $reservedOpen = DB::table('bookings')
            ->whereIn('status', ['pending', 'approved'])
            ->where('stock_reserved', true)
            ->get(['id', 'medicine_id', 'quantity']);

        foreach ($reservedOpen as $booking) {
            if ($booking->medicine_id) {
                $medicine = Medicine::query()->find($booking->medicine_id);

                if ($medicine) {
                    $medicine->quantity += (int) $booking->quantity;
                    $medicine->syncStockStatus();
                    $medicine->save();
                }
            }

            DB::table('bookings')->where('id', $booking->id)->update(['stock_reserved' => false]);
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $drop = array_filter([
                Schema::hasColumn('bookings', 'claim_days') ? 'claim_days' : null,
                Schema::hasColumn('bookings', 'claim_by') ? 'claim_by' : null,
            ]);

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
