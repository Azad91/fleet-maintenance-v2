<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garage_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('garage_id')->constrained('garages')->onDelete('cascade');
            $table->enum('role', ['admin', 'manager', 'operator', 'viewer'])->default('operator');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'garage_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garage_user');
    }
};
