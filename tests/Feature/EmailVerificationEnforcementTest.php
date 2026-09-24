<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use App\Services\Onboarding\CompanyOnboardingService;
use App\Services\Onboarding\GarageOnboardingService;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $this->company->id]);

        GarageContext::set($this->garage->id, $this->company->id);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    protected function garageSession(): array
    {
        return [
            'current_garage_id' => $this->garage->id,
            'current_company_id' => $this->company->id,
        ];
    }

    // ==================================================================
    // 1. UNVERIFIED USER IS BLOCKED FROM BUSINESS ROUTES
    // ==================================================================

    public function test_unverified_user_cannot_access_dashboard(): void
    {
        $user = User::factory()->unverified()->create(['role' => 'user']);
        $user->garages()->attach($this->garage->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->withSession($this->garageSession())
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_user_cannot_access_complaints(): void
    {
        $user = User::factory()->unverified()->create(['role' => 'user']);
        $user->garages()->attach($this->garage->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->withSession($this->garageSession())
            ->get(route('complaints.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_user_cannot_access_buses(): void
    {
        $user = User::factory()->unverified()->create(['role' => 'user']);
        $user->garages()->attach($this->garage->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->withSession($this->garageSession())
            ->get(route('buses.index'))
            ->assertRedirect(route('verification.notice'));
    }

    // ==================================================================
    // 2. VERIFIED USER CAN ACCESS BUSINESS ROUTES
    // ==================================================================

    public function test_verified_user_can_access_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'user']); // verified by default
        $user->garages()->attach($this->garage->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->withSession($this->garageSession())
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_verified_user_can_access_complaints(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $user->garages()->attach($this->garage->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->withSession($this->garageSession())
            ->get(route('complaints.index'))
            ->assertOk();
    }

    // ==================================================================
    // 3. ONBOARDING SERVICES AUTO-VERIFY
    // ==================================================================

    public function test_company_onboarding_creates_verified_director(): void
    {
        $service = app(CompanyOnboardingService::class);

        $service->createWithDirector(
            companyData: [
                'name' => 'Test Co',
                'slug' => 'test-co-verify',
                'is_active' => true,
            ],
            directorData: [
                'name' => 'Test Director',
                'email' => 'director-test@verify.com',
                'password' => 'Str0ngPass!',
                'pin' => '1234',
            ],
        );

        $director = User::where('email', 'director-test@verify.com')->first();

        $this->assertNotNull($director, 'Director must be created');
        $this->assertNotNull(
            $director->email_verified_at,
            'Company onboarding must auto-verify the director'
        );
    }

    public function test_garage_onboarding_creates_verified_admin(): void
    {
        $service = app(GarageOnboardingService::class);

        $service->createWithAdmin(
            garageData: [
                'company_id' => $this->company->id,
                'name' => 'Test Garage',
                'code' => 'TG-AUTO-VERIFY',
                'is_active' => true,
            ],
            adminData: [
                'name' => 'Test Admin',
                'email' => 'admin-test@verify.com',
                'password' => 'Str0ngPass!',
                'pin' => '1234',
            ],
        );

        $admin = User::where('email', 'admin-test@verify.com')->first();

        $this->assertNotNull($admin, 'Admin must be created');
        $this->assertNotNull(
            $admin->email_verified_at,
            'Garage onboarding must auto-verify the admin'
        );
    }

    public function test_user_service_creates_verified_worker(): void
    {
        $service = app(UserService::class);

        $user = $service->createUserWithGarageRole(
            data: [
                'name' => 'Test Worker',
                'email' => 'worker-test@verify.com',
                'password' => 'password123',
                'role' => 'warehouse_worker',
            ],
            garageId: $this->garage->id,
        );

        $this->assertNotNull($user->email_verified_at);
    }

    // ==================================================================
    // 4. VERIFICATION NOTICE PAGE REMAINS REACHABLE
    // ==================================================================

    public function test_unverified_user_can_reach_verification_notice(): void
    {
        $user = User::factory()->unverified()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk();
    }

    // ==================================================================
    // 5. SUPER ADMIN AND DIRECTOR PATHS ARE UNAFFECTED
    // ==================================================================

    public function test_super_admin_path_not_gated_by_verified(): void
    {
        // SuperAdmin route-ları `auth`+`super.admin` ilə qorunur,
        // `verified` middleware onlara tətbiq olunmur. Bu test
        // regression qorumasıdır.
        $sa = User::factory()->unverified()->create(['role' => 'super_admin']);

        // SuperAdmin MFA setup-a yönləndirilir, `verification.notice`-a yox.
        $this->actingAs($sa)
            ->get(route('super-admin.dashboard'))
            ->assertRedirect(route('super-admin.security.2fa.setup'));
    }
}
