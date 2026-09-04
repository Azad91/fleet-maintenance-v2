<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Garage;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected $garage;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::factory()->create();
        $this->garage = Garage::factory()->create([
            'company_id' => $company->id,
        ]);
    }

    public function test_bus_role_cannot_access_complaint_create_page()
    {
        $user = User::factory()->create(['role' => 'bus']);
        $user->garages()->attach($this->garage, ['role' => 'bus', 'is_active' => true]);

        session(['current_garage_id' => $this->garage->id]);

        $this->actingAs($user)
             ->get(route('complaints.create'))
             ->assertStatus(403);
    }

    public function test_complaint_role_can_access_complaint_create_page()
    {
        $user = User::factory()->create(['role' => 'complaint']);
        $user->garages()->attach($this->garage, ['role' => 'complaint', 'is_active' => true]);

        session(['current_garage_id' => $this->garage->id]);

        $this->actingAs($user)
             ->get(route('complaints.create'))
             ->assertStatus(200);
    }

    public function test_super_admin_can_access_any_page()
    {
        $user = User::factory()->create(['role' => 'admin']);
        // Super Admin üçün qaraja bağlanmağa ehtiyac yoxdur, amma sessiyada qaraj olmalıdır
        session(['current_garage_id' => $this->garage->id]);

        $this->actingAs($user)
             ->get(route('users.index'))
             ->assertStatus(200);
    }

    public function test_bus_role_cannot_access_warehouse_edit_page()
    {
        $user = User::factory()->create(['role' => 'bus']);
        $user->garages()->attach($this->garage, ['role' => 'bus', 'is_active' => true]);

        session(['current_garage_id' => $this->garage->id]);

        // Warehouse yaradırıq ki, edit səhifəsi açılsın
        $warehouse = \App\Models\Warehouse::factory()->create([
            'garage_id' => $this->garage->id,
        ]);

        $this->actingAs($user)
             ->get(route('warehouses.edit', $warehouse))
             ->assertStatus(403);
    }
}
