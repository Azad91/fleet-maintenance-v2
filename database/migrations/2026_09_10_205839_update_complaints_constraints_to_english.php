<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================================
        // 1. COMPLAINT_TYPE CONSTRAINT
        // ============================================================
        DB::statement('ALTER TABLE complaints DROP CONSTRAINT IF EXISTS complaints_sikayet_tipi_check');

        // Convert existing data (if any) to English
        DB::statement("UPDATE complaints SET complaint_type = 'accident' WHERE complaint_type = 'qezali'");
        DB::statement("UPDATE complaints SET complaint_type = 'breakdown' WHERE complaint_type = 'nasazliq'");
        DB::statement("UPDATE complaints SET complaint_type = 'maintenance' WHERE complaint_type = 'texniki_xidmet'");

        DB::statement("
            ALTER TABLE complaints ADD CONSTRAINT complaints_complaint_type_check
            CHECK (complaint_type IN ('accident', 'breakdown', 'maintenance'))
        ");

        // ============================================================
        // 2. STATUS CONSTRAINT
        // ============================================================
        DB::statement('ALTER TABLE complaints DROP CONSTRAINT IF EXISTS complaints_status_check');

        // Convert existing data
        DB::statement("UPDATE complaints SET status = 'pending' WHERE status = 'gözləmədə'");
        DB::statement("UPDATE complaints SET status = 'in_progress' WHERE status = 'işdə'");
        DB::statement("UPDATE complaints SET status = 'completed' WHERE status = 'həll olundu'");

        DB::statement("
            ALTER TABLE complaints ADD CONSTRAINT complaints_status_check
            CHECK (status IN ('pending', 'in_progress', 'completed', 'cancelled'))
        ");
        DB::statement("ALTER TABLE complaints ALTER COLUMN status SET DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE complaints DROP CONSTRAINT IF EXISTS complaints_complaint_type_check');
        DB::statement('ALTER TABLE complaints DROP CONSTRAINT IF EXISTS complaints_status_check');

        DB::statement("UPDATE complaints SET complaint_type = 'qezali' WHERE complaint_type = 'accident'");
        DB::statement("UPDATE complaints SET complaint_type = 'nasazliq' WHERE complaint_type = 'breakdown'");
        DB::statement("UPDATE complaints SET complaint_type = 'texniki_xidmet' WHERE complaint_type = 'maintenance'");

        DB::statement("UPDATE complaints SET status = 'gözləmədə' WHERE status = 'pending'");
        DB::statement("UPDATE complaints SET status = 'işdə' WHERE status = 'in_progress'");
        DB::statement("UPDATE complaints SET status = 'həll olundu' WHERE status = 'completed'");

        DB::statement("
            ALTER TABLE complaints ADD CONSTRAINT complaints_sikayet_tipi_check
            CHECK (complaint_type IN ('qezali', 'texniki_xidmet', 'nasazliq'))
        ");

        DB::statement("
            ALTER TABLE complaints ADD CONSTRAINT complaints_status_check
            CHECK (status IN ('gözləmədə', 'işdə', 'həll olundu'))
        ");
        DB::statement("ALTER TABLE complaints ALTER COLUMN status SET DEFAULT 'gözləmədə'");
    }
};