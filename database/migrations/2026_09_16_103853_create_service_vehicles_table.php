<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Service vehicles — mobile workshops that a garage dispatches to
 * repair buses on the road. Parts and consumables are transferred
 * from a garage warehouse to a service vehicle through the future
 * warehouse transfer module.
 *
 * Scoping:
 *   - garage_id is required (service vehicles belong to ONE garage)
 *   - company_id is denormalised for report queries
 *   - HasGarageScope filters every query by the current garage
 *
 * Plate number is optional. When present it must be unique within
 * a garage — but only among ACTIVE rows, so a retired vehicle
 * releases its plate for reuse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_vehicles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('garage_id')
                ->constrained('garages')
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('plate_number', 50)->nullable();
            $table->string('driver_name')->nullable();
            $table->string('phone', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['garage_id', 'is_active']);
        });

        // Partial unique index — plate numbers must be unique among
        // live rows only. Retired (soft-deleted) vehicles release
        // their plate for reuse, matching the pattern used for
        // buses.dqn and drivers.code.
        DB::statement('
            CREATE UNIQUE INDEX service_vehicles_garage_plate_unique
            ON service_vehicles (garage_id, plate_number)
            WHERE deleted_at IS NULL AND plate_number IS NOT NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS service_vehicles_garage_plate_unique');
        Schema::dropIfExists('service_vehicles');
    }
};
