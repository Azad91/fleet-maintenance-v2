<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Replaces the non-partial composite unique constraints created by
     * 2026_09_08_174319 with partial unique indexes that only apply to
     * live rows (deleted_at IS NULL).
     *
     * WHY
     * ---
     * 2026_08_30_140000 established the project-wide convention:
     * uniqueness applies only to non-deleted rows, so a soft-deleted
     * record releases its key for reuse. Every tenant-scoped table in
     * the codebase follows this pattern — companies.slug,
     * garages.code, users.email, users.employee_code, employees.code,
     * bus_brands, service_vehicles, complaint_types, bus_oil_changes.
     *
     * 2026_09_08_174319 then created non-partial composite unique
     * constraints on the SAME columns (buses.garage_id+dqn,
     * buses.garage_id+route_number, warehouses.garage_id+code,
     * drivers.garage_id+code). PostgreSQL keeps both indexes, but the
     * non-partial one is stricter — a soft-deleted row permanently
     * reserves its key, contradicting the reuse semantics that every
     * other table in the application already relies on.
     *
     * CONSEQUENCES BEFORE THIS FIX
     * ----------------------------
     *   - Soft-delete a bus, then re-import the same DQN → unique
     *     violation, even though the import logic intentionally
     *     restores the trashed row.
     *   - Soft-delete a warehouse item, then re-create it with the
     *     same code → unique violation.
     *   - Soft-delete a driver, then re-import the same driver code
     *     → unique violation.
     *
     * WHAT THIS MIGRATION DOES
     * ------------------------
     *   1. Drops the non-partial composite unique constraints.
     *   2. Creates the canonical partial unique indexes with the
     *      "active_unique" suffix that matches the project convention.
     *   3. Removes three stale indexes from 2026_08_30_140000 whose
     *      names still reference the pre-rename column names
     *      (xett_no → route_number, kod → code, kodu → code). They
     *      are functionally redundant after step 2.
     *
     * IDEMPOTENT
     * ----------
     * Every statement is DROP IF EXISTS / CREATE IF NOT EXISTS, so the
     * migration can be re-run on a partially-migrated database.
     */
    public function up(): void
    {
        // ─────────────────────────────────────────────────────────
        // 1. Drop the non-partial composite unique constraints.
        // ─────────────────────────────────────────────────────────
        DB::statement('ALTER TABLE buses DROP CONSTRAINT IF EXISTS buses_garage_dqn_unique');
        DB::statement('ALTER TABLE buses DROP CONSTRAINT IF EXISTS buses_garage_route_number_unique');
        DB::statement('ALTER TABLE warehouses DROP CONSTRAINT IF EXISTS warehouses_garage_code_unique');
        DB::statement('ALTER TABLE drivers DROP CONSTRAINT IF EXISTS drivers_garage_code_unique');

        // ─────────────────────────────────────────────────────────
        // 2. Drop the old partial indexes (whose names reference
        //    pre-rename columns) so we can recreate everything with
        //    a single canonical naming convention.
        // ─────────────────────────────────────────────────────────
        DB::statement('DROP INDEX IF EXISTS buses_garage_dqn_active_unique');
        DB::statement('DROP INDEX IF EXISTS buses_garage_xett_active_unique');
        DB::statement('DROP INDEX IF EXISTS warehouses_garage_kod_active_unique');
        DB::statement('DROP INDEX IF EXISTS drivers_garage_kodu_active_unique');

        // ─────────────────────────────────────────────────────────
        // 3. Create the canonical partial unique indexes.
        // ─────────────────────────────────────────────────────────
        DB::statement('
            CREATE UNIQUE INDEX buses_garage_dqn_active_unique
            ON buses (garage_id, dqn)
            WHERE deleted_at IS NULL
        ');

        // route_number is nullable — partial index must exclude NULLs
        // explicitly so multiple buses without a route can coexist.
        DB::statement('
            CREATE UNIQUE INDEX buses_garage_route_number_active_unique
            ON buses (garage_id, route_number)
            WHERE deleted_at IS NULL AND route_number IS NOT NULL
        ');

        DB::statement('
            CREATE UNIQUE INDEX warehouses_garage_code_active_unique
            ON warehouses (garage_id, code)
            WHERE deleted_at IS NULL
        ');

        DB::statement('
            CREATE UNIQUE INDEX drivers_garage_code_active_unique
            ON drivers (garage_id, code)
            WHERE deleted_at IS NULL
        ');
    }

    public function down(): void
    {
        // Drop the partial indexes created in up().
        DB::statement('DROP INDEX IF EXISTS buses_garage_dqn_active_unique');
        DB::statement('DROP INDEX IF EXISTS buses_garage_route_number_active_unique');
        DB::statement('DROP INDEX IF EXISTS warehouses_garage_code_active_unique');
        DB::statement('DROP INDEX IF EXISTS drivers_garage_code_active_unique');

        // Restore the non-partial constraints (matches the historical
        // state before this migration, not the desired state).
        DB::statement('ALTER TABLE buses ADD CONSTRAINT buses_garage_dqn_unique UNIQUE (garage_id, dqn)');
        DB::statement('ALTER TABLE buses ADD CONSTRAINT buses_garage_route_number_unique UNIQUE (garage_id, route_number)');
        DB::statement('ALTER TABLE warehouses ADD CONSTRAINT warehouses_garage_code_unique UNIQUE (garage_id, code)');
        DB::statement('ALTER TABLE drivers ADD CONSTRAINT drivers_garage_code_unique UNIQUE (garage_id, code)');
    }
};
