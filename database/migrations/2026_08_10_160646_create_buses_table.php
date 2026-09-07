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
        Schema::create('buses', function (Blueprint $table) {
            $table->id();
            $table->string('xett_no')->unique()->nullable();     // XƏTT №
            $table->string('dqn')->unique();             // DQN
            $table->text('shikayet')->nullable();        // ŞİKAYƏT
            $table->date('tarix')->nullable();           // TARİX
            $table->boolean('aktiv')->default(true);     // AKTİV (true = aktiv, false = passiv)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buses');
    }
};
