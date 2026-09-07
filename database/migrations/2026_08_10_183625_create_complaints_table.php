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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_id')->constrained()->onDelete('cascade');
            $table->string('surucu_adi')->nullable();
            $table->text('shikayet')->nullable();
            $table->date('bildirilme_tarix')->nullable();
            $table->time('bildirilme_saat')->nullable();
            $table->date('is_baslama_tarix')->nullable();
            $table->time('is_baslama_saat')->nullable();
            $table->date('is_bitme_tarix')->nullable();
            $table->time('is_bitme_saat')->nullable();
            $table->enum('sikayet_tipi', ['qezali', 'texniki_xidmet', 'nasazliq'])->nullable();
            $table->enum('status', ['gözləmədə', 'işdə', 'həll olundu'])->default('gözləmədə');
            $table->json('detallar')->nullable();
            $table->text('qeyd')->nullable();
            $table->string('kim_is_gorub')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
