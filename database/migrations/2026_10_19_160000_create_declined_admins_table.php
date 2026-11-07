<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('declined_admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->text('position')->nullable();
            $table->text('barangay')->nullable();
            $table->text('city')->nullable();
            $table->text('province')->nullable();
            $table->string('health_center')->nullable();
            $table->timestamp('declined_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('declined_admins');
    }
};
