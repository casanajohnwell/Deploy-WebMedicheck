<?php

use App\Models\Medicine;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->string('status', 32)->default('available')->after('expiration');
        });

        Medicine::query()->each(function (Medicine $medicine): void {
            $status = 'available';

            if ($medicine->quantity === 0) {
                $status = 'out_of_stock';
            } elseif ($medicine->quantity <= 20) {
                $status = 'low';
            }

            $medicine->forceFill(['status' => $status])->save();
        });
    }

    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
