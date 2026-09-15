<?php

namespace Tests\Feature\Seeders;

use App\Models\Bus;
use App\Models\Company;
use App\Models\ComplaintType;
use App\Models\Garage;
use App\Models\MotorOilDetail;
use App\Models\ServiceTemplate;
use App\Models\User;
use App\Services\GarageContext;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function seedAll(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(DemoDataSeeder::class);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    // ==================================================================
    // 1. STRUCTURE — 2 companies, 6 garages
    // ==================================================================

    public function test_seeds_two_companies_and_six_garages(): void
    {
        $this->seedAll();

        $this->assertSame(2, Company::count());
        $this->assertSame(6, Garage::count());
    }

    public function test_every_company_has_three_garages(): void
    {
        $this->seedAll();

        foreach (Company::all() as $company) {
            $this->assertSame(
                3,
                $company->garages()->count(),
                "Company [{$company->name}] must have exactly 3 garages"
            );
        }
    }

    // ==================================================================
    // 2. DIRECTORS — one per company
    // ==================================================================

    public function test_every_company_has_exactly_one_active_director(): void
    {
        $this->seedAll();

        foreach (Company::all() as $company) {
            $this->assertSame(
                1,
                $company->directors()->count(),
                "Company [{$company->name}] must have exactly 1 active director"
            );
        }
    }

    // ==================================================================
    // 3. GARAGE ADMINS — one per garage
    // ==================================================================

    public function test_every_garage_has_exactly_one_active_admin(): void
    {
        $this->seedAll();

        foreach (Garage::all() as $garage) {
            $activeAdmins = $garage->users()
                ->wherePivot('role', 'admin')
                ->wherePivot('is_active', true)
                ->count();

            $this->assertSame(
                1,
                $activeAdmins,
                "Garage [{$garage->name}] must have exactly 1 active admin"
            );
        }
    }

    // ==================================================================
    // 4. SUPER ADMIN — not attached to any garage
    // ==================================================================

    public function test_super_admin_is_not_attached_to_any_garage(): void
    {
        $this->seedAll();

        $superAdmin = User::where('email', 'admin@fleet.com')->first();

        $this->assertNotNull($superAdmin);
        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertSame(
            0,
            $superAdmin->garages()->count(),
            'SuperAdmin must NOT be attached to any garage'
        );
    }

    // ==================================================================
    // 5. PER-GARAGE DATA — every garage has the expected catalog
    // ==================================================================

    public function test_every_garage_has_complaint_types(): void
    {
        $this->seedAll();

        foreach (Garage::all() as $garage) {
            $count = ComplaintType::withoutGlobalScopes()
                ->where('garage_id', $garage->id)
                ->count();

            $this->assertSame(
                10,
                $count,
                "Garage [{$garage->name}] must have 10 complaint types"
            );
        }
    }

    public function test_every_garage_has_motor_oil_details(): void
    {
        $this->seedAll();

        foreach (Garage::all() as $garage) {
            $count = MotorOilDetail::withoutGlobalScopes()
                ->where('garage_id', $garage->id)
                ->count();

            $this->assertGreaterThanOrEqual(
                15,
                $count,
                "Garage [{$garage->name}] must have at least 15 motor-oil detail rows"
            );
        }
    }

    public function test_every_garage_has_service_templates(): void
    {
        $this->seedAll();

        foreach (Garage::all() as $garage) {
            $count = ServiceTemplate::withoutGlobalScopes()
                ->where('garage_id', $garage->id)
                ->count();

            $this->assertSame(
                5,
                $count,
                "Garage [{$garage->name}] must have 5 service templates"
            );
        }
    }

    public function test_every_garage_has_buses(): void
    {
        $this->seedAll();

        foreach (Garage::all() as $garage) {
            $count = Bus::withoutGlobalScopes()
                ->where('garage_id', $garage->id)
                ->count();

            $this->assertSame(
                5,
                $count,
                "Garage [{$garage->name}] must have 5 buses"
            );
        }
    }

    // ==================================================================
    // 6. IDEMPOTENCY — re-running does not duplicate data
    // ==================================================================

    public function test_seeder_is_idempotent(): void
    {
        $this->seedAll();
        $this->seed(DemoDataSeeder::class); // second time

        $this->assertSame(2, Company::count(), 'Re-running must not duplicate companies');
        $this->assertSame(6, Garage::count(), 'Re-running must not duplicate garages');
        $this->assertSame(2, Company::all()->sum(fn ($c) => $c->directors()->count()),
            'Re-running must not duplicate directors');
    }

    // ==================================================================
    // 7. TENANT ISOLATION — each garage has its own data
    // ==================================================================

    public function test_each_garage_has_a_distinct_admin(): void
    {
        $this->seedAll();

        $adminIds = [];
        foreach (Garage::all() as $garage) {
            $admin = $garage->users()
                ->wherePivot('role', 'admin')
                ->wherePivot('is_active', true)
                ->first();

            $this->assertNotNull($admin, "Garage [{$garage->name}] has no active admin");
            $adminIds[] = $admin->id;
        }

        $this->assertSame(
            count($adminIds),
            count(array_unique($adminIds)),
            'Each garage must have a DIFFERENT admin'
        );
    }
}
