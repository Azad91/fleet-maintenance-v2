<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the missing performance indexes on the `audit_logs` table.
 *
 * WHY
 * ---
 * The table is written to on every model create/update/delete and
 * is read by every report. The original migration only created a
 * single index on (auditable_type, auditable_id), which serves
 * morphMany lookups but not the two hottest read patterns:
 *
 *   1. Nightly archive command:
 *        WHERE created_at < ?
 *      Without an index this is a full sequential scan.
 *
 *   2. Every report page:
 *        WHERE auditable_type = ?
 *          AND garage_id IN (...)
 *          AND created_at BETWEEN ? AND ?
 *        GROUP BY user_id
 *      A composite index lets PostgreSQL narrow the scan to the
 *      relevant garage + time window before touching the heap.
 *
 *   3. Per-user audit queries (worker activity filters):
 *        WHERE user_id = ?
 *      Currently unindexed.
 *
 * NOTE
 * ----
 * The `garage_id`, `company_id` and `user_id` columns are declared
 * as foreignId()->constrained(), but PostgreSQL does NOT create an
 * implicit index on the referencing column of a foreign key — only
 * on the referenced side. So `WHERE garage_id = ?` has always been
 * a full scan.
 *
 * These indexes are safe to add on a live database. On a table of
 * ~500k rows each index builds in well under a second.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Composite index for the report queries:
            //   auditable_type + garage_id + date range.
            // This is the single biggest win — it covers every
            // report controller in App\Services\Reports\*.
            $table->index(
                ['auditable_type', 'garage_id', 'created_at'],
                'audit_logs_type_garage_created_idx'
            );

            // Nightly archive command:
            //   WHERE created_at < ?
            // Also used implicitly by any future retention or
            // cleanup job.
            $table->index('created_at', 'audit_logs_created_at_idx');

            // Per-user audit filter (worker activity reports,
            // scopeForUser()).
            $table->index('user_id', 'audit_logs_user_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_type_garage_created_idx');
            $table->dropIndex('audit_logs_created_at_idx');
            $table->dropIndex('audit_logs_user_id_idx');
        });
    }
};