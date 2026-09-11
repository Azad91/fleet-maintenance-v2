<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name'      => 'Test Company',
            'slug'      => 'test-company',
            'is_active' => true,
        ]);

        $this->garage = Garage::create([
            'company_id' => $this->company->id,
            'name'       => 'Test Garage',
            'code'       => 'TG-001',
            'is_active'  => true,
        ]);
    }

    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get(route('complaints.create'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_without_garage_is_redirected_to_garage_selection()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('complaints.create'));
        $response->assertRedirect(route('garage.selection'));
    }

    public function test_warehouse_manager_cannot_create_complaint()
    {
        $user = User::factory()->create();

        $user->garages()->attach($this->garage, [
            'role'      => 'warehouse_manager',
            'is_active' => true,
        ]);

        session([
            'current_garage_id'  => $this->garage->id,
            'current_company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($user)->get(route('complaints.create'));
        $response->assertStatus(403);
    }

    public function test_complaint_manager_can_create_complaint()
    {
        $user = User::factory()->create();

        $user->garages()->attach($this->garage, [
            'role'      => 'complaint_manager',
            'is_active' => true,
        ]);

        session([
            'current_garage_id'  => $this->garage->id,
            'current_company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($user)->get(route('complaints.create'));
        $response->assertStatus(200);
    }

    public function test_complaint_worker_can_create_complaint()
    {
        $user = User::factory()->create();

        $user->garages()->attach($this->garage, [
            'role'      => 'complaint_worker',
            'is_active' => true,
        ]);

        session([
            'current_garage_id'  => $this->garage->id,
            'current_company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($user)->get(route('complaints.create'));
        $response->assertStatus(200);
    }
}
