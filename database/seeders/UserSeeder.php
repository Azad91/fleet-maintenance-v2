<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // 1. SUPER ADMIN
        // ============================================================
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@fleet.com'],
            [
                'name'      => 'Super Admin',
                'password'  => Hash::make('password'),
                'is_active' => true,
            ]
        );

        // Role is not mass-assignable — set explicitly.
        $superAdmin->promoteToSuperAdmin()->save();

        // ============================================================
        // 2. TEST GARAGE ADMIN
        // ============================================================
        $company = Company::where('slug', 'bakubus')->first();

        if ($company) {
            $garage = Garage::where('company_id', $company->id)
                ->where('code', 'GAR-001')
                ->first();

            if ($garage) {
                $admin = User::updateOrCreate(
                    ['employee_code' => 'QAR-001'],
                    [
                        'name'            => 'Garage Admin',
                        'email'           => 'garage.admin@example.com',
                        'password'        => Hash::make('password'),
                        'pin'             => Hash::make('1234'),
                        'pin_is_default'  => true,
                        'is_active'       => true,
                    ]
                );

                // Role defaults to 'user' — explicit call is a safety net.
                $admin->demoteToRegularUser()->save();

                $admin->garages()->sync([
                    $garage->id => [
                        'role'      => 'admin',
                        'is_active' => true,
                    ],
                ]);
            }
        }
    }
}
