<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GarageSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garageA;

    protected Garage $garageB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();

        $this->garageA = Garage::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Alpha Qarajı',
            'is_active' => true,
        ]);

        $this->garageB = Garage::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Beta Qarajı',
            'is_active' => true,
        ]);
    }

    public function test_super_admin_sees_all_garages_without_membership(): void
    {
        // ✅ Super admin — HEÇ BİR pivot qeydi yoxdur
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->get(route('garage.selection'));

        $response->assertOk();
        $response->assertSee('Alpha Qarajı');
        $response->assertSee('Beta Qarajı');
    }

    public function test_super_admin_can_select_any_garage_without_membership(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->post(route('garage.select'), [
            'garage_id' => $this->garageA->id,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertEquals($this->garageA->id, session('current_garage_id'));
        $this->assertEquals($this->company->id, session('current_company_id'));
    }

    public function test_regular_user_only_sees_assigned_garages(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $user->garages()->attach($this->garageA->id, ['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($user)->get(route('garage.selection'));

        $response->assertOk();
        $response->assertSee('Alpha Qarajı');
        $response->assertDontSee('Beta Qarajı');
    }

    public function test_regular_user_cannot_select_unassigned_garage(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $user->garages()->attach($this->garageA->id, ['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($user)->post(route('garage.select'), [
            'garage_id' => $this->garageB->id,
        ]);

        $response->assertRedirect(route('garage.selection'));
        $this->assertNull(session('current_garage_id'));
    }

    public function test_regular_user_without_any_garage_is_redirected_to_dashboard_with_error(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        // Heç bir qaraja təyin edilməyib

        $response = $this->actingAs($user)->get(route('garage.selection'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_inactive_garage_not_shown_to_regular_user(): void
    {
        $inactiveGarage = Garage::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Passiv Qaraj',
            'is_active' => false,
        ]);

        $user = User::factory()->create(['role' => 'user']);
        $user->garages()->attach($inactiveGarage->id, ['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($user)->get(route('garage.selection'));

        $response->assertDontSee('Passiv Qaraj');
    }

    public function test_selecting_garage_updates_user_current_garage(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)->post(route('garage.select'), [
            'garage_id' => $this->garageB->id,
        ]);

        $superAdmin->refresh();
        $this->assertEquals($this->garageB->id, $superAdmin->current_garage_id);
        $this->assertEquals($this->company->id, $superAdmin->current_company_id);
        $this->assertNotNull($superAdmin->last_selected_garage_at);
    }
}
