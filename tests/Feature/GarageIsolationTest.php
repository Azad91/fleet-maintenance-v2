<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Garage;
use App\Models\Company;
use App\Models\Bus;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GarageIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        GarageContext::clear();
    }

    public function test_user_can_only_see_buses_in_their_current_garage_with_real_middleware()
    {
        $company = Company::factory()->create();

        $garageA = Garage::factory()->create(['company_id' => $company->id]);
        $garageB = Garage::factory()->create(['company_id' => $company->id]);

        $busA = Bus::factory()->create([
            'garage_id' => $garageA->id,
            'company_id' => $company->id,
            'dqn' => '90-AA-111',
        ]);
        $busB = Bus::factory()->create([
            'garage_id' => $garageB->id,
            'company_id' => $company->id,
            'dqn' => '90-BB-222',
        ]);

        $user = User::factory()->create(['role' => 'user']);
        $user->garages()->attach($garageA, ['role' => 'admin', 'is_active' => true]);

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

    public function test_user_cannot_access_unauthorized_garage_session()
    {
        $company = Company::factory()->create();
        $garageA = Garage::factory()->create(['company_id' => $company->id]);
        $garageB = Garage::factory()->create(['company_id' => $company->id]);

        $user = User::factory()->create(['role' => 'user']);
        $user->garages()->attach($garageA, ['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($user)
            ->withSession([
                'current_garage_id' => $garageB->id,
                'current_company_id' => $company->id,
            ])
            ->get(route('buses.index'));

        $response->assertRedirect(route('garage.selection'));
    }

    public function test_user_cannot_view_or_edit_bus_from_another_garage_idor()
    {
        $company = Company::factory()->create();
        $garageA = Garage::factory()->create(['company_id' => $company->id]);
        $garageB = Garage::factory()->create(['company_id' => $company->id]);

        $busB = Bus::factory()->create([
            'garage_id' => $garageB->id,
            'company_id' => $company->id,
            'dqn' => '90-BB-999',
        ]);

        $user = User::factory()->create(['role' => 'user']);
        $user->garages()->attach($garageA, ['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($user)
            ->withSession([
                'current_garage_id' => $garageA->id,
                'current_company_id' => $company->id,
            ])
            ->get(route('buses.edit', $busB));

        $this->assertTrue(in_array($response->status(), [403, 404], true));
    }

    public function test_super_admin_can_access_any_garage()
    {
        $company = Company::factory()->create();
        $garage = Garage::factory()->create(['company_id' => $company->id]);

        $bus = Bus::factory()->create([
            'garage_id' => $garage->id,
            'company_id' => $company->id,
            'dqn' => '99-ZZ-999',
        ]);

        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)
            ->withSession([
                'current_garage_id' => $garage->id,
                'current_company_id' => $company->id,
            ])
            ->get(route('buses.index'));

        $response->assertStatus(200);
        $response->assertSee($bus->dqn);
    }
}
