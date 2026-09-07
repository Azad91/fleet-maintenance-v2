<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // users.role = 'admin' olanları 'super_admin' ilə əvəz et
        DB::table('users')
            ->where('role', 'admin')
            ->update(['role' => 'super_admin']);
    }

    public function down(): void
    {
        // Geri qaytarmaq istəsən, super_admin-i admin-ə çevir
        DB::table('users')
            ->where('role', 'super_admin')
            ->update(['role' => 'admin']);
    }
};
