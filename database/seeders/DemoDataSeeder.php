<?php

namespace Database\Seeders;

use App\Models\Bus;
use App\Models\Complaint;
use App\Models\ComplaintType;
use App\Models\Company;
use App\Models\DailyKmRecord;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\Garage;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\GarageContext;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Realistic demo data for manual testing.
 *
 * Creates:
 *   - 1 company (BakuBus) with 2 garages
 *   - 1 Company Director
 *   - Per garage: 1 Admin, 4 Managers, 4 Workers (per domain)
 *   - 10 buses, 15 warehouse items, 8 employees, 8 drivers
 *   - 30 days of daily KM records + daily statuses
 *   - 15 complaints per garage
 *
 * ⚠️  DO NOT RUN IN PRODUCTION. Blocked by an environment guard.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('DemoDataSeeder is blocked in production.');
            return;
        }

        $this->command->info('🌱 Seeding demo data...');

        DB::transaction(function () {
            $company = $this->createCompany();
            [$garage1, $garage2] = $this->createGarages($company);

            $this->createDirector($company);
            $this->createSuperAdmin();

            foreach ([$garage1, $garage2] as $garage) {
                $this->seedGarage($garage);
            }
        });

        $this->command->info('✅ Demo data seeded successfully.');
        $this->command->info('');
        $this->command->info('Login credentials:');
        $this->command->info('  Super Admin → admin@fleet.com / password');
        $this->command->info('  Director    → director@demo.com / password');
        $this->command->info('  Garage Admin → admin.gar1@demo.com / password');
        $this->command->info('  Managers/Workers → see below');
    }

    // ==================== COMPANY + GARAGES ====================

    private function createCompany(): Company
    {
        return Company::updateOrCreate(
            ['slug' => 'bakubus-demo'],
            [
                'name'      => 'BakuBus Demo',
                'email'     => 'info@bakubus.demo',
                'phone'     => '+994 12 555 55 55',
                'address'   => 'Baku, Azerbaijan',
                'is_active' => true,
            ]
        );
    }

    /**
     * @return array{0: Garage, 1: Garage}
     */
    private function createGarages(Company $company): array
    {
        $g1 = Garage::updateOrCreate(
            ['code' => 'DEMO-GAR-001'],
            [
                'company_id' => $company->id,
                'name'       => 'Demo Central Garage',
                'address'    => 'Baku, Yasamal',
                'phone'      => '+994 12 111 11 11',
                'is_active'  => true,
            ]
        );

        $g2 = Garage::updateOrCreate(
            ['code' => 'DEMO-GAR-002'],
            [
                'company_id' => $company->id,
                'name'       => 'Demo Sumgayit Garage',
                'address'    => 'Sumgayit, Industrial',
                'phone'      => '+994 12 222 22 22',
                'is_active'  => true,
            ]
        );

        return [$g1, $g2];
    }

    // ==================== USERS ====================

    private function createSuperAdmin(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@fleet.com'],
            [
                'name'      => 'Super Admin',
                'password'  => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $admin->promoteToSuperAdmin()->save();
    }

    private function createDirector(Company $company): void
    {
        $director = User::updateOrCreate(
        ['email' => 'director@demo.com'],
        [
            'name'            => 'Rəşad Direktor',
            'password'        => Hash::make('password'),
            'employee_code'   => 'DIR-001',
            'pin'             => Hash::make('1234'),
            'pin_is_default'  => true,
            'is_active'       => true,
        ]
    );
    // Role defaults to 'user' — no explicit call needed.

        $company->users()->syncWithoutDetaching([
            $director->id => ['role' => 'director', 'is_active' => true],
        ]);
    }

    private function seedGarage(Garage $garage): void
    {
        $this->command->info("  → Seeding garage: {$garage->name}");

        // Set context so HasGarageScope works
        GarageContext::set($garage->id, $garage->company_id);

        $this->createGarageUsers($garage);
        $this->createComplaintTypes($garage);
        $this->createWarehouseItems($garage);
        $this->createBuses($garage);
        $this->createEmployees($garage);
        $this->createDrivers($garage);
        $this->createDailyKmRecords($garage);
        $this->createDailyStatuses($garage);
        $this->createComplaints($garage);

        GarageContext::clear();
    }

    private function createGarageUsers(Garage $garage): void
{
    $suffix = $garage->code === 'DEMO-GAR-001' ? 'gar1' : 'gar2';

    $roleMap = [
        'admin'                  => "Garage Admin ({$suffix})",
        'complaint_manager'      => "Complaint Manager ({$suffix})",
        'complaint_worker'       => "Complaint Worker ({$suffix})",
        'warehouse_manager'      => "Warehouse Manager ({$suffix})",
        'warehouse_worker'       => "Warehouse Worker ({$suffix})",
        'daily_km_manager'       => "Daily KM Manager ({$suffix})",
        'daily_km_worker'        => "Daily KM Worker ({$suffix})",
        'daily_status_manager'   => "Daily Status Manager ({$suffix})",
        'daily_status_worker'    => "Daily Status Worker ({$suffix})",
    ];

    $counter = 1;

    foreach ($roleMap as $role => $name) {
        $slug  = $role === 'admin' ? 'admin' : str_replace('_', '.', $role);
        $email = "{$slug}.{$suffix}@demo.com";

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name'            => $name,
                'password'        => Hash::make('password'),
                'employee_code'   => sprintf('EMP-%s-%03d', strtoupper($suffix), $counter),
                'pin'             => Hash::make('1234'),
                'pin_is_default'  => true,
                'is_active'       => true,
            ]
        );
        // Role defaults to 'user'.

        $user->garages()->syncWithoutDetaching([
            $garage->id => ['role' => $role, 'is_active' => true],
        ]);

        $this->command->line("     • {$role} → {$email} (PIN: 1234)");
        $counter++;
    }
}

    private function createComplaintTypes(Garage $garage): void
    {
        $types = [
            'Engine noise',
            'Tire puncture',
            'Brake problem',
            'Lighting failure',
            'Transmission problem',
            'Suspension problem',
            'Electrical problem',
            'Air conditioning failure',
            'Oil leak',
            'Other',
        ];

        foreach ($types as $name) {
            ComplaintType::withoutGlobalScopes()->updateOrCreate(
                ['name' => $name, 'garage_id' => $garage->id],
                ['company_id' => $garage->company_id]
            );
        }
    }

    private function createWarehouseItems(Garage $garage): void
    {
        $items = [
            ['code' => 'W-FLT-001', 'name' => 'Oil Filter', 'qty' => 45, 'min' => 10, 'price' => 15.50, 'unit' => 'piece'],
            ['code' => 'W-FLT-002', 'name' => 'Air Filter', 'qty' => 32, 'min' => 10, 'price' => 22.00, 'unit' => 'piece'],
            ['code' => 'W-FLT-003', 'name' => 'Fuel Filter', 'qty' => 18, 'min' => 8,  'price' => 30.75, 'unit' => 'piece'],
            ['code' => 'W-OIL-001', 'name' => 'Engine Oil 15W40', 'qty' => 120, 'min' => 30, 'price' => 8.50, 'unit' => 'liter'],
            ['code' => 'W-OIL-002', 'name' => 'Gearbox Oil', 'qty' => 85, 'min' => 20, 'price' => 12.00, 'unit' => 'liter'],
            ['code' => 'W-BRK-001', 'name' => 'Brake Pads', 'qty' => 24, 'min' => 8,  'price' => 45.00, 'unit' => 'piece'],
            ['code' => 'W-BRK-002', 'name' => 'Brake Disc', 'qty' => 6,  'min' => 8,  'price' => 180.00, 'unit' => 'piece'],
            ['code' => 'W-TIR-001', 'name' => 'Tire 295/80', 'qty' => 12, 'min' => 5,  'price' => 650.00, 'unit' => 'piece'],
            ['code' => 'W-BLT-001', 'name' => 'V-Belt', 'qty' => 30, 'min' => 10, 'price' => 25.00, 'unit' => 'piece'],
            ['code' => 'W-LMP-001', 'name' => 'Headlight Bulb', 'qty' => 3,  'min' => 10, 'price' => 8.00, 'unit' => 'piece'],
            ['code' => 'W-LMP-002', 'name' => 'Indicator Bulb', 'qty' => 4,  'min' => 15, 'price' => 3.50, 'unit' => 'piece'],
            ['code' => 'W-CLN-001', 'name' => 'Coolant', 'qty' => 60, 'min' => 20, 'price' => 6.50, 'unit' => 'liter'],
            ['code' => 'W-WPR-001', 'name' => 'Windshield Wiper', 'qty' => 0,  'min' => 8,  'price' => 18.00, 'unit' => 'piece'],
            ['code' => 'W-BAT-001', 'name' => 'Battery 12V', 'qty' => 5,  'min' => 3,  'price' => 220.00, 'unit' => 'piece'],
            ['code' => 'W-SPR-001', 'name' => 'Spark Plug', 'qty' => 48, 'min' => 20, 'price' => 12.00, 'unit' => 'piece'],
        ];

        foreach ($items as $item) {
            Warehouse::withoutGlobalScopes()->updateOrCreate(
                ['code' => $item['code'], 'garage_id' => $garage->id],
                [
                    'company_id'       => $garage->company_id,
                    'name'             => $item['name'],
                    'quantity'         => $item['qty'],
                    'minimum_quantity' => $item['min'],
                    'price'            => $item['price'],
                    'unit'             => $item['unit'],
                ]
            );
        }
    }

    private function createBuses(Garage $garage): void
    {
        $projects = ['BakuBus', 'Azerbaijan Automobile'];
        $routes = ['101', '105', '120', '154', '175', '201', 'M-5', 'M-7', 'M-9', 'M-12'];

        for ($i = 1; $i <= 10; $i++) {
            $garageCode = $garage->code === 'DEMO-GAR-001' ? 'G1' : 'G2';
            $dqn = sprintf('90-%s-%03d', $garageCode, $i);

            Bus::withoutGlobalScopes()->updateOrCreate(
                ['dqn' => $dqn, 'garage_id' => $garage->id],
                [
                    'company_id'    => $garage->company_id,
                    'bus_project'   => $projects[$i % 2],
                    'vin'           => strtoupper('VIN' . $garage->id . str_pad($i, 11, '0', STR_PAD_LEFT)),
                    'uzunluq'       => 12.5,
                    'route_number'  => $routes[$i % count($routes)],
                    'engine_number' => 'ENG-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                    'km'            => 100000 + ($i * 15000),
                    'is_active'     => $i <= 8,
                ]
            );
        }
    }

    private function createEmployees(Garage $garage): void
    {
        $names = [
            ['Elşad', 'Məmmədov', 'master'],
            ['Rəşad', 'Əliyev', 'mechanic'],
            ['Vüqar', 'Həsənov', 'electrician'],
            ['Kamran', 'İbrahimov', 'welder'],
            ['Fuad', 'Rəhimov', 'master'],
            ['Namiq', 'Süleymanov', 'mechanic'],
            ['Ramil', 'Quliyev', 'painter'],
            ['Cavid', 'Abbasov', 'driver'],
        ];

        foreach ($names as [$first, $last, $pos]) {
            Employee::withoutGlobalScopes()->updateOrCreate(
                ['first_name' => $first, 'last_name' => $last, 'garage_id' => $garage->id],
                [
                    'company_id' => $garage->company_id,
                    'position'   => $pos,
                    'is_active'  => true,
                ]
            );
        }
    }

    private function createDrivers(Garage $garage): void
    {
        $names = [
            ['Elşad', 'Həsənov'],
            ['Fərhad', 'Quliyev'],
            ['Samir', 'Nəsirov'],
            ['Vüsal', 'Babayev'],
            ['Ramin', 'Əkbərov'],
            ['Cavid', 'Məcidov'],
            ['Tural', 'Yusifov'],
            ['Emin', 'Xəlilov'],
        ];

        foreach ($names as $i => [$first, $last]) {
            $garageCode = $garage->code === 'DEMO-GAR-001' ? 'G1' : 'G2';
            $code = sprintf('D-%s-%03d', $garageCode, $i + 1);

            Driver::withoutGlobalScopes()->updateOrCreate(
                ['code' => $code, 'garage_id' => $garage->id],
                [
                    'company_id' => $garage->company_id,
                    'first_name' => $first,
                    'last_name'  => $last,
                    'phone'      => '+994 50 ' . rand(100, 999) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                    'position'   => 'Baş Sürücü',
                    'is_active'  => true,
                ]
            );
        }
    }

    private function createDailyKmRecords(Garage $garage): void
    {
        $buses = Bus::withoutGlobalScopes()
            ->where('garage_id', $garage->id)
            ->where('is_active', true)
            ->get();

        // 30 days of records
        for ($day = 30; $day >= 0; $day--) {
            $date = now()->subDays($day);

            // Skip weekends occasionally to make "missing" report meaningful
            if ($date->isSunday()) {
                continue;
            }

            foreach ($buses as $bus) {
                // Small chance to skip → "missing" report will show some buses
                if (rand(1, 100) <= 10) {
                    continue;
                }

                $kmBase = 100000 + ($bus->id * 15000);
                $km = $kmBase + (30 - $day) * rand(150, 350);

                DailyKmRecord::withoutGlobalScopes()->updateOrCreate(
                    [
                        'bus_id' => $bus->id,
                        'date'   => $date->toDateString(),
                    ],
                    [
                        'garage_id'  => $garage->id,
                        'company_id' => $garage->company_id,
                        'km'         => $km,
                        'notes'      => null,
                    ]
                );
            }
        }
    }

    private function createDailyStatuses(Garage $garage): void
    {
        $buses = Bus::withoutGlobalScopes()
            ->where('garage_id', $garage->id)
            ->where('is_active', true)
            ->get();

        $statuses = [
            'READY FOR ROUTE',
            'READY FOR ROUTE',
            'READY FOR ROUTE',
            'IN MAINTENANCE',
            'OUT OF SERVICE',
        ];

        for ($day = 30; $day >= 0; $day--) {
            $date = now()->subDays($day);

            foreach ($buses as $bus) {
                // Only create a few statuses per day (not all buses)
                if (rand(1, 100) > 40) {
                    continue;
                }

                \App\Models\BusDailyStatus::withoutGlobalScopes()->updateOrCreate(
                    [
                        'bus_id' => $bus->id,
                        'date'   => $date->toDateString(),
                    ],
                    [
                        'garage_id'  => $garage->id,
                        'company_id' => $garage->company_id,
                        'status'     => $statuses[array_rand($statuses)],
                        'notes'      => null,
                    ]
                );
            }
        }
    }

    private function createComplaints(Garage $garage): void
    {
        $buses = Bus::withoutGlobalScopes()
            ->where('garage_id', $garage->id)
            ->get();

        $types = ['breakdown', 'accident', 'maintenance'];
        $statuses = ['pending', 'in_progress', 'completed'];
        $descriptions = [
            'Engine noise at high RPM',
            'Tire puncture on rear left',
            'Brake pedal feels soft',
            'Left headlight not working',
            'Gearbox slipping on 3rd gear',
            'Air conditioner not cooling',
            'Oil leak under engine',
            'Suspension noise on bumps',
        ];

        // Get a complaint worker for this garage
        $worker = User::whereHas('garages', function ($q) use ($garage) {
            $q->where('garage_id', $garage->id)
                ->where('role', 'complaint_worker');
        })->first();

        for ($i = 1; $i <= 15; $i++) {
            $createdAt = now()->subDays(rand(0, 25))->subHours(rand(0, 23));
            $status = $statuses[array_rand($statuses)];

            $complaint = Complaint::withoutGlobalScopes()->create([
                'bus_id'         => $buses->random()->id,
                'garage_id'      => $garage->id,
                'company_id'     => $garage->company_id,
                'created_by'     => $worker?->id,
                'yer'            => rand(0, 1) ? 'garage' : 'road',
                'status'         => $status,
                'complaint_type' => $types[array_rand($types)],
                'km'             => rand(100000, 250000),
                'start_date'     => $createdAt->toDateString(),
                'start_time'     => $createdAt->format('H:i'),
                'closed_at'      => $status === 'completed' ? $createdAt->copy()->addHours(rand(2, 72)) : null,
                'end_date'       => $status === 'completed' ? $createdAt->copy()->addHours(rand(2, 72))->toDateString() : null,
                'end_time'       => $status === 'completed' ? $createdAt->copy()->addHours(rand(2, 72))->format('H:i') : null,
                'work_done_by'   => $status === 'completed' ? 'Standard repair completed.' : null,
            ]);

            // Force created_at to be realistic
            DB::table('complaints')
                ->where('id', $complaint->id)
                ->update(['created_at' => $createdAt]);

            // Add 1-2 complaint items
            $itemCount = rand(1, 2);
            for ($j = 0; $j < $itemCount; $j++) {
                $complaint->items()->create([
                    'description' => $descriptions[array_rand($descriptions)],
                    'type'        => $complaint->complaint_type,
                ]);
            }
        }
    }
}
