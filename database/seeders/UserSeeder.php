<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
        $generated = $password === null; // null signals "random was generated"

        if ($generated) {
            $password = Str::password(24, symbols: true);
        }

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

        $this->reportSuperAdminCredentials($password, $generated);
    }

    /**
     * Decide which password to use for a brand-new Super Admin.
     *
     * Priority:
     *   1. SUPER_ADMIN_PASSWORD env var (explicit operator intent)
     *   2. Production without env var → generate a strong random password
     *   3. Non-production → 'password' for developer convenience
     *
     * @return string|null The chosen password, or null to signal
     *                     "caller must generate a random one".
     */
    private function resolveSuperAdminPassword(): ?string
    {
        if ($env = env('SUPER_ADMIN_PASSWORD')) {
            return (string) $env;
        }

        if (app()->environment('production')) {
            return null; // signal random generation
        }

        return 'password';
    }

    /**
     * Print a loud, one-time warning so the operator can copy the
     * credentials before they scroll off the terminal.
     */
    private function reportSuperAdminCredentials(string $password, bool $generated): void
    {
        $this->command->newLine();
        $this->command->info('══════════════════════════════════════════════════════════');
        $this->command->info(' SUPER ADMIN CREDENTIALS (shown ONLY once)');
        $this->command->info('══════════════════════════════════════════════════════════');
        $this->command->line('  Email:    admin@fleet.com');
        $this->command->line('  Password: '.$password);
        $this->command->info('══════════════════════════════════════════════════════════');

        if ($generated) {
            $this->command->warn('  ⚠️  This password was RANDOMLY generated.');
            $this->command->warn('  ⚠️  Save it NOW — it will NEVER be shown again.');
            $this->command->warn('  ⚠️  If lost, run: php artisan tinker');
            $this->command->warn('      User::where("email","admin@fleet.com")');
            $this->command->warn('          ->update(["password" => Hash::make("new")]);');
        } else {
            $this->command->warn('  ⚠️  Change this password immediately after first login.');
        }

        $this->command->newLine();
    }
}
