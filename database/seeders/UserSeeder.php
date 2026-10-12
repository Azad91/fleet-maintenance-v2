<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the platform-level SuperAdmin account.
 *
 * Every other account type is created through its dedicated flow:
 *
 *   ┌─────────────────────┬──────────────────────────────────────────────┐
 *   │ Account type        │ Created by                                   │
 *   ├─────────────────────┼──────────────────────────────────────────────┤
 *   │ SuperAdmin          │ THIS seeder (idempotent, platform-level)     │
 *   │ Company Director    │ CompanyController::store                     │
 *   │                     │   → CompanyOnboardingService                 │
 *   │ Garage Admin        │ GarageController::store                      │
 *   │                     │   → GarageOnboardingService                  │
 *   │ Workers / Managers  │ UserManagementController (garage Admin)      │
 *   └─────────────────────┴──────────────────────────────────────────────┘
 *
 * The SuperAdmin is intentionally NOT attached to any garage — it is
 * a platform-scoped role, not a tenant-scoped one. The old
 * `seedGarageAdmin()` method (which created `garage.admin@example.com`)
 * was removed in favor of the onboarding flows above.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSuperAdmin();
    }

    // ============================================================
    // 1. SUPER ADMIN
    // ============================================================
    private function seedSuperAdmin(): void
    {
        $existing = User::withoutGlobalScopes()
            ->where('email', 'admin@fleet.com')
            ->first();

        // Idempotent: never overwrite an existing SuperAdmin's password.
        // Re-running `php artisan db:seed` must NOT reset credentials.
        if ($existing) {
            if (! $existing->isSuperAdmin()) {
                $existing->promoteToSuperAdmin()->save();
                $this->command->info('Existing user admin@fleet.com promoted to Super Admin.');
            } else {
                $this->command->line('Super Admin already exists — password left untouched.');
            }

            return;
        }

        $password = $this->resolveSuperAdminPassword();

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@fleet.com',
            'password' => Hash::make($password),
            'is_active' => true,
            // Admin-created users are considered pre-verified. Self-registration
            // is disabled, so there is no verification email to send. Without
            // this flag the user would be locked out by the `verified`
            // middleware immediately after `migrate:fresh --seed`.
            'email_verified_at' => now(),
        ]);

        $superAdmin->promoteToSuperAdmin()->save();

        $this->reportSuperAdminCredentials();
    }

    /**
     * Decide which password to use for a brand-new Super Admin.
     *
     * Priority:
     *   1. SUPER_ADMIN_PASSWORD env var (explicit operator intent)
     *   2. Local/testing without env var → 'password' for developer convenience
     *   3. Every other environment requires an explicit password. Generating a
     *      password and printing it to container logs would leak a privileged
     *      secret to every log reader.
     */
    private function resolveSuperAdminPassword(): string
    {
        if ($env = env('SUPER_ADMIN_PASSWORD')) {
            return (string) $env;
        }

        if (app()->environment(['local', 'testing'])) {
            return 'password';
        }

        throw new \RuntimeException(
            'SUPER_ADMIN_PASSWORD must be set before seeding a non-local environment.'
        );
    }

    /**
     * Confirm bootstrap without ever emitting a credential to logs.
     */
    private function reportSuperAdminCredentials(): void
    {
        $this->command->info('Super Admin created. Credentials were read from environment configuration.');
    }
}
