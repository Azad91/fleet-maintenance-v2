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

        // UserSeeder creates the first Super Admin. This legacy migration
        // must not silently elevate a hard-coded email in another tenant.
        $email = env('SUPER_ADMIN_EMAIL');
        if (is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            DB::table('users')
                ->where('email', $email)
                ->whereNull('deleted_at')
                ->update(['role' => 'super_admin']);
        }

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
