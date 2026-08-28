<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['buses', 'complaints', 'employees', 'drivers', 'daily_km_records', 'bus_daily_statuses'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'garage_id')) {
                    $table->foreignId('garage_id')->after('id')->nullable()->constrained('garages')->onDelete('cascade');
                }
                if (!Schema::hasColumn($tableName, 'company_id')) {
                    $table->foreignId('company_id')->after('garage_id')->nullable()->constrained('companies')->onDelete('cascade');
                }
            });
        }
    }

    public function down(): void
    {
        $tables = ['buses', 'complaints', 'employees', 'drivers', 'daily_km_records', 'bus_daily_statuses'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropForeign(['garage_id']);
                $table->dropForeign(['company_id']);
                $table->dropColumn(['garage_id', 'company_id']);
            });
        }
    }
};
