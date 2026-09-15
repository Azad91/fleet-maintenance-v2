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
 * Rich demo data for development and manual testing.
 *
 * Creates:
 *   - 2 companies (LEGACY MOTOR and SERVICE, BAKUBUS)
 *   - 3 garages per company (6 total)
 *   - 1 Director per company
 *   - Per garage: 1 Admin + 8 Managers/Workers, 10 complaint types,
 *     5 motor-oil intervals with service templates, 5 buses,
 *     10 warehouse items, 4 employees, 4 drivers,
 *     30 days of KM records and daily statuses, 10 complaints
 *
 * ⚠️  DO NOT RUN IN PRODUCTION.
 *     The run() method aborts immediately when APP_ENV=production.
 *     This keeps production data clean: only the SuperAdmin is seeded
 *     there, and all real tenants are created through the UI.
 */
class DemoDataSeeder extends Seeder
{
    /**
     * Default complaint types seeded for every garage.
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
     * Motor-oil service intervals (in km) seeded for every garage.
     * Each interval produces one ServiceTemplate and 3 MotorOilDetail
     * rows (filter, oil, gasket) so the whole "maintenance" workflow
     * has real data to work against.
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
            $companyA = $this->createCompany('LEGACY MOTOR and SERVICE', 'legacy-motor');
            $companyB = $this->createCompany('BAKUBUS', 'bakubus');

            $this->createDirector($companyA, 'Rəşad Direktor', 'director@legacy.test');
            $this->createDirector($companyB, 'Elşad Direktor', 'director@bakubus.test');

            $this->createGarage($companyA, 'Depo1', 'depo1');
            $this->createGarage($companyA, 'Depo2', 'depo2');
            $this->createGarage($companyA, 'DepoGence', 'depogence');

            $this->createGarage($companyB, 'Mərkəzi Qaraj', 'merkezi-qaraj');
            $this->createGarage($companyB, 'Sumqayıt Qaraj', 'sumqayit-qaraj');
            $this->createGarage($companyB, 'Xətai Qaraj', 'xetai-qaraj');
        });

        $this->command->info('✅ Demo data seeded successfully.');
        $this->command->newLine();
        $this->command->info('Login credentials:');
        $this->command->info('  Super Admin  → admin@fleet.com / password');
        $this->command->info('  Director A   → director@legacy.test / password');
        $this->command->info('  Director B   → director@bakubus.test / password');
        $this->command->info('  Admin        → {garage.code}.admin@demo.test / password');
        $this->command->info('  Workers      → PIN 1234 (employee code printed per garage)');
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

        // Ensure the director is active on the company pivot
        $company->users()->syncWithoutDetaching([
            $director->id => ['role' => 'director', 'is_active' => true],
        ]);

        // And ensure only one active director exists
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
    // GARAGE — orchestrates every per-garage seeding step
    // ==================================================================

    private function createGarage(Company $company, string $name, string $code): Garage
    {
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

        $this->command->line("  → Seeding garage: {$garage->name} ({$garage->code})");

        $this->createGarageUsers($garage);
        $this->createComplaintTypes($garage);
        $this->createMotorOilCatalog($garage);
        $this->createWarehouseItems($garage);
        $this->createBuses($garage);
        $this->createEmployees($garage);
        $this->createDrivers($garage);
        $this->createDailyKmRecords($garage);
        $this->createDailyStatuses($garage);
        $this->createComplaints($garage);

        GarageContext::clear();

        return $garage;
    }

    // ==================================================================
    // PER-GARAGE SEEDERS
    // ==================================================================

    private function createGarageUsers(Garage $garage): void
    {
        // ── Admin ──
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

        // ── Managers + Workers: 4 domains × 2 tiers = 8 accounts ──
        $roleMatrix = [
            'complaint_manager', 'complaint_worker',
            'warehouse_manager', 'warehouse_worker',
            'daily_km_manager', 'daily_km_worker',
            'daily_status_manager', 'daily_status_worker',
        ];

        foreach ($roleMatrix as $index => $role) {
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

            $this->command->line("     • {$role} → {$email} (PIN: 1234)");
        }
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

    /**
     * Motor-oil catalog + one ServiceTemplate per interval.
     *
     * Templates are derived from the catalog, mirroring the pattern
     * previously implemented by ServiceTemplateSeeder. Because the
     * whole operation runs inside GarageContext, all rows land in the
     * right garage.
     */
    private function createMotorOilCatalog(Garage $garage): void
    {
        foreach (self::SERVICE_INTERVALS as $km) {
            $parts = [
                ['code' => "OIL-FLT-{$km}", 'name' => "Oil filter ({$km} km)", 'qty' => 1,  'unit' => 'piece'],
                ['code' => "OIL-15W40-{$km}", 'name' => "Engine oil 15W40 ({$km} km)", 'qty' => 18, 'unit' => 'liter'],
                ['code' => "OIL-GSK-{$km}", 'name' => "Gasket ({$km} km)", 'qty' => 1,  'unit' => 'piece'],
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

            // One service template per interval
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
            ['code' => 'W-FLT-001', 'name' => 'Oil Filter',         'qty' => 45,  'min' => 10, 'price' => 15.50,  'unit' => 'piece'],
            ['code' => 'W-FLT-002', 'name' => 'Air Filter',         'qty' => 32,  'min' => 10, 'price' => 22.00,  'unit' => 'piece'],
            ['code' => 'W-FLT-003', 'name' => 'Fuel Filter',        'qty' => 18,  'min' => 8,  'price' => 30.75,  'unit' => 'piece'],
            ['code' => 'W-OIL-001', 'name' => 'Engine Oil 15W40',   'qty' => 120, 'min' => 30, 'price' => 8.50,   'unit' => 'liter'],
            ['code' => 'W-OIL-002', 'name' => 'Gearbox Oil',        'qty' => 85,  'min' => 20, 'price' => 12.00,  'unit' => 'liter'],
            ['code' => 'W-BRK-001', 'name' => 'Brake Pads',         'qty' => 24,  'min' => 8,  'price' => 45.00,  'unit' => 'piece'],
            ['code' => 'W-BRK-002', 'name' => 'Brake Disc',         'qty' => 6,   'min' => 8,  'price' => 180.00, 'unit' => 'piece'],
            ['code' => 'W-TIR-001', 'name' => 'Tire 295/80',        'qty' => 12,  'min' => 5,  'price' => 650.00, 'unit' => 'piece'],
            ['code' => 'W-BLT-001', 'name' => 'V-Belt',             'qty' => 30,  'min' => 10, 'price' => 25.00,  'unit' => 'piece'],
            ['code' => 'W-LMP-001', 'name' => 'Headlight Bulb',     'qty' => 3,   'min' => 10, 'price' => 8.00,   'unit' => 'piece'],
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

            // Skip Sundays to make the "missing" report meaningful
            if ($date->isSunday()) {
                continue;
            }

            foreach ($buses as $bus) {
                // 10% chance to skip so the "missing KM" report shows data
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
                // Only create statuses for ~40% of buses per day
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

            // Backdate created_at to match the "start_date"
            DB::table('complaints')
                ->where('id', $complaint->id)
                ->update(['created_at' => $createdAt]);

            // 1-2 complaint items per card
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
