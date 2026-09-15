<?php

namespace Database\Seeders;

use App\Models\Bus;
use App\Models\BusDailyStatus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\ComplaintType;
use App\Models\DailyKmRecord;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\Garage;
use App\Models\MotorOilDetail;
use App\Models\ServiceTemplate;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\GarageContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Development-only demo data.
 *
 * Creates TWO companies with different purposes:
 *
 *   1. "Demo Company"  → FULLY SEEDED
 *      Every garage has the full catalog: buses, warehouse items,
 *      motor-oil details, service templates, employees, drivers,
 *      30 days of KM records and daily statuses, and complaints.
 *      This is the sandbox you can freely destroy and re-create by
 *      running `php artisan migrate:fresh --seed`.
 *
 *   2. "LEGACY MOTOR and SERVICE"  → STRUCTURE ONLY
 *      The company, its garages, directors, garage admins and
 *      default complaint types are created, but NO domain data.
 *      This is your personal workspace: load your real buses,
 *      warehouse, motor oil, etc. through the UI. Running the
 *      seeder again will NOT touch anything you added here.
 *
 * The split means: seed everything → LEGACY is ready for your
 * real data, Demo Company is ready for tests.
 *
 * ⚠️  Blocked in production. Real tenants are created through
 *     the SuperAdmin UI in production.
 */
class DemoDataSeeder extends Seeder
{
    /**
     * Default complaint types seeded for every garage — including
     * the LEGACY garages, because a new complaint form should work
     * out of the box.
     */
    private const COMPLAINT_TYPES = [
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

    /**
     * Motor-oil service intervals (km) for the DEMO company.
     */
    private const SERVICE_INTERVALS = [15000, 30000, 60000, 90000, 120000];

    /**
     * Employee positions (matches config('settings.employee_positions')).
     */
    private const EMPLOYEE_POSITIONS = ['master', 'mechanic', 'electrician', 'welder'];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('DemoDataSeeder is blocked in production.');

            return;
        }

        $this->command->info('🌱 Seeding demo data...');

        DB::transaction(function () {
            // ─── Company 1: Demo Company (fully seeded) ───
            $demo = $this->createCompany('Demo Company', 'demo-company');
            $this->createDirector($demo, 'Demo Director', 'director@demo.test');

            $this->seedGarage($demo, 'Demo Central Garage', 'demo-central', withDomainData: true);
            $this->seedGarage($demo, 'Demo North Garage', 'demo-north', withDomainData: true);
            $this->seedGarage($demo, 'Demo South Garage', 'demo-south', withDomainData: true);

            // ─── Company 2: LEGACY MOTOR and SERVICE (structure only) ───
            $legacy = $this->createCompany('LEGACY MOTOR and SERVICE', 'legacy-motor');
            $this->createDirector($legacy, 'Rəşad Direktor', 'director@legacy.test');

            $this->seedGarage($legacy, 'Depo1', 'depo1', withDomainData: false);
            $this->seedGarage($legacy, 'Depo2', 'depo2', withDomainData: false);
            $this->seedGarage($legacy, 'DepoGence', 'depogence', withDomainData: false);
        });

