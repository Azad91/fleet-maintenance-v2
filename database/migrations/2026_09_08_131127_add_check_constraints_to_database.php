<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Anbar (Warehouses) üçün: Miqdar və qiymət mənfi ola bilməz
        DB::statement('ALTER TABLE warehouses ADD CONSTRAINT chk_warehouses_quantity CHECK (quantity >= 0)');
        DB::statement('ALTER TABLE warehouses ADD CONSTRAINT chk_warehouses_price CHECK (price >= 0)');

        // 2. Avtobuslar (Buses) üçün: KM mənfi ola bilməz
        DB::statement('ALTER TABLE buses ADD CONSTRAINT chk_buses_km CHECK (km >= 0)');

        // 3. Günlük KM qeydləri (Daily KM Records) üçün: KM mənfi ola bilməz
        DB::statement('ALTER TABLE daily_km_records ADD CONSTRAINT chk_daily_kms_km CHECK (km >= 0)');

        // 4. Şikayət detalları (Complaint Details) üçün: İstifadə olunan miqdar mütləq 0-dan böyük olmalıdır
        DB::statement('ALTER TABLE complaint_details ADD CONSTRAINT chk_complaint_details_used_qty CHECK (used_quantity > 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE warehouses DROP CONSTRAINT IF EXISTS chk_warehouses_quantity');
        DB::statement('ALTER TABLE warehouses DROP CONSTRAINT IF EXISTS chk_warehouses_price');
        DB::statement('ALTER TABLE buses DROP CONSTRAINT IF EXISTS chk_buses_km');
        DB::statement('ALTER TABLE daily_km_records DROP CONSTRAINT IF EXISTS chk_daily_kms_km');
        DB::statement('ALTER TABLE complaint_details DROP CONSTRAINT IF EXISTS chk_complaint_details_used_qty');
    }
};
