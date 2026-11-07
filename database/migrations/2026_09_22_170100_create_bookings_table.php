<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_id')->nullable()->constrained()->nullOnDelete();
            $table->string('seeker_name', 191);
            $table->string('medicine_name', 191);
            $table->unsignedInteger('quantity');
            $table->string('health_center', 191);
            $table->string('phone', 30);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->index(['status', 'health_center']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
