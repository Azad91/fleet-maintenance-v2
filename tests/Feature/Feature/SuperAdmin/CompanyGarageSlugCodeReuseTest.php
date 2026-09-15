<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\MakesSuperAdminWithMfa;

/**
 * Verifies that companies.slug and garages.code can be reused after
 * the original record has been soft-deleted, and that uniqueness is
 * still enforced among active rows.
 *
 * Mirrors the pattern already tested for users (see SoftDeleteUserTest)
 * and drivers/warehouses/buses.
 */
class CompanyGarageSlugCodeReuseTest extends TestCase
{
    use MakesSuperAdminWithMfa;
    use RefreshDatabase;

    protected User $superAdmin;

    protected Company $company;

    protected Garage $garage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create([
            'slug' => 'anchor-company',
        ]);

        $this->garage = Garage::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'ANCHOR-GARAGE',
        ]);

        $this->superAdmin = $this->makeSuperAdminWithMfa();
    }

    protected function asSuperAdmin(): self
    {
        return $this->actingAs($this->superAdmin)->withSession([
            'current_garage_id' => $this->garage->id,
            'current_company_id' => $this->company->id,
        ]);
    }

    // ==================================================================
    // 1. COMPANIES.SLUG REUSE
    // ==================================================================

    public function test_company_slug_can_be_reused_after_soft_delete(): void
    {
        // Soft-delete the original company directly (bypassing the
        // controller, which refuses to delete companies with active
        // garages). We only care about the DB unique index here.
        $this->company->delete();
        $this->garage->delete();

        $response = $this->asSuperAdmin()
            ->post(route('super-admin.companies.store'), [
                'name' => 'Second Company',
                'slug' => 'anchor-company', // ← previously taken
                'is_active' => 1,
                'director_name' => 'New Director',
                'director_email' => 'new-director@test.com',
                'director_password' => 'Str0ngPass!',
                'director_password_confirmation' => 'Str0ngPass!',
                'director_pin' => '1234',
                'director_pin_confirmation' => '1234',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('companies', [
            'slug' => 'anchor-company',
            'name' => 'Second Company',
        ]);

        // Two rows total: the soft-deleted one and the new one.
        $this->assertSame(2, Company::withTrashed()
            ->where('slug', 'anchor-company')
            ->count());
    }

    public function test_active_company_slug_still_must_be_unique(): void
    {
        // The original company is NOT soft-deleted → slug is taken.
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.companies.store'), [
                'name' => 'Duplicate Attempt',
                'slug' => 'anchor-company',
                'is_active' => 1,
                'director_name' => 'New Director',
                'director_email' => 'new-director@test.com',
                'director_password' => 'Str0ngPass!',
                'director_password_confirmation' => 'Str0ngPass!',
                'director_pin' => '1234',
                'director_pin_confirmation' => '1234',
            ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_company_update_allows_keeping_own_slug(): void
    {
        $response = $this->asSuperAdmin()
            ->put(route('super-admin.companies.update', $this->company), [
                'name' => 'Renamed Company',
                'slug' => 'anchor-company', // same slug — must be allowed
                'is_active' => 1,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('Renamed Company', $this->company->fresh()->name);
    }

    // ==================================================================
    // 2. GARAGES.CODE REUSE
    // ==================================================================

    public function test_garage_code_can_be_reused_after_soft_delete(): void
    {
        // Soft-delete the garage directly. A fresh company is used for
        // the new garage so no other constraint interferes.
        $this->garage->delete();

        $otherCompany = Company::factory()->create();

        $response = $this->asSuperAdmin()
            ->post(route('super-admin.garages.store'), [
                'company_id' => $otherCompany->id,
                'name' => 'Second Garage',
                'code' => 'ANCHOR-GARAGE', // ← previously taken
                'is_active' => 1,
                'admin_name' => 'Garage Admin',
                'admin_email' => 'garage-admin@test.com',
                'admin_password' => 'Str0ngPass!',
                'admin_password_confirmation' => 'Str0ngPass!',
                'admin_pin' => '1234',
                'admin_pin_confirmation' => '1234',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('garages', [
            'code' => 'ANCHOR-GARAGE',
            'name' => 'Second Garage',
        ]);

        $this->assertSame(2, Garage::withTrashed()
            ->where('code', 'ANCHOR-GARAGE')
            ->count());
    }

    public function test_active_garage_code_still_must_be_unique(): void
    {
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.garages.store'), [
                'company_id' => $this->company->id,
                'name' => 'Duplicate Garage',
                'code' => 'ANCHOR-GARAGE',
                'is_active' => 1,
                'admin_name' => 'Garage Admin',
                'admin_email' => 'garage-admin@test.com',
                'admin_password' => 'Str0ngPass!',
                'admin_password_confirmation' => 'Str0ngPass!',
                'admin_pin' => '1234',
                'admin_pin_confirmation' => '1234',
            ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_garage_update_allows_keeping_own_code(): void
    {
        $response = $this->asSuperAdmin()
            ->put(route('super-admin.garages.update', $this->garage), [
                'company_id' => $this->company->id,
                'name' => 'Renamed Garage',
                'code' => 'ANCHOR-GARAGE', // same code — must be allowed
                'is_active' => 1,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('Renamed Garage', $this->garage->fresh()->name);
    }
}
