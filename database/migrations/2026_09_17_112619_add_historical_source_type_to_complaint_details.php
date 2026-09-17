<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Extends the complaint_details.source_type CHECK constraint to allow
 * 'historical' as a third source value.
 *
 * WHY 'historical' EXISTS
 * -----------------------
 * When importing older complaints (which happened in the past and whose
 * parts were already consumed long ago), we must NOT deduct stock — the
 * current stock level has no relationship to what was used back then.
 *
 * Historical details are:
 *   - Saved for reference (code, name, used_quantity, employee)
 *   - Marked with source_type = 'historical'
 *   - Ignored by stock restore logic (on delete/update)
 *
 * This gives operators a full audit trail without corrupting inventory.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE complaint_details DROP CONSTRAINT IF EXISTS complaint_details_source_type_check');

        DB::statement("
            ALTER TABLE complaint_details
            ADD CONSTRAINT complaint_details_source_type_check
            CHECK (source_type IN ('warehouse', 'service_vehicle', 'historical'))
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE complaint_details DROP CONSTRAINT IF EXISTS complaint_details_source_type_check');

        // Before restoring the old constraint, coerce any existing
        // 'historical' rows to 'warehouse' so the constraint can be
        // applied without violating data integrity.
        DB::statement("
            UPDATE complaint_details
            SET source_type = 'warehouse'
            WHERE source_type = 'historical'
        ");

        DB::statement("
            ALTER TABLE complaint_details
            ADD CONSTRAINT complaint_details_source_type_check
            CHECK (source_type IN ('warehouse', 'service_vehicle'))
        ");
    }
};
