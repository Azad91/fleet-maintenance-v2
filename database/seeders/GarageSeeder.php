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
        // === ŞİRKƏT 1: BakuBus ===
        $company1 = Company::updateOrCreate(
            ['slug' => 'bakubus'],
            [
                'name' => 'BakuBus',
                'email' => 'info@bakubus.az',
                'phone' => '+994 12 123 45 67',
                'address' => 'Bakı, Nəsimi rayonu',
                'is_active' => true,
            ]
        );

        $garage1 = Garage::updateOrCreate(
            ['code' => 'GAR-001'],
            [
                'company_id' => $company1->id,
                'name' => 'Mərkəzi Qaraj',
                'address' => 'Bakı, Yasamal rayonu',
                'phone' => '+994 12 111 11 11',
                'is_active' => true,
            ]
        );

        $garage2 = Garage::updateOrCreate(
            ['code' => 'GAR-002'],
            [
                'company_id' => $company1->id,
                'name' => 'Sumqayıt Qaraj',
                'address' => 'Sumqayıt, Sənaye zonası',
                'phone' => '+994 12 222 22 22',
                'is_active' => true,
            ]
        );

        // === ŞİRKƏT 2: Azərbaycan Avtomobil ===
        $company2 = Company::updateOrCreate(
            ['slug' => 'azavto'],
            [
                'name' => 'Azərbaycan Avtomobil',
                'email' => 'info@azavto.az',
                'phone' => '+994 12 987 65 43',
                'address' => 'Bakı, Xətai rayonu',
                'is_active' => true,
            ]
        );

        $garage3 = Garage::updateOrCreate(
            ['code' => 'GAR-003'],
            [
                'company_id' => $company2->id,
                'name' => 'Xətai Qaraj',
                'address' => 'Bakı, Xətai rayonu',
                'phone' => '+994 12 333 33 33',
                'is_active' => true,
            ]
        );

        $garage4 = Garage::updateOrCreate(
            ['code' => 'GAR-004'],
            [
                'company_id' => $company2->id,
                'name' => 'Nəsimi Qaraj',
                'address' => 'Bakı, Nəsimi rayonu',
                'phone' => '+994 12 444 44 44',
                'is_active' => true,
            ]
        );

        // === İSTİFADƏÇİLƏR ===

        // 1. Admin - bütün qarajlara icazə
        $admin = User::where('email', 'admin@fleet.com')->first();
        if ($admin) {
            // Əvvəlki əlaqələri təmizlə
            $admin->garages()->detach();
            // Yeni əlaqələr
            $admin->garages()->attach([
                $garage1->id => ['role' => 'admin', 'is_active' => true],
                $garage2->id => ['role' => 'admin', 'is_active' => true],
                $garage3->id => ['role' => 'admin', 'is_active' => true],
                $garage4->id => ['role' => 'admin', 'is_active' => true],
            ]);
            $admin->setCurrentGarage($garage1);
        }

        // 2. Müdiriyyət - bütün qarajlara baxış
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

        // 3. Şikayət işçisi - BakuBus qarajlarına
        $complaint = User::where('email', 'complaint@fleet.com')->first();
        if ($complaint) {
            $complaint->garages()->detach();
            $complaint->garages()->attach([
                $garage1->id => ['role' => 'complaint', 'is_active' => true],
                $garage2->id => ['role' => 'complaint', 'is_active' => true],
            ]);
            $complaint->setCurrentGarage($garage1);
        }

        // 4. Anbar işçisi - BakuBus qarajlarına
        $warehouse = User::where('email', 'warehouse@fleet.com')->first();
        if ($warehouse) {
            $warehouse->garages()->detach();
            $warehouse->garages()->attach([
                $garage1->id => ['role' => 'warehouse', 'is_active' => true],
                $garage2->id => ['role' => 'warehouse', 'is_active' => true],
            ]);
            $warehouse->setCurrentGarage($garage1);
        }
    }
}
