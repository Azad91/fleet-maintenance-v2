<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==================== BUSES ====================
        Schema::table('buses', function (Blueprint $table) {
            // ✅ Təhlükəsiz sil – əgər varsa
            $this->dropConstraintIfExists('buses', 'buses_dqn_unique');
            $this->dropConstraintIfExists('buses', 'buses_route_number_unique');

            $table->unique(['garage_id', 'dqn'], 'buses_garage_dqn_unique');
            $table->unique(['garage_id', 'route_number'], 'buses_garage_route_number_unique');
        });

        // ==================== WAREHOUSES ====================
        Schema::table('warehouses', function (Blueprint $table) {
            $this->dropConstraintIfExists('warehouses', 'warehouses_code_unique');
            $table->unique(['garage_id', 'code'], 'warehouses_garage_code_unique');
        });

        // ==================== DRIVERS ====================
        Schema::table('drivers', function (Blueprint $table) {
            $this->dropConstraintIfExists('drivers', 'drivers_code_unique');
            $table->unique(['garage_id', 'code'], 'drivers_garage_code_unique');
        });
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

    /**
     * PostgreSQL-də constraint-i təhlükəsiz silir – əgər varsa
     */
    private function dropConstraintIfExists(string $table, string $constraintName): void
    {
        $exists = DB::select("
            SELECT 1
            FROM information_schema.table_constraints
            WHERE table_name = ?
              AND constraint_name = ?
              AND constraint_type = 'UNIQUE'
        ", [$table, $constraintName]);

        if (! empty($exists)) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$constraintName}");
        }
    }
};