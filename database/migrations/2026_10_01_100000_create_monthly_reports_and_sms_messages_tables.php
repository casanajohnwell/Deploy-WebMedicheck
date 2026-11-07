<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->string('health_center', 191);
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->json('summary');
            $table->timestamps();

            $table->unique(['health_center', 'year', 'month']);
        });

        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('health_center', 191);
            $table->string('recipient', 191);
            $table->string('phone', 30);
            $table->string('mode', 20);
            $table->string('type', 30);
            $table->text('message');
            $table->string('status', 20)->default('sent');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['health_center', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
        Schema::dropIfExists('monthly_reports');
    }
};
