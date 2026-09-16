<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add composite indexes tuned for the two hottest read patterns
 * on the daily tables:
 *
 *   1. List pages:
 *        WHERE garage_id = ? AND date = ?
 *
 *   2. Report aggregations:
 *        WHERE garage_id = ? AND date BETWEEN ? AND ?
 *
 *   3. Monthly status summary (Bus show page):
 *        WHERE bus_id = ? AND date BETWEEN ? AND ?
 *        GROUP BY status
 *
 * The existing (bus_id, date) index only covers the third pattern
 * for a single bus. The first two patterns currently trigger a
 * sequential scan on the whole table — invisible at 1 000 rows,
 * noticeable at 100 000, painful at 500 000.
 *
 * All indexes are partial (WHERE deleted_at IS NULL) because every
 * application query filters out soft-deleted rows. A partial index
 * is roughly half the size of a full one and only pays the cost of
 * maintaining entries for live rows.
 *
 * These additions are safe to apply on a live database. On a table
 * of ~200 000 rows each index takes under a second to build. If the
 * tables grow beyond a few million rows in the future, switch to
 * CREATE INDEX CONCURRENTLY to avoid blocking writes during the
 * build.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── daily_km_records ───
        DB::statement('
            CREATE INDEX IF NOT EXISTS daily_km_records_garage_date_idx
            ON daily_km_records (garage_id, date)
            WHERE deleted_at IS NULL
        ');

        // ─── bus_daily_statuses ───
        DB::statement('
            CREATE INDEX IF NOT EXISTS bus_daily_statuses_garage_date_idx
            ON bus_daily_statuses (garage_id, date)
            WHERE deleted_at IS NULL
        ');

        DB::statement('
            CREATE INDEX IF NOT EXISTS bus_daily_statuses_garage_status_idx
            ON bus_daily_statuses (garage_id, status)
            WHERE deleted_at IS NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS daily_km_records_garage_date_idx');
        DB::statement('DROP INDEX IF EXISTS bus_daily_statuses_garage_date_idx');
        DB::statement('DROP INDEX IF EXISTS bus_daily_statuses_garage_status_idx');
    }
};
