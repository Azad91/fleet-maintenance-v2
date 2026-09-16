<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Loosens the destination check constraint to allow
 * "return_to_quarantine" transfers, which have NO external
 * destination — the items move from an active warehouse row to a
 * quarantine row within the SAME garage.
 *
 * Old check (unchanged for the other two types):
 *   exactly one of (to_garage_id, to_service_vehicle_id) is set
 *
 * New check:
 *   - For type = 'return_to_quarantine':
 *       both destination columns must be NULL
 *   - For every other type:
 *       exactly one of the two must be set
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            ALTER TABLE warehouse_transfers
            DROP CONSTRAINT IF EXISTS warehouse_transfers_destination_check
        ');

        DB::statement("
            ALTER TABLE warehouse_transfers
            ADD CONSTRAINT warehouse_transfers_destination_check
            CHECK (
                (
                    type = 'return_to_quarantine'
                    AND to_garage_id IS NULL
                    AND to_service_vehicle_id IS NULL
                )
                OR
                (
                    type <> 'return_to_quarantine'
                    AND (
                        (to_garage_id IS NOT NULL AND to_service_vehicle_id IS NULL)
                        OR
                        (to_garage_id IS NULL AND to_service_vehicle_id IS NOT NULL)
                    )
                )
            )
        ");
    }

    public function down(): void
    {
        DB::statement('
            ALTER TABLE warehouse_transfers
            DROP CONSTRAINT IF EXISTS warehouse_transfers_destination_check
        ');

        DB::statement('
            ALTER TABLE warehouse_transfers
            ADD CONSTRAINT warehouse_transfers_destination_check
            CHECK (
                (to_garage_id IS NOT NULL AND to_service_vehicle_id IS NULL)
                OR
                (to_garage_id IS NULL AND to_service_vehicle_id IS NOT NULL)
            )
        ');
    }
};