        $this->command->info('✅ Demo data seeded successfully.');
        $this->command->newLine();
        $this->command->info('Login credentials (all passwords: password):');
        $this->command->info('  Super Admin    → admin@fleet.com');
        $this->command->info('  Demo Director  → director@demo.test');
        $this->command->info('  Legacy Director→ director@legacy.test');
        $this->command->info('  Garage admins  → {garage.code}.admin@demo.test');
        $this->command->info('  Workers        → PIN 1234');
    }

    // ==================================================================
    // COMPANY & DIRECTOR
    // ==================================================================

    private function createCompany(string $name, string $slug): Company
    {
        return Company::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'email' => "info@{$slug}.test",
                'phone' => '+994 12 '.random_int(100, 999).' '.random_int(10, 99).' '.random_int(10, 99),
                'address' => 'Bakı, Azərbaycan',
                'is_active' => true,
            ]
        );
    }

    private function createDirector(Company $company, string $name, string $email): User
    {
        $director = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'employee_code' => 'DIR-'.strtoupper(substr(md5($email), 0, 6)),
                'pin' => Hash::make('1234'),
                'pin_is_default' => false,
                'is_active' => true,
            ]
        );

        if ($director->role !== 'user') {
            $director->demoteToRegularUser()->save();
        }

        $company->users()->syncWithoutDetaching([
            $director->id => ['role' => 'director', 'is_active' => true],
        ]);

        // Ensure only one active director per company
        $company->users()
            ->wherePivot('role', 'director')
            ->wherePivot('is_active', true)
            ->where('users.id', '!=', $director->id)
            ->get()
            ->each(function (User $other) use ($company) {
                $company->users()->updateExistingPivot($other->id, ['is_active' => false]);
            });

        return $director;
    }

    // ==================================================================
    // GARAGE — the single entry point for seeding a garage
    // ==================================================================

    /**
     * Create (or update) a garage and optionally seed its domain data.
     *
     * When $withDomainData is false, only the structure is created:
     *   - the garage itself
     *   - its admin user (so someone can log in)
     *   - the default complaint-type catalog (so the new-complaint
     *     form works out of the box)
     *
     * No buses, no warehouse, no motor oil, no employees, no drivers,
     * no KM records, no statuses, no complaints.
     *
     * That way, running the seeder again never overwrites the real
     * data the operator has loaded into the LEGACY garages.
     */
    private function seedGarage(
        Company $company,
        string $name,
        string $code,
        bool $withDomainData
    ): Garage {
        $garage = Garage::updateOrCreate(
            ['code' => $code],
            [
                'company_id' => $company->id,
                'name' => $name,
                'address' => 'Bakı, Azərbaycan',
                'phone' => '+994 12 '.random_int(100, 999).' '.random_int(10, 99).' '.random_int(10, 99),
                'is_active' => true,
            ]
        );

        // Set the context once so every nested seeder writes to the
        // right tenant. HasGarageScope and Auditable both rely on it.
        GarageContext::set($garage->id, $company->id);

        $this->command->line("  → Seeding garage: {$garage->name} ({$garage->code})".
            ($withDomainData ? '' : '  [structure only]'));

        // Structure — always seeded.
        $this->createGarageAdmin($garage);
        $this->createComplaintTypes($garage);

        // Domain data — only for the demo company.
        if ($withDomainData) {
            $this->createGarageUsers($garage);
            $this->createMotorOilCatalog($garage);
            $this->createWarehouseItems($garage);
            $this->createBuses($garage);
            $this->createEmployees($garage);
            $this->createDrivers($garage);
            $this->createDailyKmRecords($garage);
            $this->createDailyStatuses($garage);
            $this->createComplaints($garage);
        }

        GarageContext::clear();

        return $garage;
    }

    // ==================================================================
    // PER-GARAGE SEEDERS — STRUCTURE
    // ==================================================================

    private function createGarageAdmin(Garage $garage): void
    {
        $adminEmail = "{$garage->code}.admin@demo.test";

        $admin = User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => "{$garage->name} Admin",
                'password' => Hash::make('password'),
                'employee_code' => 'ADM-'.strtoupper(substr(md5($adminEmail), 0, 6)),
                'pin' => Hash::make('1234'),
                'pin_is_default' => false,
                'is_active' => true,
            ]
        );

        if ($admin->role !== 'user') {
            $admin->demoteToRegularUser()->save();
        }

        // Ensure no other active admin exists in this garage
        // (partial unique index garage_user_single_admin).
        $garage->users()
            ->wherePivot('role', 'admin')
            ->wherePivot('is_active', true)
            ->where('users.id', '!=', $admin->id)
            ->get()
            ->each(function (User $other) use ($garage) {
                $garage->users()->updateExistingPivot($other->id, ['is_active' => false]);
            });

        $garage->users()->syncWithoutDetaching([
            $admin->id => ['role' => 'admin', 'is_active' => true],
        ]);
        $garage->users()->updateExistingPivot($admin->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function createComplaintTypes(Garage $garage): void
    {
        foreach (self::COMPLAINT_TYPES as $name) {
            ComplaintType::withoutGlobalScopes()->updateOrCreate(
                ['name' => $name, 'garage_id' => $garage->id],
                ['company_id' => $garage->company_id]
            );
        }
    }

    // ==================================================================
    // PER-GARAGE SEEDERS — DOMAIN DATA (Demo Company only)
    // ==================================================================

    private function createGarageUsers(Garage $garage): void
    {
        // Managers + Workers: 4 domains × 2 tiers = 8 accounts
        $roleMatrix = [
            'complaint_manager', 'complaint_worker',
            'warehouse_manager', 'warehouse_worker',
            'daily_km_manager', 'daily_km_worker',
            'daily_status_manager', 'daily_status_worker',
        ];

        foreach ($roleMatrix as $role) {
            $slug = str_replace('_', '.', $role);
            $email = "{$garage->code}.{$slug}@demo.test";

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => "{$garage->name} ".ucwords(str_replace('_', ' ', $role)),
                    'password' => Hash::make('password'),
                    'employee_code' => strtoupper(substr($role, 0, 3)).'-'.
                        strtoupper(substr(md5($email), 0, 6)),
                    'pin' => Hash::make('1234'),
                    'pin_is_default' => false,
                    'is_active' => true,
                ]
            );

            if ($user->role !== 'user') {
                $user->demoteToRegularUser()->save();
            }

            $garage->users()->syncWithoutDetaching([
                $user->id => ['role' => $role, 'is_active' => true],
            ]);
        }
    }

    private function createMotorOilCatalog(Garage $garage): void
    {
        foreach (self::SERVICE_INTERVALS as $km) {
            $parts = [
                ['code' => "OIL-FLT-{$km}", 'name' => "Oil filter ({$km} km)", 'qty' => 1, 'unit' => 'piece'],
                ['code' => "OIL-15W40-{$km}", 'name' => "Engine oil 15W40 ({$km} km)", 'qty' => 18, 'unit' => 'liter'],
                ['code' => "OIL-GSK-{$km}", 'name' => "Gasket ({$km} km)", 'qty' => 1, 'unit' => 'piece'],
            ];

            foreach ($parts as $part) {
                MotorOilDetail::updateOrCreate(
                    [
                        'garage_id' => $garage->id,
                        'part_code' => $part['code'],
                        'km' => $km,
                    ],
                    [
                        'company_id' => $garage->company_id,
                        'part_name' => $part['name'],
                        'unit' => $part['unit'],
                        'quantity' => $part['qty'],
                        'count' => 1,
                    ]
                );
            }

            ServiceTemplate::updateOrCreate(
                [
                    'garage_id' => $garage->id,
                    'default_km_interval' => $km,
                ],
                [
                    'company_id' => $garage->company_id,
                    'name' => "Motor Oil Change ({$km} km)",
                    'details' => collect($parts)->map(fn ($p) => [
                        'code' => $p['code'],
                        'name' => $p['name'],
                        'quantity' => $p['qty'],
                        'unit' => $p['unit'],
                    ])->toArray(),
                ]
            );
        }
    }

    private function createWarehouseItems(Garage $garage): void
    {
        $items = [
            ['code' => 'W-FLT-001', 'name' => 'Oil Filter', 'qty' => 45, 'min' => 10, 'price' => 15.50, 'unit' => 'piece'],
            ['code' => 'W-FLT-002', 'name' => 'Air Filter', 'qty' => 32, 'min' => 10, 'price' => 22.00, 'unit' => 'piece'],
            ['code' => 'W-FLT-003', 'name' => 'Fuel Filter', 'qty' => 18, 'min' => 8, 'price' => 30.75, 'unit' => 'piece'],
            ['code' => 'W-OIL-001', 'name' => 'Engine Oil 15W40', 'qty' => 120, 'min' => 30, 'price' => 8.50, 'unit' => 'liter'],
            ['code' => 'W-OIL-002', 'name' => 'Gearbox Oil', 'qty' => 85, 'min' => 20, 'price' => 12.00, 'unit' => 'liter'],
            ['code' => 'W-BRK-001', 'name' => 'Brake Pads', 'qty' => 24, 'min' => 8, 'price' => 45.00, 'unit' => 'piece'],
            ['code' => 'W-BRK-002', 'name' => 'Brake Disc', 'qty' => 6, 'min' => 8, 'price' => 180.00, 'unit' => 'piece'],
            ['code' => 'W-TIR-001', 'name' => 'Tire 295/80', 'qty' => 12, 'min' => 5, 'price' => 650.00, 'unit' => 'piece'],
            ['code' => 'W-BLT-001', 'name' => 'V-Belt', 'qty' => 30, 'min' => 10, 'price' => 25.00, 'unit' => 'piece'],
            ['code' => 'W-LMP-001', 'name' => 'Headlight Bulb', 'qty' => 3, 'min' => 10, 'price' => 8.00, 'unit' => 'piece'],
        ];

        foreach ($items as $item) {
            Warehouse::withoutGlobalScopes()->updateOrCreate(
                ['code' => $item['code'], 'garage_id' => $garage->id],
                [
                    'company_id' => $garage->company_id,
                    'name' => $item['name'],
                    'quantity' => $item['qty'],
                    'minimum_quantity' => $item['min'],
                    'price' => $item['price'],
                    'unit' => $item['unit'],
                ]
            );
        }
    }

    private function createBuses(Garage $garage): void
    {
        $projects = ['BakuBus', 'Azerbaijan Automobile'];
        $routes = ['101', '105', '120', '154', '175'];

        for ($i = 1; $i <= 5; $i++) {
            $dqn = sprintf('%s-%03d', strtoupper($garage->code), $i);

            Bus::withoutGlobalScopes()->updateOrCreate(
                ['dqn' => $dqn, 'garage_id' => $garage->id],
                [
                    'company_id' => $garage->company_id,
                    'bus_project' => $projects[$i % count($projects)],
                    'vin' => strtoupper('VIN'.$garage->id.str_pad((string) $i, 11, '0', STR_PAD_LEFT)),
                    'uzunluq' => 12.5,
                    'route_number' => $routes[$i % count($routes)],
                    'engine_number' => 'ENG-'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                    'km' => 100000 + ($i * 15000),
                    'is_active' => true,
                ]
            );
        }
    }

    private function createEmployees(Garage $garage): void
    {
        $names = [
            ['Elşad', 'Məmmədov'],
            ['Rəşad', 'Əliyev'],
            ['Vüqar', 'Həsənov'],
            ['Kamran', 'İbrahimov'],
        ];

        foreach ($names as $i => [$first, $last]) {
            $code = sprintf('EMP-%s-%03d', strtoupper($garage->code), $i + 1);

            Employee::withoutGlobalScopes()->updateOrCreate(
                ['code' => $code, 'garage_id' => $garage->id],
                [
                    'company_id' => $garage->company_id,
                    'first_name' => $first,
                    'last_name' => $last,
                    'position' => self::EMPLOYEE_POSITIONS[$i % count(self::EMPLOYEE_POSITIONS)],
                    'is_active' => true,
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
        ];

        foreach ($names as $i => [$first, $last]) {
            $code = sprintf('DRV-%s-%03d', strtoupper($garage->code), $i + 1);

            Driver::withoutGlobalScopes()->updateOrCreate(
                ['code' => $code, 'garage_id' => $garage->id],
                [
                    'company_id' => $garage->company_id,
                    'first_name' => $first,
                    'last_name' => $last,
                    'phone' => '+994 50 '.random_int(100, 999).' '.random_int(10, 99).' '.random_int(10, 99),
                    'position' => 'Baş Sürücü',
                    'is_active' => true,
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

        if ($buses->isEmpty()) {
            return;
        }

        for ($day = 30; $day >= 0; $day--) {
            $date = now()->subDays($day);

            if ($date->isSunday()) {
                continue;
            }

            foreach ($buses as $bus) {
                if (random_int(1, 100) <= 10) {
                    continue;
                }

                $kmBase = 100000 + ($bus->id * 15000);
                $km = $kmBase + ((30 - $day) * random_int(150, 350));

                DailyKmRecord::withoutGlobalScopes()->updateOrCreate(
                    [
                        'bus_id' => $bus->id,
                        'date' => $date->toDateString(),
                    ],
                    [
                        'garage_id' => $garage->id,
                        'company_id' => $garage->company_id,
                        'km' => $km,
                        'notes' => null,
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

        if ($buses->isEmpty()) {
            return;
        }

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
                if (random_int(1, 100) > 40) {
                    continue;
                }

                BusDailyStatus::withoutGlobalScopes()->updateOrCreate(
                    [
                        'bus_id' => $bus->id,
                        'date' => $date->toDateString(),
                    ],
                    [
                        'garage_id' => $garage->id,
                        'company_id' => $garage->company_id,
                        'status' => $statuses[array_rand($statuses)],
                        'notes' => null,
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

        if ($buses->isEmpty()) {
            return;
        }

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

        $worker = User::whereHas('garages', function ($q) use ($garage) {
            $q->where('garage_id', $garage->id)->where('role', 'complaint_worker');
        })->first();

        for ($i = 1; $i <= 10; $i++) {
            $createdAt = now()->subDays(random_int(0, 25))->subHours(random_int(0, 23));
            $status = $statuses[array_rand($statuses)];
            $closedAt = $status === 'completed' ? $createdAt->copy()->addHours(random_int(2, 72)) : null;

            $complaint = Complaint::withoutGlobalScopes()->create([
                'bus_id' => $buses->random()->id,
                'garage_id' => $garage->id,
                'company_id' => $garage->company_id,
                'created_by' => $worker?->id,
                'yer' => random_int(0, 1) ? 'garage' : 'road',
                'status' => $status,
                'complaint_type' => $types[array_rand($types)],
                'km' => random_int(100000, 250000),
                'start_date' => $createdAt->toDateString(),
                'start_time' => $createdAt->format('H:i'),
                'closed_at' => $closedAt,
                'end_date' => $closedAt?->toDateString(),
                'end_time' => $closedAt?->format('H:i'),
                'work_done_by' => $status === 'completed' ? 'Standard repair completed.' : null,
            ]);

            DB::table('complaints')
                ->where('id', $complaint->id)
                ->update(['created_at' => $createdAt]);

            for ($j = 0; $j < random_int(1, 2); $j++) {
                $complaint->items()->create([
                    'description' => $descriptions[array_rand($descriptions)],
                    'type' => $complaint->complaint_type?->value,
                    'garage_id' => $garage->id,
                    'company_id' => $garage->company_id,
                ]);
            }
        }
    }
}
