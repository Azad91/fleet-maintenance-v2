<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a garage-scoped employee code.
     *
     * Employees become identifiable by a human-readable code (like
     * drivers) instead of only by an auto-increment id. This lets the
     * complaint form accept a code + auto-fill the name, matching the
     * existing driver workflow.
     *
     * Steps:
     *   1. Add nullable `code` column (existing rows have no code).
     *   2. Backfill existing rows with a deterministic `EMP-####`
     *      derived from the primary key so no data is lost.
     *   3. Add a partial unique index per garage so soft-deleted rows
     *      don't block reuse of a previously used code.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('code', 100)->nullable()->after('id');
        });

        // Backfill existing employees with a deterministic code.
        DB::table('employees')
            ->whereNull('code')
            ->orderBy('id')
            ->each(function (object $employee) {
                DB::table('employees')
                    ->where('id', $employee->id)
                    ->update([
                        'code' => 'EMP-'.str_pad((string) $employee->id, 4, '0', STR_PAD_LEFT),
                    ]);
            });

        // Partial unique index — only active rows, only non-null codes.
        DB::statement('
            CREATE UNIQUE INDEX employees_garage_code_unique
            ON employees (garage_id, code)
            WHERE deleted_at IS NULL AND code IS NOT NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS employees_garage_code_unique');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
