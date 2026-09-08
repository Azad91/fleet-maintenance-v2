<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buses', function (Blueprint $table) {
            $table->index(['garage_id', 'is_active']);
            $table->index('dqn');
            $table->index('route_number');
        });

        Schema::table('complaints', function (Blueprint $table) {
            $table->index(['garage_id', 'status']);
            $table->index('bus_id');
            $table->index('created_at');
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->index(['garage_id', 'code']);
        });

        Schema::table('daily_km_records', function (Blueprint $table) {
            $table->index(['bus_id', 'date']);
        });

        Schema::table('bus_daily_statuses', function (Blueprint $table) {
            $table->index(['bus_id', 'date']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->index(['garage_id', 'is_active']);
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->index(['garage_id', 'is_active']);
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::table('buses', function (Blueprint $table) {
            $table->dropIndex(['garage_id', 'is_active']);
            $table->dropIndex(['dqn']);
            $table->dropIndex(['route_number']);
        });

        Schema::table('complaints', function (Blueprint $table) {
            $table->dropIndex(['garage_id', 'status']);
            $table->dropIndex(['bus_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropIndex(['garage_id', 'code']);
        });

        Schema::table('daily_km_records', function (Blueprint $table) {
            $table->dropIndex(['bus_id', 'date']);
        });

        Schema::table('bus_daily_statuses', function (Blueprint $table) {
            $table->dropIndex(['bus_id', 'date']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['garage_id', 'is_active']);
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropIndex(['garage_id', 'is_active']);
            $table->dropIndex(['code']);
        });
    }
};
