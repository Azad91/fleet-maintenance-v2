<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. 'admin' rolunu 'user' olaraq dəyiş (super_admin deyillərsə)
        DB::statement("
            UPDATE users
            SET role = 'user'
            WHERE role = 'admin'
            AND id NOT IN (
                SELECT user_id FROM garage_user WHERE role = 'admin'
            )
        ");

        // 2. Həqiqətən super admin olmalı olanları əl ilə təyin etmək lazımdır.
        // Məsələn, sizin admin@fleet.com hesabınız super_admin olsun:
        DB::statement("
            UPDATE users
            SET role = 'super_admin'
            WHERE email = 'admin@fleet.com'
        ");

        // 3. DB constraint-ı yenilə (əgər varsa)
        // Əgər users_role_check constraint-i varsa, onu yenilə
        // Bu addımı əlavə etmək olar, amma əvvəl mövcud constraint-i yoxlamaq lazımdır.
    }

    public function down(): void
    {
        // Geri qaytarma
        DB::statement("UPDATE users SET role = 'admin' WHERE role = 'super_admin' OR role = 'user'");
    }
};
