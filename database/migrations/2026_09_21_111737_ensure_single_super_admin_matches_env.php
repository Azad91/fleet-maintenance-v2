<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ensures the platform SuperAdmin's email matches the value in
 * SUPER_ADMIN_EMAIL.
 *
 * Background:
 *   Migration 2026_09_07_081048 hard-coded `WHERE email = 'admin@fleet.com'`
 *   to promote a user to super_admin. That is fragile in multi-tenant
 *   deployments where the SuperAdmin uses a different email.
 *
 * This migration is idempotent and safe:
 *   - If SUPER_ADMIN_EMAIL is not set, it does nothing.
 *   - If a user with that email exists and is not yet a super_admin,
 *     it promotes them.
 *   - It never demotes an existing super_admin.
 *
 * In a fresh installation, UserSeeder already creates the SuperAdmin
 * with the correct email, so this migration is a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        $email = env('SUPER_ADMIN_EMAIL');

        if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return; // Nothing to do — no valid email configured
        }

        $user = DB::table('users')
            ->where('email', $email)
            ->whereNull('deleted_at')
            ->first();

        if (! $user) {
            return; // No user with that email — nothing to promote
        }

        if ($user->role === 'super_admin') {
            return; // Already correct — nothing to do
        }

        // We must not create a second super_admin (partial unique index
        // users_single_super_admin forbids it). If one already exists,
        // log a warning and skip.
        $existingSuperAdmin = DB::table('users')
            ->where('role', 'super_admin')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->first();

        if ($existingSuperAdmin) {
            \Illuminate\Support\Facades\Log::warning(
                'SUPER_ADMIN_EMAIL points to a user that is not the current SuperAdmin. '
                .'Skipping promotion to avoid violating the single-super-admin rule.',
                [
                    'configured_email'      => $email,
                    'existing_super_admin'  => $existingSuperAdmin->email,
                ]
            );

            return;
        }

        DB::table('users')
            ->where('id', $user->id)
            ->update(['role' => 'super_admin']);

        \Illuminate\Support\Facades\Log::info(
            "Promoted user [{$email}] to super_admin via SUPER_ADMIN_EMAIL."
        );
    }

    public function down(): void
    {
        // Intentional no-op. Rolling back would require us to know
        // which super_admin was promoted by this migration, which is
        // not tracked. This is a data migration, not a schema change.
    }
};
