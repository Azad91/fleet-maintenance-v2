<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================================
        // 1. COMPLAINTS.STATUS
        //    gözləmədə → pending
        //    işdə      → in_progress
        //    həll olundu → completed
        // ============================================================
        DB::statement('ALTER TABLE complaints DROP CONSTRAINT IF EXISTS complaints_status_check');
        DB::statement("ALTER TABLE complaints ALTER COLUMN status DROP DEFAULT");
        DB::statement("ALTER TABLE complaints ALTER COLUMN status TYPE varchar(50) USING status::varchar");

        DB::statement("UPDATE complaints SET status = 'pending' WHERE status = 'gözləmədə'");
        DB::statement("UPDATE complaints SET status = 'in_progress' WHERE status = 'işdə'");
        DB::statement("UPDATE complaints SET status = 'completed' WHERE status = 'həll olundu'");

        DB::statement("
            ALTER TABLE complaints ADD CONSTRAINT complaints_status_check
            CHECK (status IN ('pending', 'in_progress', 'completed'))
        ");
        DB::statement("ALTER TABLE complaints ALTER COLUMN status SET DEFAULT 'pending'");

        // ============================================================
        // 2. COMPLAINTS.YER
        //    yol   → road
        //    qaraj → garage
        // ============================================================
        DB::statement("UPDATE complaints SET yer = 'road' WHERE yer = 'yol'");
        DB::statement("UPDATE complaints SET yer = 'garage' WHERE yer = 'qaraj'");

        // ============================================================
        // 3. COMPLAINTS.COMPLAINT_TYPE
        //    qezali          → accident
        //    nasazliq        → breakdown
        //    texniki_xidmet  → maintenance
        // ============================================================
        DB::statement("UPDATE complaints SET complaint_type = 'accident' WHERE complaint_type = 'qezali'");
        DB::statement("UPDATE complaints SET complaint_type = 'breakdown' WHERE complaint_type = 'nasazliq'");
        DB::statement("UPDATE complaints SET complaint_type = 'maintenance' WHERE complaint_type = 'texniki_xidmet'");
    }

    public function down(): void
    {
        // Status geri
        DB::statement('ALTER TABLE complaints DROP CONSTRAINT IF EXISTS complaints_status_check');
        DB::statement("ALTER TABLE complaints ALTER COLUMN status DROP DEFAULT");
        DB::statement("ALTER TABLE complaints ALTER COLUMN status TYPE varchar(50) USING status::varchar");

        DB::statement("UPDATE complaints SET status = 'gözləmədə' WHERE status = 'pending'");
        DB::statement("UPDATE complaints SET status = 'işdə' WHERE status = 'in_progress'");
        DB::statement("UPDATE complaints SET status = 'həll olundu' WHERE status = 'completed'");

        DB::statement("
            ALTER TABLE complaints ADD CONSTRAINT complaints_status_check
            CHECK (status IN ('gözləmədə', 'işdə', 'həll olundu'))
        ");
        DB::statement("ALTER TABLE complaints ALTER COLUMN status SET DEFAULT 'gözləmədə'");

        // Yer geri
        DB::statement("UPDATE complaints SET yer = 'yol' WHERE yer = 'road'");
        DB::statement("UPDATE complaints SET yer = 'qaraj' WHERE yer = 'garage'");

        // Type geri
        DB::statement("UPDATE complaints SET complaint_type = 'qezali' WHERE complaint_type = 'accident'");
        DB::statement("UPDATE complaints SET complaint_type = 'nasazliq' WHERE complaint_type = 'breakdown'");
        DB::statement("UPDATE complaints SET complaint_type = 'texniki_xidmet' WHERE complaint_type = 'maintenance'");
    }
};
