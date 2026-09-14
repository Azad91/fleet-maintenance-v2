<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\MakesSuperAdminWithMfa;

class CompanyOnboardingTest extends TestCase
{
    use MakesSuperAdminWithMfa;
    use RefreshDatabase;

    protected User $superAdmin;

    protected Garage $garage;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $company->id]);

        $this->superAdmin = $this->makeSuperAdminWithMfa();
    }

    protected function asSuperAdmin(): self
    {
        return $this->actingAs($this->superAdmin)->withSession([
            'current_garage_id' => $this->garage->id,
            'current_company_id' => $this->garage->company_id,
        ]);
    }

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Acme Fleet',
            'slug' => 'acme-fleet',
            'email' => 'info@acme.test',
            'is_active' => 1,
            'director_name' => 'Rashad Aliyev',
            'director_email' => 'rashad@acme.test',
            'director_password' => 'Str0ngPass!',
            'director_password_confirmation' => 'Str0ngPass!',
            'director_pin' => '1234',
            'director_pin_confirmation' => '1234',
        ], $overrides);
    }

    // ==================================================================
    // 1. HAPPY PATH — Company + Director birlikdə yaranır
    // ==================================================================

    public function test_super_admin_creates_company_with_director_in_one_request(): void
    {
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.companies.store'), $this->validPayload());

        $response->assertRedirect();

        $company = Company::where('slug', 'acme-fleet')->first();
        $this->assertNotNull($company);

        $director = User::where('email', 'rashad@acme.test')->first();
        $this->assertNotNull($director);
        $this->assertSame('user', $director->role);
        $this->assertTrue($director->pin_is_default);
        $this->assertStringStartsWith('DIR-', $director->employee_code);

        // Pivot yoxlaması
        $pivot = $company->users()->whereKey($director->id)->first();
        $this->assertNotNull($pivot);
        $this->assertSame('director', $pivot->pivot->role);
        $this->assertTrue((bool) $pivot->pivot->is_active);
    }

    // ==================================================================
    // 2. DIRECTOR FIELDS MƏCBURİDİR
    // ==================================================================

    public function test_company_cannot_be_created_without_director_name(): void
    {
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.companies.store'), $this->validPayload([
                'director_name' => '',
            ]));

        $response->assertSessionHasErrors('director_name');
        $this->assertDatabaseMissing('companies', ['slug' => 'acme-fleet']);
    }

    public function test_company_cannot_be_created_without_director_email(): void
    {
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.companies.store'), $this->validPayload([
                'director_email' => '',
            ]));

        $response->assertSessionHasErrors('director_email');
        $this->assertDatabaseMissing('companies', ['slug' => 'acme-fleet']);
    }

    public function test_company_cannot_be_created_without_director_password(): void
    {
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.companies.store'), $this->validPayload([
                'director_password' => '',
                'director_password_confirmation' => '',
            ]));

        $response->assertSessionHasErrors('director_password');
    }

    public function test_company_cannot_be_created_without_director_pin(): void
    {
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.companies.store'), $this->validPayload([
                'director_pin' => '',
                'director_pin_confirmation' => '',
            ]));

        $response->assertSessionHasErrors('director_pin');
    }

    public function test_director_pin_must_be_4_to_6_digits(): void
    {
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.companies.store'), $this->validPayload([
                'director_pin' => 'abc',
                'director_pin_confirmation' => 'abc',
            ]));

        $response->assertSessionHasErrors('director_pin');
    }

    public function test_director_pin_must_match_confirmation(): void
    {
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.companies.store'), $this->validPayload([
                'director_pin' => '1234',
                'director_pin_confirmation' => '5678',
            ]));

        $response->assertSessionHasErrors('director_pin');
    }

    public function test_director_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'rashad@acme.test']);

        $response = $this->asSuperAdmin()
            ->post(route('super-admin.companies.store'), $this->validPayload());

        $response->assertSessionHasErrors('director_email');
    }

    // ==================================================================
    // 3. TRANSACTION ATOMİKLİYİ
    // ==================================================================

    public function test_company_is_not_created_if_director_creation_fails(): void
    {
        // Let the exception propagate to the test — otherwise the HTTP
        // exception handler converts it into a 500 response and the
        // try/catch below never fires.
        $this->withoutExceptionHandling();

        // The Director (User) row is inserted AFTER the Company row inside

        Event::listen(
            'eloquent.creating: '.User::class,
            function () {
                throw new \RuntimeException('Simulated director creation failure');
            }
        );

        try {
            $this->asSuperAdmin()
                ->post(route('super-admin.companies.store'), $this->validPayload());
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated director creation failure', $e->getMessage());
        }

        // Neither company nor director should exist — rollback worked.
        $this->assertDatabaseMissing('companies', ['slug' => 'acme-fleet']);
        $this->assertDatabaseMissing('users', ['email' => 'rashad@acme.test']);
    }

    // ==================================================================
    // 4. SLUG AVTOMATİK GENERASİYA
    // ==================================================================

    public function test_slug_is_auto_generated_when_empty(): void
    {
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.companies.store'), $this->validPayload([
                'slug' => null,
                'name' => 'Baku Transport LLC',
            ]));

        $response->assertSessionHasNoErrors();

        $company = Company::where('name', 'Baku Transport LLC')->first();
        $this->assertNotNull($company);
        $this->assertSame('baku-transport-llc', $company->slug);
    }

    // ==================================================================
    // 5. NON-SUPER-ADMIN REJECTED
    // ==================================================================

    public function test_regular_user_cannot_create_company(): void
    {
        $regular = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($regular)
            ->withSession([
                'current_garage_id' => $this->garage->id,
                'current_company_id' => $this->garage->company_id,
            ])
            ->post(route('super-admin.companies.store'), $this->validPayload());

        $response->assertForbidden();
        $this->assertDatabaseMissing('companies', ['slug' => 'acme-fleet']);
    }
}
