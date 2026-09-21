<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GarageNoAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_any_garage_sees_no_access_page(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        // Heç bir qaraja təyin olunmayıb
        $response = $this->actingAs($user)
            ->get(route('garage.selection'));

        $response->assertOk();
        $response->assertViewIs('garage-no-access');
    }

    public function test_no_access_page_does_not_redirect_to_dashboard(): void
    {
        // Əvvəlki bug: /select-garage → /dashboard → /select-garage
        // Sonsuz loop. İndi 200 qaytarır, redirect YOX.
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->get(route('garage.selection'));

        $response->assertStatus(200);
        $response->assertDontSee('dashboard', false);
    }

    public function test_no_access_page_shows_logout_button(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->get(route('garage.selection'));

        $response->assertOk();
        $response->assertSee('logout');
    }

    public function test_user_with_inactive_membership_sees_no_access_page(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $company = \App\Models\Company::factory()->create();
        $garage = \App\Models\Garage::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Membership var, lakin deaktivdir
        $user->garages()->attach($garage->id, [
            'role' => 'admin',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('garage.selection'));

        $response->assertOk();
        $response->assertViewIs('garage-no-access');
    }

    public function test_super_admin_without_garages_does_not_see_no_access_page(): void
    {
        // SuperAdmin heç bir qaraja təyin olunmasa belə, garage-no-access
        // görməməlidir — çünki o, qaraj seçiminə ehtiyac duymur.
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)
            ->get(route('garage.selection'));

        $response->assertOk();
        $response->assertViewIs('garage-selection');
        $response->assertDontSee('garage-no-access');
    }

    public function test_user_with_active_garage_sees_selection_page(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $company = \App\Models\Company::factory()->create();
        $garage = \App\Models\Garage::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $user->garages()->attach($garage->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('garage.selection'));

        $response->assertOk();
        $response->assertViewIs('garage-selection');
    }
}
