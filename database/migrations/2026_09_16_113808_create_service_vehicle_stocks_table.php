<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stock held on a service vehicle.
 *
 * Each service vehicle carries its own stock, separate from the
 * garage warehouse. The two are linked by `code` so a "FILTER-001"
 * on a service vehicle is the same physical item as a
 * "FILTER-001" in the source garage warehouse.
 *
 * Why a separate table instead of adding service_vehicle_id to
 * warehouses:
 *   - warehouses.code has a partial unique index on
 *     (garage_id, code). Adding service vehicle rows would break it.
 *   - The two concepts have different lifecycles and will likely
 *     diverge in the future (e.g. usage logs per complaint).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_vehicle_stocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_vehicle_id')
                ->constrained('service_vehicles')
                ->cascadeOnDelete();

            // Denormalised for cross-garage queries in reports.
            $table->foreignId('garage_id')
                ->constrained('garages')
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->string('code', 100);
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit', 50)->nullable();
            $table->integer('quantity')->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();

            // One row per (service vehicle, code). A service vehicle
            // cannot have two rows with the same code — transfers
            // accumulate into the existing row.
            $table->unique(
                ['service_vehicle_id', 'code'],
                'service_vehicle_stocks_vehicle_code_unique'
            );

            $table->index(['garage_id', 'service_vehicle_id']);
        });

        DB::statement('
            ALTER TABLE service_vehicle_stocks
            ADD CONSTRAINT service_vehicle_stocks_quantity_check
            CHECK (quantity >= 0)
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('service_vehicle_stocks');
    }
};
