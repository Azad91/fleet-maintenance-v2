<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\Onboarding\GarageOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\MakesSuperAdminWithMfa;

class GarageOnboardingTest extends TestCase
{
    use MakesSuperAdminWithMfa;
    use RefreshDatabase;

    protected User $superAdmin;

    protected Company $company;

    protected Garage $existingGarage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->existingGarage = Garage::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->superAdmin = $this->makeSuperAdminWithMfa();    }

    protected function asSuperAdmin(): self
    {
        return $this->actingAs($this->superAdmin)->withSession([
            'current_garage_id' => $this->existingGarage->id,
            'current_company_id' => $this->company->id,
        ]);
    }

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'company_id' => $this->company->id,
            'name' => 'Central Garage',
            'code' => 'GAR-NEW-001',
            'address' => 'Baku, Azerbaijan',
            'phone' => '+994 12 555 00 00',
            'is_active' => 1,
            'admin_name' => 'Elshad Mammadov',
            'admin_email' => 'elshad@garage.test',
            'admin_password' => 'Str0ngPass!',
            'admin_password_confirmation' => 'Str0ngPass!',
            'admin_pin' => '1234',
            'admin_pin_confirmation' => '1234',
        ], $overrides);
    }

    // ==================================================================
    // 1. HAPPY PATH — Garage + Admin birlikdə yaranır
    // ==================================================================

    public function test_super_admin_creates_garage_with_admin_in_one_request(): void
    {
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.garages.store'), $this->validPayload());

        $response->assertRedirect();

        $garage = Garage::where('code', 'GAR-NEW-001')->first();
        $this->assertNotNull($garage);

        $admin = User::where('email', 'elshad@garage.test')->first();
        $this->assertNotNull($admin);
        $this->assertSame('user', $admin->role);
        $this->assertTrue($admin->pin_is_default);
        $this->assertStringStartsWith('ADM-', $admin->employee_code);

        // Pivot yoxlaması
        $pivot = $garage->users()->whereKey($admin->id)->first();
        $this->assertNotNull($pivot);
        $this->assertSame('admin', $pivot->pivot->role);
        $this->assertTrue((bool) $pivot->pivot->is_active);
    }

    // ==================================================================
    // 2. ADMIN FIELDS MƏCBURİDİR
    // ==================================================================

    public function test_garage_cannot_be_created_without_admin_name(): void
    {
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.garages.store'), $this->validPayload([
                'admin_name' => '',
            ]));

        $response->assertSessionHasErrors('admin_name');
        $this->assertDatabaseMissing('garages', ['code' => 'GAR-NEW-001']);
    }

    public function test_garage_cannot_be_created_without_admin_email(): void
    {
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.garages.store'), $this->validPayload([
                'admin_email' => '',
            ]));

        $response->assertSessionHasErrors('admin_email');
        $this->assertDatabaseMissing('garages', ['code' => 'GAR-NEW-001']);
    }

    public function test_garage_cannot_be_created_without_admin_password(): void
    {
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.garages.store'), $this->validPayload([
                'admin_password' => '',
                'admin_password_confirmation' => '',
            ]));

        $response->assertSessionHasErrors('admin_password');
    }

    public function test_garage_cannot_be_created_without_admin_pin(): void
    {
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.garages.store'), $this->validPayload([
                'admin_pin' => '',
                'admin_pin_confirmation' => '',
            ]));

        $response->assertSessionHasErrors('admin_pin');
    }

    public function test_admin_pin_must_be_4_to_6_digits(): void
    {
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.garages.store'), $this->validPayload([
                'admin_pin' => 'abc',
                'admin_pin_confirmation' => 'abc',
            ]));

        $response->assertSessionHasErrors('admin_pin');
    }

    public function test_admin_pin_must_match_confirmation(): void
    {
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.garages.store'), $this->validPayload([
                'admin_pin' => '1234',
                'admin_pin_confirmation' => '5678',
            ]));

        $response->assertSessionHasErrors('admin_pin');
    }

    public function test_admin_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'elshad@garage.test']);

        $response = $this->asSuperAdmin()
            ->post(route('super-admin.garages.store'), $this->validPayload());

        $response->assertSessionHasErrors('admin_email');
    }

    public function test_garage_code_must_be_unique(): void
    {
        Garage::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'GAR-NEW-001',
        ]);

        $response = $this->asSuperAdmin()
            ->post(route('super-admin.garages.store'), $this->validPayload());

        $response->assertSessionHasErrors('code');
    }

    // ==================================================================
    // 3. TRANSACTION ATOMİKLİYİ (service-i birbaşa test edir)
    // ==================================================================

    public function test_garage_is_not_created_if_admin_creation_fails(): void
    {
        // Admin (User) sətri Garage sətrindən SONRA yaradılır, eyni
        // transaction içində. `eloquent.creating` event-i ilə User
        // yaradılmasını bloklayırıq — transaction rollback baş verməli,
        // əvvəl yaradılmış Garage sətri də silinməlidir.
        Event::listen(
            'eloquent.creating: '.User::class,
            function () {
                throw new \RuntimeException('Simulated admin creation failure');
            }
        );

        $service = app(GarageOnboardingService::class);

        try {
            $service->createWithAdmin(
                garageData: [
                    'company_id' => $this->company->id,
                    'name' => 'Central Garage',
                    'code' => 'GAR-NEW-001',
                    'is_active' => true,
                ],
                adminData: [
                    'name' => 'Elshad Mammadov',
                    'email' => 'elshad@garage.test',
                    'password' => 'Str0ngPass!',
                    'pin' => '1234',
                ],
            );
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated admin creation failure', $e->getMessage());
        }

        // Nə Garage, nə Admin yaranmamalıdır.
        $this->assertDatabaseMissing('garages', ['code' => 'GAR-NEW-001']);
        $this->assertDatabaseMissing('users', ['email' => 'elshad@garage.test']);
    }

    // ==================================================================
    // 4. YALNIZ SUPER ADMIN
    // ==================================================================

    public function test_regular_user_cannot_create_garage(): void
    {
        $regular = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($regular)
            ->withSession([
                'current_garage_id' => $this->existingGarage->id,
                'current_company_id' => $this->company->id,
            ])
            ->post(route('super-admin.garages.store'), $this->validPayload());

        $response->assertForbidden();
        $this->assertDatabaseMissing('garages', ['code' => 'GAR-NEW-001']);
    }

    // ==================================================================
    // 5. ÇOXLU QARAJ — limitsiz
    // ==================================================================

    public function test_company_can_have_multiple_garages(): void
    {
        // İlk qaraj artıq setUp()-də var. İki əlavə yaradaq.
        $this->asSuperAdmin()->post(route('super-admin.garages.store'), $this->validPayload([
            'code' => 'GAR-NEW-002',
            'admin_email' => 'admin2@garage.test',
        ]));

        $this->asSuperAdmin()->post(route('super-admin.garages.store'), $this->validPayload([
            'code' => 'GAR-NEW-003',
            'admin_email' => 'admin3@garage.test',
        ]));

        $this->assertSame(
            3,
            Garage::where('company_id', $this->company->id)->count(),
            'A company should have no limit on the number of garages'
        );
    }
}
