<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Purge the orphan global catalog data.
     *
     * Both motor_oil_details and service_templates were previously
     * global. After the garage_id columns were added in the two
     * prior migrations, every existing row remains with garage_id =
     * NULL — a "ghost catalog" that would be visible to no garage
     * but would still occupy the database.
     *
     * The decision (confirmed by the project owner) is to delete all
     * of this legacy data. Each garage will re-import its own motor
     * oil Excel file through the standard UI.
     *
     * Cascade behavior on service_templates deletion:
     *   - bus_service_intervals    → CASCADE  (rows deleted)
     *   - bus_service_history      → CASCADE  (rows deleted)
     *   - complaints.service_template_id → SET NULL
     *
     * Those dependent rows are also legacy data tied to the global
     * templates, so cascade deletion is the correct behavior here.
     * This migration will not be reversible.
     */
    public function up(): void
    {
        // Null out the FK on complaints first so the SET NULL behavior
        // is explicit in the migration history (rather than relying on
        // the DB-level ON DELETE clause being present in every env).
        DB::table('complaints')
            ->whereNotNull('service_template_id')
            ->update(['service_template_id' => null]);

        // Delete the child tables explicitly so this migration is
        // independent of whether the ON DELETE CASCADE clause exists.
        DB::table('bus_service_intervals')->delete();
        DB::table('bus_service_history')->delete();

        // Delete the parent tables.
        DB::table('service_templates')->delete();
        DB::table('motor_oil_details')->delete();
    }

    public function down(): void
    {
        // Intentional no-op.
        //
        // The data deleted by this migration cannot be reconstructed.
        // Rolling back is possible only in the sense of restoring the
        // schema, which the two prior migrations handle on their own.
    }
};
