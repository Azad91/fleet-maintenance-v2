<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the baseline multi-tenant structure:
 *   - 2 companies (BakuBus, Azerbaijan Automobile)
 *   - 1 Director per company (see SuperAdmin spec)
 *   - 4 garages across the 2 companies
 *   - 1 Admin per garage (see SuperAdmin spec)
 *
 * The SuperAdmin (admin@fleet.com) is created by UserSeeder and is
 * intentionally NOT attached to any garage — the SuperAdmin is a
 * platform-level role, not a garage admin.
 *
 * This seeder is idempotent: re-running it will not reset passwords,
 * PINs, or duplicate any rows. It uses `updateOrCreate` for the
 * canonical rows and `syncWithoutDetaching` for pivot relations.
 */
class GarageSeeder extends Seeder
{
    public function run(): void
    {
        // ─── COMPANY 1: BakuBus ───
        $company1 = Company::updateOrCreate(
            ['slug' => 'bakubus'],
            [
                'name' => 'BakuBus',
                'email' => 'info@bakubus.az',
                'phone' => '+994 12 123 45 67',
                'address' => 'Baku, Nasimi district',
                'is_active' => true,
            ]
        );

        $garage1 = Garage::updateOrCreate(
            ['code' => 'GAR-001'],
            [
                'company_id' => $company1->id,
                'name' => 'Central Garage',
                'address' => 'Baku, Yasamal district',
                'phone' => '+994 12 111 11 11',
                'is_active' => true,
            ]
        );

        $garage2 = Garage::updateOrCreate(
            ['code' => 'GAR-002'],
            [
                'company_id' => $company1->id,
                'name' => 'Sumgayit Garage',
                'address' => 'Sumgayit, Industrial zone',
                'phone' => '+994 12 222 22 22',
                'is_active' => true,
            ]
        );

        // ─── COMPANY 2: Azerbaijan Automobile ───
        $company2 = Company::updateOrCreate(
            ['slug' => 'azavto'],
            [
                'name' => 'Azerbaijan Automobile',
                'email' => 'info@azavto.az',
                'phone' => '+994 12 987 65 43',
                'address' => 'Baku, Khatai district',
                'is_active' => true,
            ]
        );

        $garage3 = Garage::updateOrCreate(
            ['code' => 'GAR-003'],
            [
                'company_id' => $company2->id,
                'name' => 'Khatai Garage',
                'address' => 'Baku, Khatai district',
                'phone' => '+994 12 333 33 33',
                'is_active' => true,
            ]
        );

        $garage4 = Garage::updateOrCreate(
            ['code' => 'GAR-004'],
            [
                'company_id' => $company2->id,
                'name' => 'Nasimi Garage',
                'address' => 'Baku, Nasimi district',
                'phone' => '+994 12 444 44 44',
                'is_active' => true,
            ]
        );

        // ─── ONE DIRECTOR PER COMPANY ───
        $this->seedDirector($company1, 'director.bakubus@fleet.com', 'BakuBus Director');
        $this->seedDirector($company2, 'director.azavto@fleet.com', 'AzAvto Director');

        // ─── ONE ADMIN PER GARAGE ───
        $this->seedGarageAdmin($garage1, 'admin.gar1@fleet.com', 'Central Garage Admin');
        $this->seedGarageAdmin($garage2, 'admin.gar2@fleet.com', 'Sumgayit Garage Admin');
        $this->seedGarageAdmin($garage3, 'admin.gar3@fleet.com', 'Khatai Garage Admin');
        $this->seedGarageAdmin($garage4, 'admin.gar4@fleet.com', 'Nasimi Garage Admin');
    }

    /**
     * Create a Director user for the given company.
     *
     * `updateOrCreate` keeps the seeder idempotent — re-running it will
     * not reset an existing user's password or PIN. The global role is
     * forced to 'user' (never 'super_admin') since the concrete role
     * lives on the `company_user` pivot.
     */
    private function seedDirector(Company $company, string $email, string $name): void
    {
        $director = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'employee_code' => $this->generateEmployeeCode('DIR-', $email),
                'pin' => Hash::make('1234'),
                'pin_is_default' => true,
                'is_active' => true,
            ]
        );

        // Safety: demote if the account was accidentally promoted before.
        if ($director->role !== 'user') {
            $director->demoteToRegularUser()->save();
        }

        // Attach as director if not already attached.
        $company->users()->syncWithoutDetaching([
            $director->id => ['role' => 'director', 'is_active' => true],
        ]);
    }

    /**
     * Create an Admin user for the given garage.
     *
     * Each garage gets its OWN admin. Before attaching, any other
     * active admin in the same garage is deactivated to satisfy the
     * `garage_user_single_admin` partial unique index.
     */
    private function seedGarageAdmin(Garage $garage, string $email, string $name): void
    {
        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'employee_code' => $this->generateEmployeeCode('ADM-', $email),
                'pin' => Hash::make('1234'),
                'pin_is_default' => true,
                'is_active' => true,
            ]
        );

        if ($admin->role !== 'user') {
            $admin->demoteToRegularUser()->save();
        }

        // Deactivate any OTHER active admin in this garage first.
        $garage->users()
            ->wherePivot('role', 'admin')
            ->wherePivot('is_active', true)
            ->where('users.id', '!=', $admin->id)
            ->get()
            ->each(function (User $other) use ($garage) {
                $garage->users()->updateExistingPivot($other->id, [
                    'is_active' => false,
                ]);
            });

        // Attach or reactivate the pivot row.
        $garage->users()->syncWithoutDetaching([
            $admin->id => ['role' => 'admin', 'is_active' => true],
        ]);

        // `syncWithoutDetaching` does not flip is_active to true if the
        // row already exists with is_active = false — fix that.
        $garage->users()->updateExistingPivot($admin->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    /**
     * Deterministic employee code from email. Avoids random collisions
     * and keeps the seeder truly idempotent (rerunning yields the same
     * code for the same email).
     */
    private function generateEmployeeCode(string $prefix, string $email): string
    {
        return $prefix.strtoupper(substr(md5($email), 0, 6));
    }
}
