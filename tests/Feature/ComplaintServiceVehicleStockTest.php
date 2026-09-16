<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\Employee;
use App\Models\Garage;
use App\Models\ServiceVehicle;
use App\Models\ServiceVehicleStock;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Complaint\ComplaintItemService;
use App\Services\Complaint\ComplaintService;
use App\Services\Complaint\ComplaintStatusTransitionService;
use App\Services\Complaint\ComplaintStockService;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ComplaintServiceVehicleStockTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Garage $garage;
    protected Bus $bus;
    protected Employee $employee;
    protected ServiceVehicle $vehicle;
    protected ComplaintService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage  = Garage::factory()->create(['company_id' => $this->company->id]);

        GarageContext::set($this->garage->id, $this->company->id);

        $this->bus = Bus::factory()->create([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        $this->employee = Employee::factory()->create([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        $this->vehicle = ServiceVehicle::withoutGlobalScopes()->create([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
            'name'       => 'Service Vehicle 1',
            'is_active'  => true,
        ]);

        $this->service = new ComplaintService(
            new ComplaintStockService,
            new ComplaintItemService,
            new ComplaintStatusTransitionService
        );
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    protected function makeWarehouse(string $code, int $qty): Warehouse
    {
        return Warehouse::factory()->create([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
            'code'       => $code,
            'name'       => "Part {$code}",
            'quantity'   => $qty,
        ]);
    }

    protected function makeServiceStock(string $code, int $qty): ServiceVehicleStock
    {
        return ServiceVehicleStock::withoutGlobalScopes()->create([
            'service_vehicle_id' => $this->vehicle->id,
            'garage_id'          => $this->garage->id,
            'company_id'         => $this->company->id,
            'code'               => $code,
            'name'               => "Part {$code}",
            'unit'               => 'piece',
            'quantity'           => $qty,
        ]);
    }

    protected function baseData(string $location = 'garage'): array
    {
        return [
            'bus_id'         => $this->bus->id,
            'yer'            => $location,
            'status'         => 'pending',
            'complaint_type' => 'breakdown',
            'km'             => 1000,
        ];
    }

    protected function detail(string $code, int $qty): array
    {
        return [
            'shikayet_index' => 0,
            'code'           => $code,
            'used_quantity'  => $qty,
            'employee_id'    => $this->employee->id,
            'notes'          => 'Test',
        ];
    }

    // ==================================================================
    // 1. GARAGE LOCATION — ALWAYS USES WAREHOUSE
    // ==================================================================

    public function test_garage_location_uses_warehouse_even_when_service_vehicle_has_stock(): void
    {
        $warehouse = $this->makeWarehouse('FILTER-001', 20);
        $this->makeServiceStock('FILTER-001', 50);

        $complaint = $this->service->create(
            $this->baseData('garage'),
            [$this->detail('FILTER-001', 5)],
            ['Test']
        );

        // Warehouse was decremented
        $this->assertSame(15, $warehouse->fresh()->quantity);

        // Service vehicle stock untouched
        $this->assertSame(50, ServiceVehicleStock::withoutGlobalScopes()
            ->where('code', 'FILTER-001')->sum('quantity'));

        // Detail marked as warehouse
        $this->assertSame('warehouse', $complaint->details->first()->source_type);
    }

    // ==================================================================
    // 2. ROAD LOCATION — USES SERVICE VEHICLE FIRST
    // ==================================================================

    public function test_road_location_prefers_service_vehicle_stock(): void
    {
        $warehouse = $this->makeWarehouse('FILTER-001', 20);
        $this->makeServiceStock('FILTER-001', 10);

        $complaint = $this->service->create(
            $this->baseData('road'),
            [$this->detail('FILTER-001', 5)],
            ['Test']
        );

        // Warehouse untouched
        $this->assertSame(20, $warehouse->fresh()->quantity);

        // Service vehicle decremented
        $this->assertSame(5, ServiceVehicleStock::withoutGlobalScopes()
            ->where('code', 'FILTER-001')->sum('quantity'));

        // Detail marked as service_vehicle
        $this->assertSame('service_vehicle', $complaint->details->first()->source_type);
    }

    // ==================================================================
    // 3. ROAD + INSUFFICIENT SERVICE VEHICLE — FALLBACK TO WAREHOUSE
    // ==================================================================

    public function test_road_location_falls_back_to_warehouse_when_service_vehicle_insufficient(): void
    {
        $warehouse = $this->makeWarehouse('FILTER-001', 20);
        $this->makeServiceStock('FILTER-001', 3);

        $complaint = $this->service->create(
            $this->baseData('road'),
            [$this->detail('FILTER-001', 10)],
            ['Test']
        );

        // Service vehicle untouched (fallback, not partial)
        $this->assertSame(3, ServiceVehicleStock::withoutGlobalScopes()
            ->where('code', 'FILTER-001')->sum('quantity'));

        // Warehouse fully used
        $this->assertSame(10, $warehouse->fresh()->quantity);

        // Detail marked as warehouse
        $this->assertSame('warehouse', $complaint->details->first()->source_type);
    }

    public function test_road_location_falls_back_when_no_service_vehicle_stock_exists(): void
    {
        $warehouse = $this->makeWarehouse('FILTER-001', 20);

        $complaint = $this->service->create(
            $this->baseData('road'),
            [$this->detail('FILTER-001', 5)],
            ['Test']
        );

        $this->assertSame(15, $warehouse->fresh()->quantity);
        $this->assertSame('warehouse', $complaint->details->first()->source_type);
    }

    // ==================================================================
    // 4. MULTIPLE SERVICE VEHICLES — DRAIN LARGEST FIRST
    // ==================================================================

    public function test_road_location_drains_multiple_service_vehicles_largest_first(): void
    {
        $vehicle2 = ServiceVehicle::withoutGlobalScopes()->create([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
            'name'       => 'Service Vehicle 2',
            'is_active'  => true,
        ]);

        // Vehicle 1 has 3, Vehicle 2 has 10
        ServiceVehicleStock::withoutGlobalScopes()->create([
            'service_vehicle_id' => $this->vehicle->id,
            'garage_id'          => $this->garage->id,
            'company_id'         => $this->company->id,
            'code'               => 'FILTER-001',
            'name'               => 'Filter',
            'quantity'           => 3,
        ]);

        ServiceVehicleStock::withoutGlobalScopes()->create([
            'service_vehicle_id' => $vehicle2->id,
            'garage_id'          => $this->garage->id,
            'company_id'         => $this->company->id,
            'code'               => 'FILTER-001',
            'name'               => 'Filter',
            'quantity'           => 10,
        ]);

        $this->service->create(
            $this->baseData('road'),
            [$this->detail('FILTER-001', 8)],
            ['Test']
        );

        // Largest drained first: vehicle2 (10) is fully consumed
        // down to 2; vehicle1 (3) stays untouched.
        // Need 8 → take 8 from the largest stack (vehicle2 10 → 2).
        $this->assertSame(3, ServiceVehicleStock::withoutGlobalScopes()
            ->where('service_vehicle_id', $this->vehicle->id)->sum('quantity'));
        $this->assertSame(2, ServiceVehicleStock::withoutGlobalScopes()
            ->where('service_vehicle_id', $vehicle2->id)->sum('quantity'));
    }

    // ==================================================================
    // 5. RESTORE ON DELETE
    // ==================================================================

    public function test_restore_returns_stock_to_service_vehicle(): void
    {
        $this->makeServiceStock('FILTER-001', 10);

        $complaint = $this->service->create(
            $this->baseData('road'),
            [$this->detail('FILTER-001', 4)],
            ['Test']
        );

        $this->assertSame(6, ServiceVehicleStock::withoutGlobalScopes()
            ->where('code', 'FILTER-001')->sum('quantity'));

        // Delete → restore
        $this->service->delete($complaint);

        $this->assertSame(10, ServiceVehicleStock::withoutGlobalScopes()
            ->where('code', 'FILTER-001')->sum('quantity'));
    }

    public function test_restore_returns_stock_to_warehouse_when_fallback_used(): void
    {
        $warehouse = $this->makeWarehouse('FILTER-001', 20);

        $complaint = $this->service->create(
            $this->baseData('road'),
            [$this->detail('FILTER-001', 5)],
            ['Test']
        );

        $this->assertSame(15, $warehouse->fresh()->quantity);

        $this->service->delete($complaint);

        $this->assertSame(20, $warehouse->fresh()->quantity);
    }

    // ==================================================================
    // 6. INSUFFICIENT EVERYWHERE
    // ==================================================================

    public function test_road_location_fails_when_neither_source_has_enough(): void
    {
        $this->makeWarehouse('FILTER-001', 3);
        $this->makeServiceStock('FILTER-001', 2);

        $this->expectException(ValidationException::class);

        $this->service->create(
            $this->baseData('road'),
            [$this->detail('FILTER-001', 10)],
            ['Test']
        );
    }
}
