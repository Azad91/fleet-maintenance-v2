<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaultGarageId = 1; // İlk qarajın ID-si

        $tables = ['buses', 'complaints', 'employees', 'drivers', 'daily_km_records', 'bus_daily_statuses', 'warehouses'];

        foreach ($tables as $table) {
            DB::table($table)
                ->whereNull('garage_id')
                ->update(['garage_id' => $defaultGarageId]);
        }
    }

    public function down(): void
    {
        // Geri qaytarmağa ehtiyac yoxdur
    }
};
