<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminFormRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected Company $company;

    protected Garage $garage;

    protected function setUp(): void
    {
        parent::setUp();

        // The /super-admin/* routes are wrapped in `garage.selected`,
        // which requires a current garage in the session even for super
        // admins. Real requests get this from the login flow; tests must
        // populate it explicitly.
        $this->company = Company::factory()->create();
        $this->garage  = Garage::factory()->create(['company_id' => $this->company->id]);

        $this->superAdmin = User::factory()->create(['role' => 'super_admin']);
    }

    /**
     * Act as the super admin with a garage context in session.
     */
    protected function asSuperAdmin(): self
    {
        return $this->actingAs($this->superAdmin)->withSession([
            'current_garage_id'  => $this->garage->id,
            'current_company_id' => $this->company->id,
        ]);
    }

    /**
     * Act as a regular (non-super-admin) user with a garage context.
     */
    protected function asRegularUser(?User $user = null): self
    {
        $user ??= User::factory()->create(['role' => 'user']);

        return $this->actingAs($user)->withSession([
            'current_garage_id'  => $this->garage->id,
            'current_company_id' => $this->company->id,
        ]);
    }

    // ==================================================================
    // 1. AUTHORIZE() — Non-super-admin users are rejected
    // ==================================================================

    public function test_non_super_admin_cannot_store_company(): void
    {
        $response = $this->asRegularUser()
            ->post(route('super-admin.companies.store'), [
                'name' => 'Attacker Inc',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('companies', ['name' => 'Attacker Inc']);
    }

    public function test_non_super_admin_cannot_store_garage(): void
    {
        $response = $this->asRegularUser()
            ->post(route('super-admin.garages.store'), [
                'company_id' => $this->company->id,
                'name'       => 'Attacker Garage',
                'code'       => 'ATT-001',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('garages', ['code' => 'ATT-001']);
    }

    public function test_non_super_admin_cannot_store_user(): void
    {
        $response = $this->asRegularUser()
            ->post(route('super-admin.users.store'), [
                'name'                  => 'Attacker',
                'email'                 => 'attacker@test.com',
                'password'              => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'attacker@test.com']);
    }

    // ==================================================================
    // 2. UNIQUE RULES — Store
    // ==================================================================

    public function test_company_slug_must_be_unique_on_store(): void
    {
        Company::factory()->create(['slug' => 'taken-slug']);

        $response = $this->asSuperAdmin()
            ->post(route('super-admin.companies.store'), [
                'name' => 'New Co',
                'slug' => 'taken-slug',
            ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_garage_code_must_be_unique_on_store(): void
    {
        Garage::factory()->create(['code' => 'GAR-001', 'company_id' => $this->company->id]);

        $response = $this->asSuperAdmin()
            ->post(route('super-admin.garages.store'), [
                'company_id' => $this->company->id,
                'name'       => 'New Garage',
                'code'       => 'GAR-001',
            ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_user_email_must_be_unique_on_store(): void
    {
        User::factory()->create(['email' => 'taken@test.com']);

        $response = $this->asSuperAdmin()
            ->post(route('super-admin.users.store'), [
                'name'                  => 'New',
                'email'                 => 'taken@test.com',
                'password'              => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertSessionHasErrors('email');
    }

    // ==================================================================
    // 3. UNIQUE RULES — Update ignores current record
    // ==================================================================

    public function test_company_slug_update_ignores_current_company(): void
    {
        $company = Company::factory()->create(['slug' => 'my-slug']);

        $response = $this->asSuperAdmin()
            ->put(route('super-admin.companies.update', $company), [
                'name'      => 'Updated Name',
                'slug'      => 'my-slug', // same slug — must be allowed
                'is_active' => 1,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('Updated Name', $company->fresh()->name);
    }

    public function test_garage_code_update_ignores_current_garage(): void
    {
        $garage = Garage::factory()->create([
            'code'       => 'GAR-KEEP',
            'company_id' => $this->company->id,
        ]);

        $response = $this->asSuperAdmin()
            ->put(route('super-admin.garages.update', $garage), [
                'company_id' => $this->company->id,
                'name'       => 'Updated Garage',
                'code'       => 'GAR-KEEP', // same code — must be allowed
                'is_active'  => 1,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('Updated Garage', $garage->fresh()->name);
    }

    public function test_user_email_update_ignores_current_user(): void
    {
        $user = User::factory()->create(['email' => 'keep@test.com']);

        $response = $this->asSuperAdmin()
            ->put(route('super-admin.users.update', $user), [
                'name'      => 'Updated',
                'email'     => 'keep@test.com', // same email — must be allowed
                'is_active' => 1,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('Updated', $user->fresh()->name);
    }

    // ==================================================================
    // 4. FORMAT RULES
    // ==================================================================

    public function test_slug_must_match_regex(): void
    {
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.companies.store'), [
                'name' => 'New Co',
                'slug' => 'Invalid Slug With Spaces!',
            ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_user_pin_must_be_4_to_6_digits(): void
    {
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.users.store'), [
                'name'                  => 'New',
                'email'                 => 'new@test.com',
                'password'              => 'password123',
                'password_confirmation' => 'password123',
                'pin'                   => 'abc', // not digits
            ]);

        $response->assertSessionHasErrors('pin');
    }

    public function test_user_password_must_be_confirmed(): void
    {
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.users.store'), [
                'name'                  => 'New',
                'email'                 => 'new@test.com',
                'password'              => 'password123',
                'password_confirmation' => 'different',
            ]);

        $response->assertSessionHasErrors('password');
    }

    // ==================================================================
    // 5. HAPPY PATH — super admin creates/updates successfully
    // ==================================================================

    public function test_super_admin_can_create_company(): void
    {
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.companies.store'), [
                'name'      => 'Test Company',
                'slug'      => 'test-company',
                'is_active' => 1,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('companies', ['slug' => 'test-company']);
    }

    public function test_super_admin_can_create_garage(): void
    {
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.garages.store'), [
                'company_id' => $this->company->id,
                'name'       => 'New Garage',
                'code'       => 'NEW-001',
                'is_active'  => 1,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('garages', ['code' => 'NEW-001']);
    }

    public function test_super_admin_can_create_user(): void
    {
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.users.store'), [
                'name'                  => 'New User',
                'email'                 => 'newuser@test.com',
                'password'              => 'password123',
                'password_confirmation' => 'password123',
                'is_active'             => 1,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'newuser@test.com']);
    }
}
