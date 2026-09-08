<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==================== BUSES ====================
        Schema::table('buses', function (Blueprint $table) {
            $table->dropUnique('buses_dqn_unique');
            $table->dropUnique('buses_route_number_unique');
            $table->unique(['garage_id', 'dqn'], 'buses_garage_dqn_unique');
            $table->unique(['garage_id', 'route_number'], 'buses_garage_route_number_unique');
        });

        // ==================== WAREHOUSES ====================
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropUnique('warehouses_code_unique');
            $table->unique(['garage_id', 'code'], 'warehouses_garage_code_unique');
        });

        // ==================== DRIVERS ====================
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropUnique('drivers_code_unique');
            $table->unique(['garage_id', 'code'], 'drivers_garage_code_unique');
        });

        // ==================== DAILY_KM_RECORDS ====================
        // Artıq (bus_id, date) unique var – dəyişməyə ehtiyac yoxdur

        // ==================== BUS_DAILY_STATUSES ====================
        // Artıq (bus_id, date) unique var – dəyişməyə ehtiyac yoxdur
    }

    public function down(): void
    {
        Schema::table('buses', function (Blueprint $table) {
            $table->dropUnique('buses_garage_dqn_unique');
            $table->dropUnique('buses_garage_route_number_unique');
            $table->unique('dqn');
            $table->unique('route_number');
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropUnique('warehouses_garage_code_unique');
            $table->unique('code');
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropUnique('drivers_garage_code_unique');
            $table->unique('code');
        });
    }
};