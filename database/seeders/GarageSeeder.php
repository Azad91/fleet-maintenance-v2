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
        $company1 = Company::create([
            'name' => 'BakuBus',
            'slug' => 'bakubus',
            'email' => 'info@bakubus.az',
            'phone' => '+994 12 123 45 67',
            'address' => 'Bakı, Nəsimi rayonu',
            'is_active' => true,
        ]);

        $garage1 = Garage::create([
            'company_id' => $company1->id,
            'name' => 'Mərkəzi Qaraj',
            'code' => 'GAR-001',
            'address' => 'Bakı, Yasamal rayonu',
            'phone' => '+994 12 111 11 11',
            'is_active' => true,
        ]);

        $garage2 = Garage::create([
            'company_id' => $company1->id,
            'name' => 'Sumqayıt Qaraj',
            'code' => 'GAR-002',
            'address' => 'Sumqayıt, Sənaye zonası',
            'phone' => '+994 12 222 22 22',
            'is_active' => true,
        ]);

        $company2 = Company::create([
            'name' => 'Azərbaycan Avtomobil',
            'slug' => 'azavto',
            'email' => 'info@azavto.az',
            'phone' => '+994 12 987 65 43',
            'address' => 'Bakı, Xətai rayonu',
            'is_active' => true,
        ]);

        $garage3 = Garage::create([
            'company_id' => $company2->id,
            'name' => 'Xətai Qaraj',
            'code' => 'GAR-003',
            'address' => 'Bakı, Xətai rayonu',
            'phone' => '+994 12 333 33 33',
            'is_active' => true,
        ]);

        $garage4 = Garage::create([
            'company_id' => $company2->id,
            'name' => 'Nəsimi Qaraj',
            'code' => 'GAR-004',
            'address' => 'Bakı, Nəsimi rayonu',
            'phone' => '+994 12 444 44 44',
            'is_active' => true,
        ]);

        $admin = User::where('email', 'admin@fleet.com')->first();
        if ($admin) {
            $admin->garages()->attach([$garage1->id, $garage2->id, $garage3->id, $garage4->id], ['role' => 'admin']);
            $admin->setCurrentGarage($garage1);
        }
    }
}
