<?php

namespace Tests\Feature\Seeders;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use Database\Seeders\GarageSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GarageSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function seedAll(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(GarageSeeder::class);
    }

    // ==================================================================
    // 1. STRUKTUR — 2 company, 4 qaraj
    // ==================================================================

    public function test_seeds_two_companies_and_four_garages(): void
    {
        $this->seedAll();

        $this->assertSame(2, Company::count());
        $this->assertSame(4, Garage::count());
    }

    // ==================================================================
    // 2. HƏR ŞİRKƏT ÜÇÜN 1 AKTIV DIRECTOR
    // ==================================================================

    public function test_every_company_gets_exactly_one_active_director(): void
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
    // 3. HƏR QARAJ ÜÇÜN 1 AKTIV ADMIN
    // ==================================================================

    public function test_every_garage_gets_exactly_one_active_admin(): void
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
    // 4. SUPER ADMIN — HEÇ BİR QARAJA BAĞLANMAYIB
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
            'SuperAdmin must NOT be attached to any garage (platform-level role)'
        );
    }

    // ==================================================================
    // 5. DIRECTOR USER — role = 'user' (qlobal), pivot = 'director'
    // ==================================================================

    public function test_director_users_have_global_role_user(): void
    {
        $this->seedAll();

        $director = User::where('email', 'director.bakubus@fleet.com')->first();

        $this->assertNotNull($director);
        $this->assertSame('user', $director->role);
        $this->assertTrue($director->isDirector());
    }

    public function test_garage_admin_users_have_global_role_user(): void
    {
        $this->seedAll();

        $admin = User::where('email', 'admin.gar1@fleet.com')->first();

        $this->assertNotNull($admin);
        $this->assertSame('user', $admin->role);
    }

    // ==================================================================
    // 6. IDEMPOTENT — 2 dəfə işlətmək problem yaratmır
    // ==================================================================

    public function test_seeder_is_idempotent(): void
    {
        $this->seedAll();
        $this->seed(GarageSeeder::class); // twice

        $this->assertSame(2, Company::count(), 'Re-running must not duplicate companies');
        $this->assertSame(4, Garage::count(), 'Re-running must not duplicate garages');

        // 1 super admin + 2 directors + 4 admins = 7 users
        $this->assertSame(7, User::count(), 'Re-running must not duplicate users');
    }

    // ==================================================================
    // 7. HƏR QARAJIN ÖZ ADMİNİ VAR (eyni adam deyil)
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
