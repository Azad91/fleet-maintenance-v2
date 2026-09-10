<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_single_super_admin');

        DB::statement("
            CREATE UNIQUE INDEX users_single_super_admin
            ON users (role)
            WHERE role = 'super_admin'
              AND deleted_at IS NULL
              AND is_active = true
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_single_super_admin');
    }
};
