<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================================
        // 1. Köhnə CHECK constraint-ı sil
        // ============================================================
        DB::statement('ALTER TABLE garage_user DROP CONSTRAINT IF EXISTS garage_user_role_check');

        // ============================================================
        // 2. Sahə rollarını _manager variantına köçür
        //    (sahə tək rol idi, indi manager pilləsi)
        // ============================================================
        DB::statement("UPDATE garage_user SET role = 'complaint_manager' WHERE role = 'complaint'");
        DB::statement("UPDATE garage_user SET role = 'warehouse_manager'  WHERE role = 'warehouse'");
        DB::statement("UPDATE garage_user SET role = 'daily_km_manager'  WHERE role = 'daily_km'");
        DB::statement("UPDATE garage_user SET role = 'daily_status_manager' WHERE role = 'daily_status'");

        // ============================================================
        // 3. Ləğv olunmuş rolları təhlükəsiz vəziyyətə sal
        //    (complaint_worker + deaktiv — admin review edəcək)
        // ============================================================
        DB::statement("
            UPDATE garage_user
            SET role = 'complaint_worker', is_active = false
            WHERE role IN ('directorate', 'manager', 'viewer')
        ");

        // ============================================================
        // 4. Yeni CHECK constraint (yalnız 9 qaraj rolu)
        // ============================================================
        DB::statement("
            ALTER TABLE garage_user ADD CONSTRAINT garage_user_role_check
            CHECK (role IN (
                'admin',
                'complaint_manager', 'complaint_worker',
                'warehouse_manager', 'warehouse_worker',
                'daily_km_manager', 'daily_km_worker',
                'daily_status_manager', 'daily_status_worker'
            ))
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE garage_user DROP CONSTRAINT IF EXISTS garage_user_role_check');

        // Reverse data
        DB::statement("UPDATE garage_user SET role = 'complaint' WHERE role IN ('complaint_manager')");
        DB::statement("UPDATE garage_user SET role = 'warehouse' WHERE role IN ('warehouse_manager')");
        DB::statement("UPDATE garage_user SET role = 'daily_km' WHERE role IN ('daily_km_manager')");
        DB::statement("UPDATE garage_user SET role = 'daily_status' WHERE role IN ('daily_status_manager')");

        // Workers → lowest common role (viewer)
        DB::statement("
            UPDATE garage_user SET role = 'viewer'
            WHERE role IN (
                'complaint_worker', 'warehouse_worker',
                'daily_km_worker', 'daily_status_worker'
            )
        ");

        DB::statement("
            ALTER TABLE garage_user ADD CONSTRAINT garage_user_role_check
            CHECK (role IN (
                'admin', 'manager', 'complaint', 'warehouse',
                'daily_km', 'daily_status', 'directorate', 'viewer'
            ))
        ");
    }
};
