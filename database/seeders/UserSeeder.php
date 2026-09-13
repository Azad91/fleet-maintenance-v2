<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSuperAdmin();
        $this->seedGarageAdmin();
    }

    // ============================================================
    // 1. SUPER ADMIN
    // ============================================================
    private function seedSuperAdmin(): void
    {
        $existing = User::withoutGlobalScopes()
            ->where('email', 'admin@fleet.com')
            ->first();

        // Idempotent: never overwrite an existing super admin's password.
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

    // ============================================================
    // 2. TEST GARAGE ADMIN (dev only — unchanged behavior)
    // ============================================================
    private function seedGarageAdmin(): void
    {
        $company = Company::where('slug', 'bakubus')->first();

        if (! $company) {
            return;
        }

        $garage = Garage::where('company_id', $company->id)
            ->where('code', 'GAR-001')
            ->first();

        if (! $garage) {
            return;
        }

        // Skip in production — this is a known-credential test account.
        if (app()->environment('production')) {
            $this->command->warn('Skipped test garage admin (production environment).');

            return;
        }

        $admin = User::updateOrCreate(
            ['employee_code' => 'QAR-001'],
            [
                'name' => 'Garage Admin',
                'email' => 'garage.admin@example.com',
                'password' => Hash::make('password'),
                'pin' => Hash::make('1234'),
                'pin_is_default' => true,
                'is_active' => true,
            ]
        );

        $admin->demoteToRegularUser()->save();

        // ─────────────────────────────────────────────────────────────
        // STEP 1: Deactivate any OTHER active admin in this garage.
        //
        // The `garage_user_single_admin` partial unique index allows only
        // ONE active admin per garage. Without this step, attaching our
        // seeder admin to a garage that already has an active admin
        // raises a UniqueConstraintViolationException.
        // ─────────────────────────────────────────────────────────────
        $garage->users()
            ->wherePivot('role', 'admin')
            ->wherePivot('is_active', true)
            ->where('users.id', '!=', $admin->id)
            ->get()
            ->each(function (User $otherAdmin) use ($garage) {
                $garage->users()->updateExistingPivot($otherAdmin->id, [
                    'is_active' => false,
                ]);

                $this->command->warn(sprintf(
                    '  ⚠️  Deactivated existing admin (id=%d, %s) in garage "%s".',
                    $otherAdmin->id,
                    $otherAdmin->email,
                    $garage->name,
                ));
            });

        // ─────────────────────────────────────────────────────────────
        // STEP 2: Attach the seeder admin.
        //
        // We use `syncWithoutDetaching` instead of `sync` so that
        // pre-existing pivot rows for OTHER garages are preserved.
        // `sync` would detach this user from every other garage.
        // ─────────────────────────────────────────────────────────────
        $admin->garages()->syncWithoutDetaching([
            $garage->id => [
                'role' => 'admin',
                'is_active' => true,
            ],
        ]);

        // Make sure our own pivot row is active — syncWithoutDetaching
        // does not flip `is_active` to true if the row already exists
        // with is_active = false.
        $garage->users()->updateExistingPivot($admin->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->command->line(sprintf(
            '  ✓ Garage admin seeded: %s (garage: %s)',
            $admin->email,
            $garage->name,
        ));
    }
}
