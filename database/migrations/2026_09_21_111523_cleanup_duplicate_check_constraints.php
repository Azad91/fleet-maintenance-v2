<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Removes duplicate CHECK constraints created by two separate
 * migrations that both enforced "quantity >= 0" / "price >= 0" /
 * "km >= 0" on the same tables.
 *
 * History:
 *   - 2026_09_07_082252_add_check_constraints_to_warehouses_and_buses.php
 *     created: check_quantity_non_negative, check_price_non_negative,
 *              check_km_non_negative
 *   - 2026_09_08_131127_add_check_constraints_to_database.php
 *     created: chk_warehouses_quantity, chk_warehouses_price,
 *              chk_buses_km, chk_daily_kms_km
 *
 * The second migration added the same logical rules under new names.
 * This migration drops the older set (check_*) and keeps the newer
 * (chk_*) — they are functionally identical, but the chk_* names
 * follow the project's naming convention going forward.
 *
 * This is a non-destructive change: the business rule
 * "quantity >= 0" is still enforced after this migration runs.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Warehouses — keep chk_warehouses_quantity, chk_warehouses_price
        DB::statement('ALTER TABLE warehouses DROP CONSTRAINT IF EXISTS check_quantity_non_negative');
        DB::statement('ALTER TABLE warehouses DROP CONSTRAINT IF EXISTS check_price_non_negative');

        // Buses — keep chk_buses_km
        DB::statement('ALTER TABLE buses DROP CONSTRAINT IF EXISTS check_km_non_negative');
    }

    public function down(): void
    {
        // Restore the dropped constraints so `migrate:rollback` leaves
        // the DB in the same shape it had before this migration ran.
        DB::statement('ALTER TABLE warehouses ADD CONSTRAINT check_quantity_non_negative CHECK (quantity >= 0)');
        DB::statement('ALTER TABLE warehouses ADD CONSTRAINT check_price_non_negative CHECK (price >= 0)');
        DB::statement('ALTER TABLE buses ADD CONSTRAINT check_km_non_negative CHECK (km >= 0)');
    }
};
