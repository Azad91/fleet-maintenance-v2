<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop global unique constraints on users table
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_email_unique');
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_employee_code_unique');

        // Recreate as partial unique indexes (only for active rows)
        DB::statement('
            CREATE UNIQUE INDEX users_email_active_unique
            ON users (email)
            WHERE deleted_at IS NULL
        ');

        DB::statement('
            CREATE UNIQUE INDEX users_employee_code_active_unique
            ON users (employee_code)
            WHERE deleted_at IS NULL AND employee_code IS NOT NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_email_active_unique');
        DB::statement('DROP INDEX IF EXISTS users_employee_code_active_unique');

        DB::statement('ALTER TABLE users ADD CONSTRAINT users_email_unique UNIQUE (email)');
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_employee_code_unique UNIQUE (employee_code)');
    }
};
