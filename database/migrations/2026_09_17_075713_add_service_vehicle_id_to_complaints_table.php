<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a direct link between a road complaint and the specific service
 * vehicle that performed the work on the road.
 *
 * WHY THIS EXISTS
 * ---------------
 * Before this migration, a road complaint only stored `driver_id` (the
 * bus driver) and `yer = 'road'`. The stock deduction logic then picked
 * *any* service vehicle of the garage, draining the one with the largest
 * stock — a nondeterministic behaviour that caused wrong vehicles to be
 * debited and made stock reconciliation impossible.
 *
 * After this migration:
 *   - yer = 'garage' → service_vehicle_id is NULL (stock comes from warehouse)
 *   - yer = 'road'   → service_vehicle_id is required (stock comes from that vehicle)
 *
 * Nullable because garage complaints have no vehicle, and because
 * historical road complaints cannot be backfilled automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->foreignId('service_vehicle_id')
                ->nullable()
                ->after('driver_id')
                ->constrained('service_vehicles')
                ->nullOnDelete();

            // Composite index: reports frequently filter road complaints
            // by vehicle within a period.
            $table->index(
                ['service_vehicle_id', 'created_at'],
                'complaints_service_vehicle_created_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropIndex('complaints_service_vehicle_created_idx');
            $table->dropConstrainedForeignId('service_vehicle_id');
        });
    }
};
