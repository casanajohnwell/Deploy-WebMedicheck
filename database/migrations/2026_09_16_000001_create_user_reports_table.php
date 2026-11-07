<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_reports', function (Blueprint $table) {
            $table->id();
            $table->string('seeker', 191);
            $table->string('phone', 50);
            $table->unsignedInteger('booking_id')->nullable();
            $table->string('given_medicine', 191);
            $table->string('correct_medicine', 191);
            $table->string('health_center', 191);
            $table->text('reason');
            $table->string('status', 32)->default('pending');
            $table->timestamp('reported_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_reports');
    }
};
