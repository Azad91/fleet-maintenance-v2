<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('kodu')->unique();
            $table->string('ad');
            $table->string('soyad')->nullable();
            $table->string('telefon')->nullable();
            $table->string('vezifesi')->nullable();
            $table->boolean('aktiv')->default(true);
            $table->text('qeyd')->nullable();
            $table->timestamps();
        });

        // Complaint cədvəlinə driver_id əlavə et
        Schema::table('complaints', function (Blueprint $table) {
            $table->foreignId('driver_id')->nullable()->constrained()->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropConstrainedForeignId('driver_id');
        });
        Schema::dropIfExists('drivers');
    }
};
