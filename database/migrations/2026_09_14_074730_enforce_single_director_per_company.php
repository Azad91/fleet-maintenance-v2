<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Enforces the "one active director per company" rule at the database level.
 *
 * Mirrors the pattern already used by:
 *   - users_single_super_admin          (single active SuperAdmin)
 *   - garage_user_single_admin          (single active admin per garage)
 *
 * A partial unique index is used instead of a CHECK constraint because
 * PostgreSQL cannot express "at most one row per group" as a CHECK.
 * The `WHERE is_active = true` predicate means:
 *   - Deactivated (historical) directors do NOT block a new assignment
 *   - The audit trail is preserved indefinitely
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS company_user_single_director');

        DB::statement("
            CREATE UNIQUE INDEX company_user_single_director
            ON company_user (company_id)
            WHERE role = 'director'
              AND is_active = true
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS company_user_single_director');
    }
};
