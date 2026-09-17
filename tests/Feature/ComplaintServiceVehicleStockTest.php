<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\Employee;
use App\Models\Garage;
use App\Models\ServiceVehicle;
use App\Models\ServiceVehicleStock;
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
    protected ServiceVehicle $vehicleA;
    protected ServiceVehicle $vehicleB;
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

        $this->vehicleA = ServiceVehicle::withoutGlobalScopes()->create([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
            'name'       => 'Service A',
            'is_active'  => true,
        ]);

        $this->vehicleB = ServiceVehicle::withoutGlobalScopes()->create([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
            'name'       => 'Service B',
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

    protected function makeVehicleStock(ServiceVehicle $vehicle, string $code, int $qty): ServiceVehicleStock
    {
        return ServiceVehicleStock::withoutGlobalScopes()->create([
            'service_vehicle_id' => $vehicle->id,
            'garage_id'          => $this->garage->id,
            'company_id'         => $this->company->id,
            'code'               => $code,
            'name'               => "Part {$code}",
            'unit'               => 'piece',
            'quantity'           => $qty,
        ]);
    }

    protected function baseData(string $location = 'garage', ?int $vehicleId = null): array
    {
        $data = [
            'bus_id'         => $this->bus->id,
            'yer'            => $location,
            'status'         => 'pending',
            'complaint_type' => 'breakdown',
            'km'             => 1000,
        ];

        if ($location === 'road') {
            $data['driver_name']   = 'Test Driver';
            $data['reported_date'] = now()->toDateString();
            $data['reported_time'] = now()->format('H:i');
        }

        if ($vehicleId !== null) {
            $data['service_vehicle_id'] = $vehicleId;
        }

        return $data;
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
    // 1. GARAGE LOCATION — WAREHOUSE ONLY
    // ==================================================================

    public function test_garage_location_uses_warehouse(): void
    {
        $warehouse = $this->makeWarehouse('FILTER-001', 20);

        $complaint = $this->service->create(
            $this->baseData('garage'),
            [$this->detail('FILTER-001', 5)],
            ['Test']
        );

        $this->assertSame(15, $warehouse->fresh()->quantity);
        $this->assertSame('warehouse', $complaint->details->first()->source_type);
        $this->assertNull($complaint->service_vehicle_id);
    }

    public function test_garage_location_ignores_service_vehicle_stock(): void
    {
        $warehouse = $this->makeWarehouse('FILTER-001', 20);
        $this->makeVehicleStock($this->vehicleA, 'FILTER-001', 50);

        $complaint = $this->service->create(
            $this->baseData('garage'),
            [$this->detail('FILTER-001', 5)],
            ['Test']
        );

        $this->assertSame(15, $warehouse->fresh()->quantity);
        $this->assertSame(50, ServiceVehicleStock::withoutGlobalScopes()
            ->where('service_vehicle_id', $this->vehicleA->id)
            ->sum('quantity'));

        $this->assertSame('warehouse', $complaint->details->first()->source_type);
    }

    // ==================================================================
    // 2. ROAD LOCATION — SPECIFIC VEHICLE ONLY (NO FALLBACK)
    // ==================================================================

    public function test_road_location_deducts_from_selected_vehicle(): void
    {
        $warehouse = $this->makeWarehouse('FILTER-001', 20);
        $this->makeVehicleStock($this->vehicleA, 'FILTER-001', 10);
        $this->makeVehicleStock($this->vehicleB, 'FILTER-001', 30);

        $complaint = $this->service->create(
            $this->baseData('road', $this->vehicleB->id),
            [$this->detail('FILTER-001', 5)],
            ['Test']
        );

        // Only vehicle B was debited — vehicle A untouched, warehouse untouched.
        $this->assertSame(10, ServiceVehicleStock::withoutGlobalScopes()
            ->where('service_vehicle_id', $this->vehicleA->id)
            ->sum('quantity'));

        $this->assertSame(25, ServiceVehicleStock::withoutGlobalScopes()
            ->where('service_vehicle_id', $this->vehicleB->id)
            ->sum('quantity'));

        $this->assertSame(20, $warehouse->fresh()->quantity, 'Warehouse must not be touched');

        $this->assertSame('service_vehicle', $complaint->details->first()->source_type);
        $this->assertSame($this->vehicleB->id, $complaint->service_vehicle_id);
    }

    public function test_road_location_does_not_fallback_to_warehouse(): void
    {
        $warehouse = $this->makeWarehouse('FILTER-001', 100);
        $this->makeVehicleStock($this->vehicleA, 'FILTER-001', 3);

        // Vehicle A only has 3, request 10 — must fail, NOT fall back.
        $this->expectException(ValidationException::class);

        $this->service->create(
            $this->baseData('road', $this->vehicleA->id),
            [$this->detail('FILTER-001', 10)],
            ['Test']
        );
    }

    public function test_road_location_requires_service_vehicle_id(): void
    {
        $this->makeWarehouse('FILTER-001', 100);

        $this->expectException(ValidationException::class);

        $this->service->create(
            $this->baseData('road'),   // ← no service_vehicle_id
            [$this->detail('FILTER-001', 5)],
            ['Test']
        );
    }

    public function test_road_location_fails_when_part_missing_on_vehicle(): void
    {
        $this->makeWarehouse('FILTER-001', 100);
        $this->makeVehicleStock($this->vehicleA, 'OTHER-PART', 10);

        $this->expectException(ValidationException::class);

        $this->service->create(
            $this->baseData('road', $this->vehicleA->id),
            [$this->detail('FILTER-001', 5)],
            ['Test']
        );
    }

    // ==================================================================
    // 3. RESTORE BEHAVIOR
    // ==================================================================

    public function test_delete_restores_to_the_specific_vehicle(): void
    {
        $this->makeVehicleStock($this->vehicleA, 'FILTER-001', 10);
        $this->makeVehicleStock($this->vehicleB, 'FILTER-001', 30);

        $complaint = $this->service->create(
            $this->baseData('road', $this->vehicleB->id),
            [$this->detail('FILTER-001', 5)],
            ['Test']
        );

        $this->assertSame(25, ServiceVehicleStock::withoutGlobalScopes()
            ->where('service_vehicle_id', $this->vehicleB->id)->sum('quantity'));

        // Delete → restore
        $this->service->delete($complaint);

        // Vehicle B restored, vehicle A untouched.
        $this->assertSame(30, ServiceVehicleStock::withoutGlobalScopes()
            ->where('service_vehicle_id', $this->vehicleB->id)->sum('quantity'));

        $this->assertSame(10, ServiceVehicleStock::withoutGlobalScopes()
            ->where('service_vehicle_id', $this->vehicleA->id)->sum('quantity'));
    }

    public function test_update_restores_old_and_deducts_new_from_same_vehicle(): void
    {
        $this->makeVehicleStock($this->vehicleA, 'FILTER-001', 20);

        $complaint = $this->service->create(
            $this->baseData('road', $this->vehicleA->id),
            [$this->detail('FILTER-001', 5)],
            ['Test']
        );

        $this->assertSame(15, ServiceVehicleStock::withoutGlobalScopes()
            ->where('service_vehicle_id', $this->vehicleA->id)->sum('quantity'));

        // Update quantity 5 → 8
        $this->service->update(
            $complaint,
            $this->baseData('road', $this->vehicleA->id),
            [$this->detail('FILTER-001', 8)],
            ['Test']
        );

        // 20 - 8 = 12
        $this->assertSame(12, ServiceVehicleStock::withoutGlobalScopes()
            ->where('service_vehicle_id', $this->vehicleA->id)->sum('quantity'));
    }

    public function test_restore_recreates_row_when_it_was_fully_depleted(): void
    {
        $this->makeWarehouse('FILTER-001', 100);
        $this->makeVehicleStock($this->vehicleA, 'FILTER-001', 5);

        $complaint = $this->service->create(
            $this->baseData('road', $this->vehicleA->id),
            [$this->detail('FILTER-001', 5)],
            ['Test']
        );

        // Vehicle stock is now 0
        $this->assertSame(0, ServiceVehicleStock::withoutGlobalScopes()
            ->where('service_vehicle_id', $this->vehicleA->id)->sum('quantity'));

        // Force-remove the stock row to simulate cleanup
        ServiceVehicleStock::withoutGlobalScopes()
            ->where('service_vehicle_id', $this->vehicleA->id)
            ->delete();

        // Delete complaint → restore should recreate the row
        $this->service->delete($complaint);

        $restored = ServiceVehicleStock::withoutGlobalScopes()
            ->where('service_vehicle_id', $this->vehicleA->id)
            ->where('code', 'FILTER-001')
            ->first();

        $this->assertNotNull($restored, 'Stock row should be recreated');
        $this->assertSame(5, $restored->quantity);
    }

    // ==================================================================
    // 4. LEGACY ROAD COMPLAINTS (no service_vehicle_id) — GRACEFUL
    // ==================================================================

    public function test_delete_legacy_road_complaint_does_not_crash(): void
    {
        $complaint = Complaint::create([
            'bus_id'         => $this->bus->id,
            'garage_id'      => $this->garage->id,
            'company_id'     => $this->company->id,
            'yer'            => 'road',
            'status'         => 'pending',
            'complaint_type' => 'breakdown',
        ]);

        $complaint->details()->create([
            'code'            => 'LEGACY-001',
            'name'            => 'Legacy Part',
            'stock_quantity'  => 10,
            'used_quantity'   => 3,
            'source_type'     => 'service_vehicle',
            'garage_id'       => $this->garage->id,
            'company_id'      => $this->company->id,
        ]);

        // Must not throw. The warning is logged, the delete succeeds.
        $this->service->delete($complaint);

        $this->assertSoftDeleted('complaints', ['id' => $complaint->id]);
    }
}
