<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Köhnə constraint-i sil
        DB::statement("ALTER TABLE garage_user DROP CONSTRAINT IF EXISTS garage_user_role_check");

        // Yeni constraint əlavə et (bütün rollarla)
        DB::statement("ALTER TABLE garage_user ADD CONSTRAINT garage_user_role_check CHECK (role IN ('admin', 'complaint', 'warehouse', 'daily_km', 'daily_status', 'directorate', 'manager', 'viewer'))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE garage_user DROP CONSTRAINT IF EXISTS garage_user_role_check");
        DB::statement("ALTER TABLE garage_user ADD CONSTRAINT garage_user_role_check CHECK (role IN ('admin', 'manager', 'operator', 'viewer'))");
    }
};
