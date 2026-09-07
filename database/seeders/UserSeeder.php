<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin – super_admin olaraq qalır
        User::updateOrCreate(
            ['email' => 'admin@fleet.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
            ]
        );

        // Müdiriyyət
        User::updateOrCreate(
            ['email' => 'directorate@fleet.com'],
            [
                'name' => 'Müdiriyyət',
                'password' => Hash::make('password'),
                'role' => 'directorate',
            ]
        );

        // Şikayət işçisi
        User::updateOrCreate(
            ['email' => 'complaint@fleet.com'],
            [
                'name' => 'Şikayət İşçisi',
                'password' => Hash::make('password'),
                'role' => 'complaint',
            ]
        );

        // Anbar işçisi
        User::updateOrCreate(
            ['email' => 'warehouse@fleet.com'],
            [
                'name' => 'Anbar İşçisi',
                'password' => Hash::make('password'),
                'role' => 'warehouse',
            ]
        );

        // ✅ DƏYİŞİKLİK: 'bus' əvəzinə 'viewer' (və ya 'user')
        User::updateOrCreate(
            ['email' => 'daily-km@fleet.com'],
            [
                'name' => 'Günlük KM İşçisi',
                'password' => Hash::make('password'),
                'role' => RoleEnum::VIEWER->value, // 'viewer'
            ]
        );

        User::updateOrCreate(
            ['email' => 'daily-status@fleet.com'],
            [
                'name' => 'Günlük Status İşçisi',
                'password' => Hash::make('password'),
                'role' => RoleEnum::VIEWER->value, // 'viewer'
            ]
        );
    }
}
