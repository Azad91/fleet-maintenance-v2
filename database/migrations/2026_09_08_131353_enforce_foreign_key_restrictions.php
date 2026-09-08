<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Şikayətlər cədvəlində avtobusun silinməsini qadağan edirik
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropForeign(['bus_id']);
            $table->foreign('bus_id')->references('id')->on('buses')->onDelete('restrict');
        });

        // 2. Gündəlik KM qeydlərində avtobusun silinməsini qadağan edirik
        Schema::table('daily_km_records', function (Blueprint $table) {
            $table->dropForeign(['bus_id']);
            $table->foreign('bus_id')->references('id')->on('buses')->onDelete('restrict');
        });

        // 3. Avtobus statuslarında avtobusun silinməsini qadağan edirik
        Schema::table('bus_daily_statuses', function (Blueprint $table) {
            $table->dropForeign(['bus_id']);
            $table->foreign('bus_id')->references('id')->on('buses')->onDelete('restrict');
        });

        // 4. Şikayət detallarında işçinin (employee) silinməsini qadağan edirik
        Schema::table('complaint_details', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('restrict');

            // Şikayət fiziki silinərsə, detalları da silinməlidir (CASCADE)
            $table->dropForeign(['complaint_id']);
            $table->foreign('complaint_id')->references('id')->on('complaints')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Geri qaytarmaq lazım gələrsə, standart əlaqələri bərpa edirik
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropForeign(['bus_id']);
            $table->foreign('bus_id')->references('id')->on('buses');
        });

        Schema::table('daily_km_records', function (Blueprint $table) {
            $table->dropForeign(['bus_id']);
            $table->foreign('bus_id')->references('id')->on('buses');
        });

        Schema::table('bus_daily_statuses', function (Blueprint $table) {
            $table->dropForeign(['bus_id']);
            $table->foreign('bus_id')->references('id')->on('buses');
        });

        Schema::table('complaint_details', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->foreign('employee_id')->references('id')->on('employees');

            $table->dropForeign(['complaint_id']);
            $table->foreign('complaint_id')->references('id')->on('complaints');
        });
    }
};
