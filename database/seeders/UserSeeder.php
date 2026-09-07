<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Garage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ==================== SUPER ADMIN ====================
        // users.role = super_admin olaraq qalır
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@fleet.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
            ]
        );

        // ==================== İSTİFADƏÇİLƏR (users.role = user) ====================
        // Bütün business rollar garage_user cədvəlində saxlanılacaq

        // 1. Müdiriyyət
        $directorate = User::updateOrCreate(
            ['email' => 'directorate@fleet.com'],
            [
                'name' => 'Müdiriyyət',
                'password' => Hash::make('password'),
                'role' => 'user',
            ]
        );

        // 2. Şikayət işçisi
        $complaint = User::updateOrCreate(
            ['email' => 'complaint@fleet.com'],
            [
                'name' => 'Şikayət İşçisi',
                'password' => Hash::make('password'),
                'role' => 'user',
            ]
        );

        // 3. Anbar işçisi
        $warehouse = User::updateOrCreate(
            ['email' => 'warehouse@fleet.com'],
            [
                'name' => 'Anbar İşçisi',
                'password' => Hash::make('password'),
                'role' => 'user',
            ]
        );

        // 4. Günlük KM işçisi
        $dailyKm = User::updateOrCreate(
            ['email' => 'daily-km@fleet.com'],
            [
                'name' => 'Günlük KM İşçisi',
                'password' => Hash::make('password'),
                'role' => 'user',
            ]
        );

        // 5. Günlük Status işçisi
        $dailyStatus = User::updateOrCreate(
            ['email' => 'daily-status@fleet.com'],
            [
                'name' => 'Günlük Status İşçisi',
                'password' => Hash::make('password'),
                'role' => 'user',
            ]
        );

        // ==================== QARAJLARA TƏYİN ET ====================
        $garages = Garage::all();

        if ($garages->isEmpty()) {
            // Qaraj yoxdursa, seeder işləməz – amma GarageSeeder əvvəlcə işləyəcək
            return;
        }

        // Super Admin – bütün qarajlara tam nəzarət
        foreach ($garages as $garage) {
            $superAdmin->garages()->syncWithoutDetaching([
                $garage->id => ['role' => 'admin', 'is_active' => true]
            ]);
        }

        // Müdiriyyət – bütün qarajlara baxış
        foreach ($garages as $garage) {
            $directorate->garages()->syncWithoutDetaching([
                $garage->id => ['role' => 'directorate', 'is_active' => true]
            ]);
        }

        // Şikayət işçisi – yalnız ilk qaraja
        if ($garages->first()) {
            $complaint->garages()->syncWithoutDetaching([
                $garages->first()->id => ['role' => 'complaint', 'is_active' => true]
            ]);
        }

        // Anbar işçisi – yalnız ilk qaraja
        if ($garages->first()) {
            $warehouse->garages()->syncWithoutDetaching([
                $garages->first()->id => ['role' => 'warehouse', 'is_active' => true]
            ]);
        }

        // Günlük KM – yalnız ilk qaraja
        if ($garages->first()) {
            $dailyKm->garages()->syncWithoutDetaching([
                $garages->first()->id => ['role' => 'daily_km', 'is_active' => true]
            ]);
        }

        // Günlük Status – yalnız ilk qaraja
        if ($garages->first()) {
            $dailyStatus->garages()->syncWithoutDetaching([
                $garages->first()->id => ['role' => 'daily_status', 'is_active' => true]
            ]);
        }

        // ==================== Cari qaraj təyini ====================
        $firstGarage = $garages->first();
        if ($firstGarage) {
            $superAdmin->setCurrentGarage($firstGarage);
            $directorate->setCurrentGarage($firstGarage);
            $complaint->setCurrentGarage($firstGarage);
            $warehouse->setCurrentGarage($firstGarage);
            $dailyKm->setCurrentGarage($firstGarage);
            $dailyStatus->setCurrentGarage($firstGarage);
        }
    }
}
