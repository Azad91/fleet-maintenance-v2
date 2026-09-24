<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Allows complaint_details rows with used_quantity = 0.
 *
 * Business rationale:
 *   A detail row can represent two different things:
 *     1. A part that was CONSUMED (used_quantity > 0) → affects stock
 *     2. A part that was INSPECTED / REPAIRED but not replaced
 *        (used_quantity = 0) → no stock change, but the work must
 *        still be recorded so future operators can see that the
 *        part was looked at.
 *
 * The old CHECK (used_quantity > 0) made it impossible to store
 * case #2. This migration relaxes the constraint to >= 0 and adds
 * an 'inspection' source_type so reports can distinguish the two.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Relax the quantity constraint.
        DB::statement('ALTER TABLE complaint_details DROP CONSTRAINT IF EXISTS chk_complaint_details_used_qty');
        DB::statement('ALTER TABLE complaint_details ADD CONSTRAINT chk_complaint_details_used_qty CHECK (used_quantity >= 0)');

        // Extend the source_type CHECK to allow 'inspection'.
        DB::statement('ALTER TABLE complaint_details DROP CONSTRAINT IF EXISTS complaint_details_source_type_check');
        DB::statement("
            ALTER TABLE complaint_details
            ADD CONSTRAINT complaint_details_source_type_check
            CHECK (source_type IN ('warehouse', 'service_vehicle', 'historical', 'inspection'))
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE complaint_details DROP CONSTRAINT IF EXISTS complaint_details_source_type_check');
        DB::statement("
            ALTER TABLE complaint_details
            ADD CONSTRAINT complaint_details_source_type_check
            CHECK (source_type IN ('warehouse', 'service_vehicle', 'historical'))
        ");

        // Roll back rows that would violate the old constraint.
        DB::statement('UPDATE complaint_details SET used_quantity = 1 WHERE used_quantity = 0');
        DB::statement("UPDATE complaint_details SET source_type = 'warehouse' WHERE source_type = 'inspection'");

        DB::statement('ALTER TABLE complaint_details DROP CONSTRAINT IF EXISTS chk_complaint_details_used_qty');
        DB::statement('ALTER TABLE complaint_details ADD CONSTRAINT chk_complaint_details_used_qty CHECK (used_quantity > 0)');
    }
};
