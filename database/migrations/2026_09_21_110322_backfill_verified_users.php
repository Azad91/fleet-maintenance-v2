<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Marks every existing user as email-verified.
 *
 * Background:
 *   - Self-registration is DISABLED. Every user in this system is
 *     created by an admin (SuperAdmin onboarding, CompanyOnboarding,
 *     GarageOnboarding, or UserService).
 *   - We're about to add the `verified` middleware to business routes.
 *     Without this backfill, every existing user would be locked out
 *     on deploy.
 *   - Going forward, admin-created users are auto-verified at creation
 *     time (see the onboarding services), so this migration only needs
 *     to run once.
 */
return new class extends Migration
{
    public function up(): void
    {
        $updated = DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);

        if ($updated > 0) {
            // Laravel 11+ uses `Context` for logging metadata, but this
            // migration also runs in console context where request_id
            // is not set. Keep it simple.
            Illuminate\Support\Facades\Log::info(
                "Backfill: {$updated} user(s) marked as email-verified."
            );
        }
    }

    public function down(): void
    {
        // Intentional no-op. Un-marking users as verified would be
        // destructive and is not a meaningful rollback.
    }
};
