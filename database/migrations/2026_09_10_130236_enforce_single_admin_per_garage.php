<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS garage_user_single_admin');

        DB::statement("
            CREATE UNIQUE INDEX garage_user_single_admin
            ON garage_user (garage_id)
            WHERE role = 'admin'
              AND is_active = true
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS garage_user_single_admin');
    }
};
