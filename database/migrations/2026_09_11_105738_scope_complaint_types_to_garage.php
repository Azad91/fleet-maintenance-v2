<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==================== 1. ADD COLUMNS ====================
        Schema::table('complaint_types', function (Blueprint $table) {
            if (! Schema::hasColumn('complaint_types', 'garage_id')) {
                $table->foreignId('garage_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('garages')
                    ->cascadeOnDelete();
            }

            if (! Schema::hasColumn('complaint_types', 'company_id')) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('garage_id')
                    ->constrained('companies')
                    ->cascadeOnDelete();
            }
        });

        // ==================== 2. DUPLICATE GLOBAL TYPES FOR EACH GARAGE ====================
        // Existing global types (garage_id IS NULL) become per-garage copies.
        // Complaints reference the type by NAME (string), so this is safe —
        // no FK to update, existing data continues to resolve.
        $globalTypes = DB::table('complaint_types')
            ->whereNull('garage_id')
            ->get();

        $garages = DB::table('garages')->get();

        if ($globalTypes->isNotEmpty() && $garages->isNotEmpty()) {
            $rows = [];

            foreach ($garages as $garage) {
                foreach ($globalTypes as $type) {
                    $rows[] = [
                        'name'       => $type->name,
                        'garage_id'  => $garage->id,
                        'company_id' => $garage->company_id,
                        'created_at' => $type->created_at ?? now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Chunk insert to avoid memory blowups on large datasets
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('complaint_types')->insert($chunk);
            }
        }

        // ==================== 3. REMOVE GLOBAL ROWS ====================
        DB::table('complaint_types')->whereNull('garage_id')->delete();

        // ==================== 4. UNIQUE CONSTRAINT (garage_id, name) ====================
        // No two complaint types with the same name in the same garage.
        DB::statement('
            CREATE UNIQUE INDEX complaint_types_garage_name_unique
            ON complaint_types (garage_id, name)
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS complaint_types_garage_name_unique');

        Schema::table('complaint_types', function (Blueprint $table) {
            $table->dropForeign(['garage_id']);
            $table->dropForeign(['company_id']);
            $table->dropColumn(['garage_id', 'company_id']);
        });
    }
};
