<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add garage/company scoping to complaint_items.
     *
     * Previously this table had no tenant columns, so ComplaintItem
     * queries could not be automatically filtered by the current
     * garage — the only protection was a manual join to `complaints`
     * in the `recurring` scope. Every future query had to remember
     * that join, which is a leak waiting to happen.
     *
     * After this migration:
     *   - Every complaint_items row carries its own garage_id.
     *   - The HasGarageScope global scope filters it automatically.
     *   - New rows are auto-populated on create.
     *   - Existing rows are backfilled from their parent complaint.
     *
     * Nullable by design: matches complaint_details (see migration
     * 2026_09_08_122839). The HasGarageScope creating event fills
     * the value at runtime; the DB column stays nullable so console
     * seeding / historical imports do not break.
     */
    public function up(): void
    {
        Schema::table('complaint_items', function (Blueprint $table) {
            if (! Schema::hasColumn('complaint_items', 'garage_id')) {
                $table->foreignId('garage_id')
                    ->nullable()
                    ->after('complaint_id')
                    ->constrained('garages')
                    ->cascadeOnDelete();
            }

            if (! Schema::hasColumn('complaint_items', 'company_id')) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('garage_id')
                    ->constrained('companies')
                    ->cascadeOnDelete();
            }
        });

        // ── Backfill from the parent complaint ──
        // complaint_items.complaint_id → complaints.id
        // We only touch rows that are still NULL so the migration
        // is idempotent if re-run on a partially-migrated DB.
        DB::statement('
            UPDATE complaint_items
            SET garage_id = c.garage_id,
                company_id = c.company_id
            FROM complaints c
            WHERE complaint_items.complaint_id = c.id
              AND complaint_items.garage_id IS NULL
        ');

        // ── Composite index for the new global scope ──
        // HasGarageScope will always emit `WHERE garage_id = ?`.
        // Most complaint_items queries also join on complaint_id,
        // so a composite index covers both access patterns.
        DB::statement('
            CREATE INDEX IF NOT EXISTS complaint_items_garage_complaint_idx
            ON complaint_items (garage_id, complaint_id)
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS complaint_items_garage_complaint_idx');

        Schema::table('complaint_items', function (Blueprint $table) {
            $table->dropForeign(['garage_id']);
            $table->dropForeign(['company_id']);
            $table->dropColumn(['garage_id', 'company_id']);
        });
    }
};
