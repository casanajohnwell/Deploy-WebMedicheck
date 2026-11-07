<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('category', 191);
            $table->string('health_center', 191);
            $table->unsignedInteger('quantity')->default(0);
            $table->string('unit', 50);
            $table->date('expiration');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
