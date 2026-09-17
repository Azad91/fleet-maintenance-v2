<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Records where a complaint detail's stock came from.
 *
 * Two sources are possible:
 *   - warehouse        → the source garage's own warehouse (default)
 *   - service_vehicle  → stock held on any service vehicle belonging
 *                        to the source garage
 *
 * Existing rows are treated as warehouse-sourced because the
 * service-vehicle flow is being introduced for the first time in
 * this migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaint_details', function (Blueprint $table) {
            $table->string('source_type', 20)
                ->default('warehouse')
                ->after('used_quantity');
        });

        // CHECK constraint ensures only known source types can be
        // stored — protects against typos and future refactors that
        // forget to update the enum.
        DB::statement("
            ALTER TABLE complaint_details
            ADD CONSTRAINT complaint_details_source_type_check
            CHECK (source_type IN ('warehouse', 'service_vehicle'))
        ");

        // Query pattern: "how much did we use from service vehicles?"
        DB::statement('
            CREATE INDEX complaint_details_source_type_idx
            ON complaint_details (garage_id, source_type)
            WHERE deleted_at IS NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS complaint_details_source_type_idx');
        DB::statement('ALTER TABLE complaint_details DROP CONSTRAINT IF EXISTS complaint_details_source_type_check');

        Schema::table('complaint_details', function (Blueprint $table) {
            $table->dropColumn('source_type');
        });
    }
};
