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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('kod')->unique();
            $table->string('ad');
            $table->string('kateqoriya')->nullable();
            $table->string('olcu_vahidi')->nullable();
            $table->integer('miqdar')->default(0);
            $table->integer('minimum_miqdar')->default(0);
            $table->decimal('qiymet', 10, 2)->nullable();
            $table->string('tedarikci')->nullable();
            $table->text('qeyd')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
