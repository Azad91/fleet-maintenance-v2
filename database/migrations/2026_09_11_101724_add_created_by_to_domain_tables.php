<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==================== DOMAIN TABLES (Worker ownership) ====================
        $domainTables = ['warehouses', 'daily_km_records', 'bus_daily_statuses'];

        foreach ($domainTables as $tableName) {
            if (! Schema::hasColumn($tableName, 'created_by')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('created_by')
                        ->nullable()
                        ->after('company_id')
                        ->constrained('users')
                        ->nullOnDelete();

                    $table->index(['created_by', 'garage_id']);
                });
            }
        }

        // ==================== AUDIT TABLES (Super Admin trace) ====================
        $auditTables = ['companies', 'garages'];

        foreach ($auditTables as $tableName) {
            if (! Schema::hasColumn($tableName, 'created_by')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('created_by')
                        ->nullable()
                        ->after('is_active')
                        ->constrained('users')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        $domainTables = ['warehouses', 'daily_km_records', 'bus_daily_statuses'];
        foreach ($domainTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['created_by', 'garage_id']);
                $table->dropConstrainedForeignId('created_by');
            });
        }

        $auditTables = ['companies', 'garages'];
        foreach ($auditTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('created_by');
            });
        }
    }
};
