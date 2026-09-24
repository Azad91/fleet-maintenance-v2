<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Garage;
use App\Models\ServiceVehicle;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceVehicleTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garageA;

    protected Garage $garageB;

    protected User $admin;

    protected User $otherAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garageA = Garage::factory()->create(['company_id' => $this->company->id]);
        $this->garageB = Garage::factory()->create(['company_id' => $this->company->id]);

        $this->admin = User::factory()->create(['role' => 'user']);
        $this->admin->garages()->attach($this->garageA->id, ['role' => 'admin', 'is_active' => true]);

        $this->otherAdmin = User::factory()->create(['role' => 'user']);
        $this->otherAdmin->garages()->attach($this->garageB->id, ['role' => 'admin', 'is_active' => true]);

        GarageContext::set($this->garageA->id, $this->company->id);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    protected function garageSession(): array
    {
        return [
            'current_garage_id' => $this->garageA->id,
            'current_company_id' => $this->company->id,
        ];
    }

    // ==================================================================
    // 1. INDEX / AUTH
    // ==================================================================

    public function test_admin_can_view_index(): void
    {
        $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('service-vehicles.index'))
            ->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('service-vehicles.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_view_index(): void
    {
        $worker = User::factory()->create(['role' => 'user']);
        $worker->garages()->attach($this->garageA->id, ['role' => 'complaint_worker', 'is_active' => true]);

        $this->actingAs($worker)
            ->withSession($this->garageSession())
            ->get(route('service-vehicles.index'))
            ->assertForbidden();
    }

    // ==================================================================
    // 2. CREATE
    // ==================================================================

    public function test_admin_can_create_service_vehicle(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('service-vehicles.store'), [
                'name' => 'Service Vehicle 1',
                'plate_number' => '90-AA-123',
                'driver_name' => 'Elshad Mammadov',
                'phone' => '+994 50 123 45 67',
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('service-vehicles.index'));

        $this->assertDatabaseHas('service_vehicles', [
            'name' => 'Service Vehicle 1',
            'plate_number' => '90-AA-123',
            'garage_id' => $this->garageA->id,
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
    }

    public function test_name_is_required(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('service-vehicles.store'), [
                'plate_number' => '90-AA-123',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_plate_number_is_normalized_to_uppercase(): void
    {
        $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('service-vehicles.store'), [
                'name' => 'Vehicle',
                'plate_number' => '90-aa-123',
            ]);

        $this->assertDatabaseHas('service_vehicles', [
            'plate_number' => '90-AA-123',
        ]);
    }

    public function test_duplicate_plate_number_in_same_garage_is_rejected(): void
    {
        ServiceVehicle::withoutGlobalScopes()->create([
            'garage_id' => $this->garageA->id,
            'company_id' => $this->company->id,
            'name' => 'Existing',
            'plate_number' => '90-AA-123',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('service-vehicles.store'), [
                'name' => 'Duplicate',
                'plate_number' => '90-AA-123',
            ]);

        $response->assertSessionHasErrors('plate_number');
    }

    public function test_same_plate_number_allowed_in_different_garages(): void
    {
        ServiceVehicle::withoutGlobalScopes()->create([
            'garage_id' => $this->garageB->id,
            'company_id' => $this->company->id,
            'name' => 'Other garage vehicle',
            'plate_number' => '90-AA-123',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('service-vehicles.store'), [
                'name' => 'Same plate here',
                'plate_number' => '90-AA-123',
            ]);

        $response->assertSessionHasNoErrors();
    }

    // ==================================================================
    // 3. UPDATE / DELETE
    // ==================================================================

    public function test_admin_can_update_own_vehicle(): void
    {
        $vehicle = ServiceVehicle::withoutGlobalScopes()->create([
            'garage_id' => $this->garageA->id,
            'company_id' => $this->company->id,
            'name' => 'Old Name',
            'plate_number' => '90-AA-111',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->put(route('service-vehicles.update', $vehicle), [
                'name' => 'New Name',
                'plate_number' => '90-AA-111',
                'is_active' => 1,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('New Name', $vehicle->fresh()->name);
    }

    public function test_admin_cannot_update_other_garage_vehicle(): void
    {
        $vehicle = ServiceVehicle::withoutGlobalScopes()->create([
            'garage_id' => $this->garageB->id,
            'company_id' => $this->company->id,
            'name' => 'B garage vehicle',
            'is_active' => true,
        ]);

        // Global scope hides it → 404
        $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->put(route('service-vehicles.update', $vehicle), [
                'name' => 'Hacked',
                'is_active' => 1,
            ])
            ->assertNotFound();
    }

    public function test_admin_can_soft_delete_vehicle(): void
    {
        $vehicle = ServiceVehicle::withoutGlobalScopes()->create([
            'garage_id' => $this->garageA->id,
            'company_id' => $this->company->id,
            'name' => 'To delete',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->delete(route('service-vehicles.destroy', $vehicle))
            ->assertRedirect(route('service-vehicles.index'));

        $this->assertSoftDeleted('service_vehicles', ['id' => $vehicle->id]);
    }

    // ==================================================================
    // 4. SOFT-DELETE PLATE REUSE
    // ==================================================================

    public function test_plate_number_can_be_reused_after_soft_delete(): void
    {
        $vehicle = ServiceVehicle::withoutGlobalScopes()->create([
            'garage_id' => $this->garageA->id,
            'company_id' => $this->company->id,
            'name' => 'Old vehicle',
            'plate_number' => '90-AA-555',
            'is_active' => true,
        ]);

        $vehicle->delete();

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('service-vehicles.store'), [
                'name' => 'New vehicle',
                'plate_number' => '90-AA-555',
            ]);

        $response->assertSessionHasNoErrors();
    }
}
