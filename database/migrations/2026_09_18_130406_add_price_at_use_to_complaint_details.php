<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshots the unit price of a part at the moment it was consumed.
 *
 * WHY THIS EXISTS
 * ---------------
 * The warehouse catalog's `price` column is mutable — an operator can
 * change it at any time. Without a snapshot, historical reports would
 * silently change: "last month we spent 5 000 AZN" could become
 * "8 000 AZN" after a single price update.
 *
 * This migration:
 *   1. Adds a nullable `price_at_use` column to complaint_details.
 *   2. Backfills existing rows with the CURRENT warehouse price
 *      (best-effort approximation — the true historical price is lost).
 *
 * GOING FORWARD
 * -------------
 * Every deduction writes the price snapshot:
 *   - source_type='warehouse'        → warehouse.price
 *   - source_type='service_vehicle'  → warehouse.price (matched by code)
 *   - source_type='inspection'       → 0.00
 *   - source_type='historical'       → warehouse.price (best effort)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaint_details', function (Blueprint $table) {
            $table->decimal('price_at_use', 10, 2)
                ->nullable()
                ->after('used_quantity');
        });

        // Backfill: use the current warehouse price as a best-effort
        // snapshot for legacy rows. Rows with no matching warehouse
        // stay NULL and fall back to NULL in reports.
        DB::statement('
            UPDATE complaint_details cd
            SET price_at_use = w.price
            FROM warehouses w
            WHERE cd.code = w.code
              AND cd.garage_id = w.garage_id
              AND cd.price_at_use IS NULL
              AND w.deleted_at IS NULL
              AND w.price IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('complaint_details', function (Blueprint $table) {
            $table->dropColumn('price_at_use');
        });
    }
};
