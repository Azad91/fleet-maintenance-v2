<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_transfers', function (Blueprint $table) {
            $table->id();

            // Denormalised for report queries — always equals the
            // company_id of both garages (transfers never cross
            // companies).
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            // Source garage — always required.
            $table->foreignId('from_garage_id')
                ->constrained('garages')
                ->restrictOnDelete();

            // Destination — either a garage OR a service vehicle.
            // Both are nullable so each type can fill only what it needs.
            // A CHECK constraint below enforces "exactly one destination".
            $table->foreignId('to_garage_id')
                ->nullable()
                ->constrained('garages')
                ->restrictOnDelete();

            $table->foreignId('to_service_vehicle_id')
                ->nullable()
                ->constrained('service_vehicles')
                ->restrictOnDelete();

            $table->string('type', 30);
            $table->string('status', 20)->default('draft');

            $table->integer('declared_total')->default(0);
            $table->integer('received_total')->nullable();

            $table->text('notes')->nullable();
            $table->text('discrepancy_notes')->nullable();
            $table->string('resolution', 30)->nullable();

            // Lifecycle timestamps and actors.
            $table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispatched_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Query patterns:
            //   - "outbound transfers from my garage"     → (from_garage_id, status)
            //   - "inbound transfers to my garage"        → (to_garage_id, status)
            //   - "pending transfers across the company"  → (company_id, status)
            $table->index(['from_garage_id', 'status']);
            $table->index(['to_garage_id', 'status']);
            $table->index(['company_id', 'status']);
        });

        // Enforce: exactly one destination (either to_garage_id or
        // to_service_vehicle_id, never both, never neither).
        DB::statement('
            ALTER TABLE warehouse_transfers
            ADD CONSTRAINT warehouse_transfers_destination_check
            CHECK (
                (to_garage_id IS NOT NULL AND to_service_vehicle_id IS NULL)
                OR
                (to_garage_id IS NULL AND to_service_vehicle_id IS NOT NULL)
            )
        ');

        // Enforce: type and status values are within the enum set.
        DB::statement("
            ALTER TABLE warehouse_transfers
            ADD CONSTRAINT warehouse_transfers_type_check
            CHECK (type IN ('garage_to_garage', 'to_service_vehicle', 'return_to_quarantine'))
        ");

        DB::statement("
            ALTER TABLE warehouse_transfers
            ADD CONSTRAINT warehouse_transfers_status_check
            CHECK (status IN ('draft', 'dispatched', 'received', 'disputed', 'rejected', 'cancelled', 'resolved'))
        ");

        DB::statement("
            ALTER TABLE warehouse_transfers
            ADD CONSTRAINT warehouse_transfers_resolution_check
            CHECK (resolution IS NULL OR resolution IN ('retransfer', 'loss_accepted'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfers');
    }
};
