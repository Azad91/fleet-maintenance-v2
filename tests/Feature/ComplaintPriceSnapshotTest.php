<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Company;
use App\Models\ComplaintDetail;
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
use Tests\TestCase;

class ComplaintPriceSnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected Bus $bus;

    protected Employee $employee;

    protected ComplaintService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $this->company->id]);

        GarageContext::set($this->garage->id, $this->company->id);

        $this->bus = Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        $this->employee = Employee::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
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

    private function makeWarehouse(string $code, int $qty, ?float $price = null): Warehouse
    {
        return Warehouse::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'code' => $code,
            'name' => "Part {$code}",
            'quantity' => $qty,
            'price' => $price,
        ]);
    }

    private function baseData(string $location = 'garage', ?int $vehicleId = null): array
    {
        $data = [
            'bus_id' => $this->bus->id,
            'yer' => $location,
            'status' => 'pending',
            'complaint_type' => 'breakdown',
            'km' => 1000,
        ];

        if ($location === 'road') {
            $data['driver_name'] = 'Test Driver';
            $data['reported_date'] = now()->toDateString();
            $data['reported_time'] = now()->format('H:i');
        }

        if ($vehicleId !== null) {
            $data['service_vehicle_id'] = $vehicleId;
        }

        return $data;
    }

    private function detail(string $code, int $qty): array
    {
        return [
            'shikayet_index' => 0,
            'code' => $code,
            'used_quantity' => $qty,
            'employee_id' => $this->employee->id,
            'notes' => 'Test',
        ];
    }

    // ==================================================================
    // 1. WAREHOUSE SOURCE — snapshots warehouse price
    // ==================================================================

    public function test_warehouse_deduction_snapshots_price(): void
    {
        $this->makeWarehouse('SNAP-1', 10, 25.50);

        $complaint = $this->service->create(
            $this->baseData(),
            [$this->detail('SNAP-1', 3)],
            ['Test']
        );

        $detail = $complaint->details()->first();
        $this->assertSame('25.50', $detail->price_at_use);
    }

    public function test_null_warehouse_price_stays_null_in_snapshot(): void
    {
        $this->makeWarehouse('SNAP-2', 10, null);

        $complaint = $this->service->create(
            $this->baseData(),
            [$this->detail('SNAP-2', 3)],
            ['Test']
        );

        $detail = $complaint->details()->first();
        $this->assertNull($detail->price_at_use);
    }

    public function test_price_snapshot_is_immutable_after_warehouse_price_changes(): void
    {
        $warehouse = $this->makeWarehouse('SNAP-3', 100, 10.00);

        $complaint = $this->service->create(
            $this->baseData(),
            [$this->detail('SNAP-3', 5)],
            ['Test']
        );

        // Change warehouse price after the fact
        $warehouse->update(['price' => 99.99]);

        $detail = $complaint->details()->first();
        $this->assertSame('10.00', $detail->price_at_use,
            'Historical price must not change when the warehouse catalog is updated');
    }

    // ==================================================================
    // 2. SERVICE VEHICLE SOURCE — fallback to warehouse price
    // ==================================================================

    public function test_service_vehicle_deduction_falls_back_to_warehouse_price(): void
    {
        $this->makeWarehouse('SNAP-4', 50, 40.00);

        $vehicle = ServiceVehicle::withoutGlobalScopes()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'name' => 'Vehicle 1',
            'is_active' => true,
        ]);

        ServiceVehicleStock::withoutGlobalScopes()->create([
            'service_vehicle_id' => $vehicle->id,
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'code' => 'SNAP-4',
            'name' => 'Part SNAP-4',
            'quantity' => 10,
        ]);

        $complaint = $this->service->create(
            $this->baseData('road', $vehicle->id),
            [$this->detail('SNAP-4', 2)],
            ['Test']
        );

        $detail = $complaint->details()->first();
        $this->assertSame('40.00', $detail->price_at_use,
            'Vehicle deduction must fall back to the warehouse price for the same code');
    }

    // ==================================================================
    // 3. INSPECTION ROWS — price is 0
    // ==================================================================

    public function test_inspection_row_has_zero_price(): void
    {
        $this->makeWarehouse('SNAP-5', 10, 100.00);

        $complaint = $this->service->create(
            $this->baseData(),
            [$this->detail('SNAP-5', 0)],
            ['Test']
        );

        $detail = $complaint->details()->first();
        $this->assertSame('inspection', $detail->source_type);
        $this->assertSame('0.00', $detail->price_at_use);
    }

    // ==================================================================
    // 4. TOTAL COST ACCESSOR
    // ==================================================================

    public function test_total_cost_accessor_computes_correctly(): void
    {
        $this->makeWarehouse('SNAP-6', 10, 12.50);

        $complaint = $this->service->create(
            $this->baseData(),
            [$this->detail('SNAP-6', 4)],
            ['Test']
        );

        $detail = $complaint->details()->first();
        $this->assertSame(50.0, $detail->total_cost);
    }

    public function test_total_cost_is_null_when_price_is_null(): void
    {
        $this->makeWarehouse('SNAP-7', 10, null);

        $complaint = $this->service->create(
            $this->baseData(),
            [$this->detail('SNAP-7', 4)],
            ['Test']
        );

        $detail = $complaint->details()->first();
        $this->assertNull($detail->total_cost);
    }

    // ==================================================================
    // 5. IMPORT — HISTORICAL MODE
    // ==================================================================

    public function test_historical_import_snapshots_current_warehouse_price(): void
    {
        $this->makeWarehouse('IMP-SNAP-1', 100, 15.00);

        Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'dqn' => 'IMPORT-PRICE-001',
        ]);

        $import = new \App\Imports\ComplaintsImport(
            $this->garage->id,
            $this->company->id,
            deductStock: false,
        );

        $import->processRow([
            'dqn' => 'IMPORT-PRICE-001',
            'yer' => 'garage',
            'complaint_type' => 'breakdown',
            'complaints' => 'Historical test',
            'status' => 'completed',
            'part_code' => 'IMP-SNAP-1',
            'used_quantity' => 5,
        ], 2);

        $detail = ComplaintDetail::withoutGlobalScopes()->first();
        $this->assertNotNull($detail);
        $this->assertSame('historical', $detail->source_type);
        $this->assertSame('15.00', $detail->price_at_use);
    }

    // ==================================================================
    // 6. REGRESSION — price snapshot preserved on update
    // ==================================================================

    public function test_update_does_not_lose_price_snapshot(): void
    {
        $this->makeWarehouse('UPD-SNAP-1', 100, 20.00);

        $complaint = $this->service->create(
            $this->baseData(),
            [$this->detail('UPD-SNAP-1', 3)],
            ['Test']
        );

        // Update quantity only — price snapshot must remain
        $this->service->update(
            $complaint,
            $this->baseData(),
            [$this->detail('UPD-SNAP-1', 5)],
            ['Test']
        );

        $detail = $complaint->fresh()->details()->first();
        $this->assertSame('20.00', $detail->price_at_use);
        $this->assertSame(5, $detail->used_quantity);
    }
}
