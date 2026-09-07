<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureGarageSelected;
use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected function setUp(): void
    {
        parent::setUp();
        GarageContext::clear();

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
    }

    public function test_super_admin_has_unrestricted_access()
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)
            ->withSession([
                'current_garage_id' => $this->garage->id,
                'current_company_id' => $this->company->id,
            ])
            ->get(route('dashboard'));

        $response->assertStatus(200);
    }

    public function test_garage_admin_can_access_complaints_and_warehouse()
    {
        $adminUser = User::factory()->create(['role' => 'user']);
        $adminUser->garages()->attach($this->garage, ['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($adminUser)
            ->withSession([
                'current_garage_id' => $this->garage->id,
                'current_company_id' => $this->company->id,
            ])
            ->get(route('complaints.index'));

        $response->assertStatus(200);

        $response2 = $this->actingAs($adminUser)
            ->withSession([
                'current_garage_id' => $this->garage->id,
                'current_company_id' => $this->company->id,
            ])
            ->get(route('warehouses.index'));

        $response2->assertStatus(200);
    }

    public function test_warehouse_role_cannot_create_complaint()
    {
        $warehouseUser = User::factory()->create(['role' => 'user']);
        $warehouseUser->garages()->attach($this->garage, ['role' => 'warehouse', 'is_active' => true]);

        $response = $this->actingAs($warehouseUser)
            ->withSession([
                'current_garage_id' => $this->garage->id,
                'current_company_id' => $this->company->id,
            ])
            ->get(route('complaints.create'));

        $response->assertStatus(403);
    }

    public function test_complaint_role_can_create_complaint()
    {
        $complaintUser = User::factory()->create(['role' => 'user']);
        $complaintUser->garages()->attach($this->garage, ['role' => 'complaint', 'is_active' => true]);

        $response = $this->actingAs($complaintUser)
            ->withSession([
                'current_garage_id' => $this->garage->id,
                'current_company_id' => $this->company->id,
            ])
            ->get(route('complaints.create'));

        $response->assertStatus(200);
    }

    public function test_inactive_garage_membership_is_denied()
    {
        $this->withMiddleware([EnsureGarageSelected::class]);

        $company = Company::factory()->create();
        $garage = Garage::factory()->create(['company_id' => $company->id]);

        $user = User::factory()->create(['role' => 'user']);
        $user->garages()->attach($garage, ['role' => 'admin', 'is_active' => false]);

        $response = $this->actingAs($user)
            ->withSession([
                'current_garage_id' => $garage->id,
                'current_company_id' => $company->id,
            ])
            ->get(route('dashboard'));

        $response->assertRedirect(route('garage.selection'));
    }
}
