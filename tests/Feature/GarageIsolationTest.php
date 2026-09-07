<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GarageIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    public function test_user_can_only_see_buses_in_their_current_garage_with_real_middleware(): void
    {
        $company = Company::factory()->create();
        $garageA = Garage::factory()->create(['company_id' => $company->id]);
        $garageB = Garage::factory()->create(['company_id' => $company->id]);

        $user = User::factory()->create(['role' => 'admin']);
        $user->garages()->attach($garageA->id, ['role' => 'admin', 'is_active' => true]);

        GarageContext::set($garageA->id, $company->id);
        $busA = Bus::create([
            'garage_id' => $garageA->id,
            'company_id' => $company->id,
            'dqn' => '90-AA-111',
            'route_number' => '380',
            'is_active' => true,
        ]);

        GarageContext::set($garageB->id, $company->id);
        $busB = Bus::create([
            'garage_id' => $garageB->id,
            'company_id' => $company->id,
            'dqn' => '90-BB-222',
            'route_number' => '191',
            'is_active' => true,
        ]);

        GarageContext::set($garageA->id, $company->id);

        $response = $this->actingAs($user)
            ->withSession([
                'current_garage_id' => $garageA->id,
                'current_company_id' => $company->id,
            ])
            ->get(route('buses.index'));

        $response->assertStatus(200);
        $response->assertSee($busA->dqn);
        $response->assertDontSee($busB->dqn);
    }

    public function test_user_cannot_access_unauthorized_garage_session(): void
    {
        $company = Company::factory()->create();
        $garageA = Garage::factory()->create(['company_id' => $company->id]);
        $garageB = Garage::factory()->create(['company_id' => $company->id]);

        // 'viewer' yerinə mövcud 'complaint' rolu istifadə olunur
        $user = User::factory()->create(['role' => 'complaint']);
        $user->garages()->attach($garageA->id, ['role' => 'complaint', 'is_active' => true]);

        $response = $this->actingAs($user)
            ->withSession([
                'current_garage_id' => $garageB->id,
                'current_company_id' => $company->id,
            ])
            ->get(route('buses.index'));

        if ($response->status() === 403) {
            $response->assertStatus(403);
        } else {
            $response->assertRedirect(route('garage.selection'));
        }
    }

    public function test_user_cannot_view_or_edit_bus_from_another_garage_idor(): void
    {
        $company = Company::factory()->create();
        $garageA = Garage::factory()->create(['company_id' => $company->id]);
        $garageB = Garage::factory()->create(['company_id' => $company->id]);

        $user = User::factory()->create(['role' => 'admin']);
        $user->garages()->attach($garageA->id, ['role' => 'admin', 'is_active' => true]);

        GarageContext::set($garageB->id, $company->id);
        $busB = Bus::create([
            'garage_id' => $garageB->id,
            'company_id' => $company->id,
            'dqn' => '90-CC-333',
            'is_active' => true,
        ]);

        GarageContext::set($garageA->id, $company->id);

        $response = $this->actingAs($user)
            ->withSession([
                'current_garage_id' => $garageA->id,
                'current_company_id' => $company->id,
            ])
            ->get(route('buses.show', $busB->id));

        $response->assertStatus(404);
    }

    public function test_super_admin_can_access_any_garage(): void
    {
        $company = Company::factory()->create();
        $garage = Garage::factory()->create(['company_id' => $company->id]);

        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        GarageContext::set($garage->id, $company->id);
        $bus = Bus::create([
            'garage_id' => $garage->id,
            'company_id' => $company->id,
            'dqn' => '90-DD-444',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)
            ->withSession([
                'current_garage_id' => $garage->id,
                'current_company_id' => $company->id,
            ])
            ->get(route('buses.show', $bus->id));

        $response->assertStatus(200);
    }
}
