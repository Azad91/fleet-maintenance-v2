<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_transfer_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transfer_id')
                ->constrained('warehouse_transfers')
                ->cascadeOnDelete();

            // The source warehouse row being transferred. Quantity is
            // decremented from this row when the transfer is dispatched.
            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->restrictOnDelete();

            // What the source garage says it is sending.
            $table->integer('declared_quantity');

            // What the destination garage actually counted. Null until
            // the transfer is received or rejected.
            $table->integer('received_quantity')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('transfer_id');
        });

        // Quantities must be positive. received_quantity stays null
        // until receipt but must be >= 0 when set.
        DB::statement('
            ALTER TABLE warehouse_transfer_items
            ADD CONSTRAINT warehouse_transfer_items_declared_check
            CHECK (declared_quantity > 0)
        ');

        DB::statement('
            ALTER TABLE warehouse_transfer_items
            ADD CONSTRAINT warehouse_transfer_items_received_check
            CHECK (received_quantity IS NULL OR received_quantity >= 0)
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfer_items');
    }
};
