<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // warehouses cədvəli üçün CHECK
        DB::statement('ALTER TABLE warehouses ADD CONSTRAINT check_quantity_non_negative CHECK (quantity >= 0)');
        DB::statement('ALTER TABLE warehouses ADD CONSTRAINT check_price_non_negative CHECK (price >= 0)');

        // buses cədvəli üçün CHECK
        DB::statement('ALTER TABLE buses ADD CONSTRAINT check_km_non_negative CHECK (km >= 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE warehouses DROP CONSTRAINT check_quantity_non_negative');
        DB::statement('ALTER TABLE warehouses DROP CONSTRAINT check_price_non_negative');
        DB::statement('ALTER TABLE buses DROP CONSTRAINT check_km_non_negative');
    }
};
