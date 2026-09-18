<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Brand catalog — one row per bus manufacturer within a garage.
 *
 * Scoping rationale:
 *   - Each garage maintains its OWN brand list. Different garages may
 *     operate different fleets with different manufacturers.
 *   - Composite partial unique indexes (WHERE deleted_at IS NULL)
 *     let a soft-deleted brand release its code/name for reuse.
 *
 * Example rows (garage_id = 2):
 *   - BMC
 *   - Yutong
 *   - Iveco
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bus_brands', function (Blueprint $table) {
            $table->id();

            $table->foreignId('garage_id')
                ->constrained('garages')
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->string('name', 100);
            $table->string('code', 50);

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['garage_id', 'is_active']);
        });

        // Partial unique indexes — soft-deleted rows must not block reuse
        // of the same code or name within the same garage.
        DB::statement('
            CREATE UNIQUE INDEX bus_brands_garage_code_unique
            ON bus_brands (garage_id, code)
            WHERE deleted_at IS NULL
        ');

        DB::statement('
            CREATE UNIQUE INDEX bus_brands_garage_name_unique
            ON bus_brands (garage_id, name)
            WHERE deleted_at IS NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS bus_brands_garage_code_unique');
        DB::statement('DROP INDEX IF EXISTS bus_brands_garage_name_unique');

        Schema::dropIfExists('bus_brands');
    }
};
