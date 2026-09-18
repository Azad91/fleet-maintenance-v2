<?php

namespace Tests\Feature;

use App\Enums\Location;
use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\ComplaintType as ComplaintTypeModel;
use App\Models\Driver;
use App\Models\Garage;
use App\Models\ServiceVehicle;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected Bus $bus;

    protected Driver $driver;

    protected ServiceVehicle $serviceVehicle;

    protected User $admin;

    protected string $validDescription = 'Engine noise';

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $this->company->id]);

        GarageContext::set($this->garage->id, $this->company->id);

        ComplaintTypeModel::withoutGlobalScopes()->create([
            'name' => $this->validDescription,
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        $this->bus = Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        $this->driver = Driver::withoutGlobalScopes()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'code' => 'DRV-001',
            'first_name' => 'Test',
            'last_name' => 'Driver',
            'is_active' => true,
        ]);

        $this->serviceVehicle = ServiceVehicle::withoutGlobalScopes()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'name' => 'Service Vehicle 1',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create(['role' => 'user']);
        $this->admin->garages()->attach($this->garage->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    private function garageSession(): array
    {
        return [
            'current_garage_id' => $this->garage->id,
            'current_company_id' => $this->company->id,
        ];
    }

    // ==================================================================
    // 1. GARAGE LOCATION
    // ==================================================================

    public function test_garage_location_does_not_require_driver(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('complaints.store'), [
                'bus_id' => $this->bus->id,
                'yer' => 'garage',
                'status' => 'pending',
                'complaint_type' => 'breakdown',
                'complaints' => [$this->validDescription],
            ]);

        $response->assertSessionHasNoErrors();

        $complaint = Complaint::first();
        $this->assertNotNull($complaint);
        $this->assertSame(Location::Garage, $complaint->yer);
        $this->assertNull($complaint->driver_id);
        $this->assertNull($complaint->driver_name);
    }

    // ==================================================================
    // 2. ROAD LOCATION
    // ==================================================================

    public function test_road_location_with_valid_driver_succeeds(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('complaints.store'), [
                'bus_id' => $this->bus->id,
                'yer' => 'road',
                'driver_id' => $this->driver->id,
                'service_vehicle_id' => $this->serviceVehicle->id,
                'status' => 'pending',
                'complaint_type' => 'breakdown',
                'complaints' => [$this->validDescription],
                'reported_date' => now()->toDateString(),
                'reported_time' => now()->format('H:i'),
            ]);

        $response->assertSessionHasNoErrors();

        $complaint = Complaint::first();
        $this->assertNotNull($complaint);
        $this->assertSame(Location::Road, $complaint->yer);
        $this->assertSame($this->driver->id, $complaint->driver_id);
        $this->assertSame($this->serviceVehicle->id, $complaint->service_vehicle_id);
        $this->assertSame($this->driver->full_name, $complaint->driver_name);
    }

    public function test_road_location_requires_reported_date(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('complaints.store'), [
                'bus_id' => $this->bus->id,
                'yer' => 'road',
                'driver_id' => $this->driver->id,
                'service_vehicle_id' => $this->serviceVehicle->id,
                'status' => 'pending',
                'complaint_type' => 'breakdown',
                'complaints' => [$this->validDescription],
                // reported_date / reported_time yoxdur
            ]);

        $response->assertSessionHasErrors(['reported_date', 'reported_time']);
    }

    public function test_road_location_requires_driver(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('complaints.store'), [
                'bus_id' => $this->bus->id,
                'yer' => 'road',
                'service_vehicle_id' => $this->serviceVehicle->id,
                'status' => 'pending',
                'complaint_type' => 'breakdown',
                'complaints' => [$this->validDescription],
                'reported_date' => now()->toDateString(),
                'reported_time' => now()->format('H:i'),
                // driver_id yoxdur
            ]);

        $response->assertSessionHasErrors('driver_id');
    }

    public function test_road_location_requires_service_vehicle(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('complaints.store'), [
                'bus_id' => $this->bus->id,
                'yer' => 'road',
                'driver_id' => $this->driver->id,
                'status' => 'pending',
                'complaint_type' => 'breakdown',
                'complaints' => [$this->validDescription],
                'reported_date' => now()->toDateString(),
                'reported_time' => now()->format('H:i'),
                // service_vehicle_id yoxdur
            ]);

        $response->assertSessionHasErrors('service_vehicle_id');
    }

    // ==================================================================
    // 3. INVALID LOCATION
    // ==================================================================

    public function test_invalid_location_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->post(route('complaints.store'), [
                'bus_id' => $this->bus->id,
                'yer' => 'yol', // legacy AZ value — must be rejected
                'status' => 'pending',
                'complaint_type' => 'breakdown',
                'complaints' => [$this->validDescription],
            ]);

        $response->assertSessionHasErrors('yer');
    }

    public function test_legacy_azerbaijani_location_value_is_rejected(): void
    {
        foreach (['yol', 'qaraj', 'ROAD', 'GARAGE', 'unknown'] as $badValue) {
            $response = $this->actingAs($this->admin)
                ->withSession($this->garageSession())
                ->post(route('complaints.store'), [
                    'bus_id' => $this->bus->id,
                    'yer' => $badValue,
                    'status' => 'pending',
                    'complaint_type' => 'breakdown',
                    'complaints' => [$this->validDescription],
                ]);

            $response->assertSessionHasErrors('yer');
        }

        $this->assertEquals(0, Complaint::count());
    }

    // ==================================================================
    // 4. UPDATE — LOCATION CHANGE
    // ==================================================================

    public function test_update_from_road_to_garage_clears_driver_context(): void
    {
        $complaint = Complaint::create([
            'bus_id' => $this->bus->id,
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'yer' => 'road',
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->full_name,
            'service_vehicle_id' => $this->serviceVehicle->id,
            'status' => 'pending',
            'complaint_type' => 'breakdown',
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->put(route('complaints.update', $complaint), [
                'bus_id' => $this->bus->id,
                'yer' => 'garage',
                'status' => 'pending',
                'complaint_type' => 'breakdown',
                'complaints' => [$this->validDescription],
            ]);

        $response->assertSessionHasNoErrors();

        $fresh = $complaint->fresh();
        $this->assertSame(Location::Garage, $fresh->yer);
        $this->assertNull($fresh->driver_id);
        $this->assertNull($fresh->driver_name);
        $this->assertNull($fresh->service_vehicle_id);
    }
}
