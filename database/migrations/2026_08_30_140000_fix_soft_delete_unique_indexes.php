<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE buses DROP CONSTRAINT IF EXISTS buses_dqn_unique');
        DB::statement('ALTER TABLE buses DROP CONSTRAINT IF EXISTS buses_xett_no_unique');
        DB::statement('ALTER TABLE warehouses DROP CONSTRAINT IF EXISTS warehouses_kod_unique');
        DB::statement('ALTER TABLE drivers DROP CONSTRAINT IF EXISTS drivers_kodu_unique');
        DB::statement('ALTER TABLE daily_km_records DROP CONSTRAINT IF EXISTS daily_km_records_bus_id_tarix_unique');
        DB::statement('ALTER TABLE bus_daily_statuses DROP CONSTRAINT IF EXISTS bus_daily_statuses_bus_id_tarix_unique');

        DB::statement('CREATE UNIQUE INDEX buses_garage_dqn_active_unique ON buses (garage_id, dqn) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX buses_garage_xett_active_unique ON buses (garage_id, xett_no) WHERE deleted_at IS NULL AND xett_no IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX warehouses_garage_kod_active_unique ON warehouses (garage_id, kod) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX drivers_garage_kodu_active_unique ON drivers (garage_id, kodu) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX daily_km_records_active_unique ON daily_km_records (bus_id, tarix) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX bus_daily_statuses_active_unique ON bus_daily_statuses (bus_id, tarix) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        foreach (['buses_garage_dqn_active_unique', 'buses_garage_xett_active_unique', 'warehouses_garage_kod_active_unique', 'drivers_garage_kodu_active_unique', 'daily_km_records_active_unique', 'bus_daily_statuses_active_unique'] as $index) {
            DB::statement("DROP INDEX IF EXISTS {$index}");
        }
    }
};
