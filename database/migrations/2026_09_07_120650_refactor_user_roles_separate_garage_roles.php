<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================================
        // 1. USERS.ROLE CONSTRAINT
        // ============================================================

        // Köhnə constraint-i sil
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');

        // BÜTÜN uyğunsuz rolları 'user' et
        DB::table('users')
            ->where('role', '!=', 'super_admin')
            ->update(['role' => 'user']);

        DB::table('users')
            ->whereNull('role')
            ->update(['role' => 'user']);

        // Yeni constraint əlavə et
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('super_admin', 'user'))");

        // ============================================================
        // 2. GARAGE_USER.ROLE CONSTRAINT
        // ============================================================

        // Köhnə constraint-i sil
        DB::statement("ALTER TABLE garage_user DROP CONSTRAINT IF EXISTS garage_user_role_check");

        // Uyğunsuz rolları 'viewer' et (operator kimi köhnə dəyərlər varsa)
        DB::statement("
            UPDATE garage_user
            SET role = 'viewer'
            WHERE role NOT IN ('admin', 'manager', 'complaint', 'warehouse', 'daily_km', 'daily_status', 'directorate', 'viewer')
        ");

        // Yeni constraint əlavə et
        DB::statement("
            ALTER TABLE garage_user ADD CONSTRAINT garage_user_role_check
            CHECK (role IN ('admin', 'manager', 'complaint', 'warehouse', 'daily_km', 'daily_status', 'directorate', 'viewer'))
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("
            ALTER TABLE users ADD CONSTRAINT users_role_check
            CHECK (role IN ('super_admin', 'admin', 'user', 'bus', 'complaint', 'warehouse', 'daily_km', 'daily_status', 'directorate'))
        ");

        DB::statement('ALTER TABLE garage_user DROP CONSTRAINT IF EXISTS garage_user_role_check');
        DB::statement("
            ALTER TABLE garage_user ADD CONSTRAINT garage_user_role_check
            CHECK (role IN ('admin', 'manager', 'operator', 'viewer'))
        ");
    }
};
