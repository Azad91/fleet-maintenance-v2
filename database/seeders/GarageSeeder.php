<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Database\Seeder;

class GarageSeeder extends Seeder
{
    public function run(): void
    {
        // === COMPANY 1: BakuBus ===
        $company1 = Company::updateOrCreate(
            ['slug' => 'bakubus'],
            [
                'name'      => 'BakuBus',
                'email'     => 'info@bakubus.az',
                'phone'     => '+994 12 123 45 67',
                'address'   => 'Baku, Nasimi district',
                'is_active' => true,
            ]
        );

        $garage1 = Garage::updateOrCreate(
            ['code' => 'GAR-001'],
            [
                'company_id' => $company1->id,
                'name'       => 'Central Garage',
                'address'    => 'Baku, Yasamal district',
                'phone'      => '+994 12 111 11 11',
                'is_active'  => true,
            ]
        );

        $garage2 = Garage::updateOrCreate(
            ['code' => 'GAR-002'],
            [
                'company_id' => $company1->id,
                'name'       => 'Sumgayit Garage',
                'address'    => 'Sumgayit, Industrial zone',
                'phone'      => '+994 12 222 22 22',
                'is_active'  => true,
            ]
        );

        // === COMPANY 2: Azerbaijan Automobile ===
        $company2 = Company::updateOrCreate(
            ['slug' => 'azavto'],
            [
                'name'      => 'Azerbaijan Automobile',
                'email'     => 'info@azavto.az',
                'phone'     => '+994 12 987 65 43',
                'address'   => 'Baku, Khatai district',
                'is_active' => true,
            ]
        );

        $garage3 = Garage::updateOrCreate(
            ['code' => 'GAR-003'],
            [
                'company_id' => $company2->id,
                'name'       => 'Khatai Garage',
                'address'    => 'Baku, Khatai district',
                'phone'      => '+994 12 333 33 33',
                'is_active'  => true,
            ]
        );

        $garage4 = Garage::updateOrCreate(
            ['code' => 'GAR-004'],
            [
                'company_id' => $company2->id,
                'name'       => 'Nasimi Garage',
                'address'    => 'Baku, Nasimi district',
                'phone'      => '+994 12 444 44 44',
                'is_active'  => true,
            ]
        );

        // === USERS ===

        // 1. Admin — access to all garages
        $admin = User::where('email', 'admin@fleet.com')->first();
        if ($admin) {
            $admin->garages()->detach();
            $admin->garages()->attach([
                $garage1->id => ['role' => 'admin', 'is_active' => true],
                $garage2->id => ['role' => 'admin', 'is_active' => true],
                $garage3->id => ['role' => 'admin', 'is_active' => true],
                $garage4->id => ['role' => 'admin', 'is_active' => true],
            ]);
            $admin->setCurrentGarage($garage1);
        }

        // 2. Directorate — read-only access to all garages
        $directorate = User::where('email', 'directorate@fleet.com')->first();
        if ($directorate) {
            $directorate->garages()->detach();
            $directorate->garages()->attach([
                $garage1->id => ['role' => 'directorate', 'is_active' => true],
                $garage2->id => ['role' => 'directorate', 'is_active' => true],
                $garage3->id => ['role' => 'directorate', 'is_active' => true],
                $garage4->id => ['role' => 'directorate', 'is_active' => true],
            ]);
            $directorate->setCurrentGarage($garage1);
        }

        // 3. Complaint manager — BakuBus garages
        $complaint = User::where('email', 'complaint@fleet.com')->first();
        if ($complaint) {
            $complaint->garages()->detach();
            $complaint->garages()->attach([
                $garage1->id => ['role' => 'complaint', 'is_active' => true],
                $garage2->id => ['role' => 'complaint', 'is_active' => true],
            ]);
            $complaint->setCurrentGarage($garage1);
        }

        // 4. Warehouse manager — BakuBus garages
        $warehouse = User::where('email', 'warehouse@fleet.com')->first();
        if ($warehouse) {
            $warehouse->garages()->detach();
            $warehouse->garages()->attach([
                $garage1->id => ['role' => 'warehouse', 'is_active' => true],
                $garage2->id => ['role' => 'warehouse', 'is_active' => true],
            ]);
            $warehouse->setCurrentGarage($garage1);
        }

        // 5. Daily KM manager
        $dailyKm = User::where('email', 'daily-km@fleet.com')->first();
        if ($dailyKm) {
            $dailyKm->garages()->sync([
                $garage1->id => ['role' => 'daily_km', 'is_active' => true],
                $garage2->id => ['role' => 'daily_km', 'is_active' => true],
            ]);
            $dailyKm->setCurrentGarage($garage1);
        }

        // 6. Daily status manager
        $dailyStatus = User::where('email', 'daily-status@fleet.com')->first();
        if ($dailyStatus) {
            $dailyStatus->garages()->sync([
                $garage1->id => ['role' => 'daily_status', 'is_active' => true],
                $garage2->id => ['role' => 'daily_status', 'is_active' => true],
            ]);
            $dailyStatus->setCurrentGarage($garage1);
        }
    }
}