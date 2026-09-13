<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\ComplaintDetail;
use App\Models\Employee;
use App\Models\Garage;
use App\Models\Warehouse;
use App\Services\Complaint\ComplaintItemService;
use App\Services\Complaint\ComplaintService;
use App\Services\Complaint\ComplaintStatusTransitionService;
use App\Services\Complaint\ComplaintStockService;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintZeroQuantityTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected Bus $bus;

    protected Employee $employee;

    protected ComplaintService $service;

    protected ComplaintStockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $this->company->id]);

        GarageContext::set($this->garage->id, $this->company->id);

        $this->bus = Bus::factory()->create([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        $this->employee = Employee::factory()->create([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        $this->stockService = new ComplaintStockService();
        $this->service = new ComplaintService(
            $this->stockService,
            new ComplaintItemService(),
            new ComplaintStatusTransitionService()
        );
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    private function makeWarehouse(string $code, int $quantity): Warehouse
    {
        return Warehouse::factory()->create([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
            'code'       => $code,
            'name'       => "Part {$code}",
            'quantity'   => $quantity,
        ]);
    }

    private function baseData(): array
    {
        return [
            'bus_id'         => $this->bus->id,
            'yer'            => 'garage',
            'status'         => 'pending',
            'complaint_type' => 'breakdown',
            'km'             => 1000,
        ];
    }

    // ==================================================================
    // 1. ZERO QUANTITY SKIP — SERVICE LEVEL
    // ==================================================================

    public function test_deduct_stock_skips_zero_quantity_details(): void
    {
        $warehouse = $this->makeWarehouse('D-001', 10);

        $processed = $this->stockService->deductStock([
            [
                'code'          => 'D-001',
                'used_quantity' => 0,  // ← Skip olmalı
                'employee_id'   => $this->employee->id,
                'notes'         => 'Test',
            ],
        ]);

        $this->assertEmpty($processed, 'Zero-quantity details must be skipped');
        $this->assertEquals(10, $warehouse->fresh()->quantity, 'Stock must not change');
    }

    public function test_deduct_stock_skips_negative_quantity_details(): void
    {
        $warehouse = $this->makeWarehouse('D-002', 10);

        $processed = $this->stockService->deductStock([
            [
                'code'          => 'D-002',
                'used_quantity' => -5,  // ← Skip olmalı
                'employee_id'   => $this->employee->id,
                'notes'         => 'Test',
            ],
        ]);

        $this->assertEmpty($processed);
        $this->assertEquals(10, $warehouse->fresh()->quantity);
    }

    public function test_deduct_stock_skips_details_without_quantity_key(): void
    {
        $warehouse = $this->makeWarehouse('D-003', 10);

        $processed = $this->stockService->deductStock([
            [
                'code'        => 'D-003',
                'employee_id' => $this->employee->id,
                'notes'       => 'Missing used_quantity',
                // 'used_quantity' heç yoxdur
            ],
        ]);

        $this->assertEmpty($processed);
        $this->assertEquals(10, $warehouse->fresh()->quantity);
    }

    public function test_deduct_stock_processes_only_positive_quantities(): void
    {
        $warehouseA = $this->makeWarehouse('D-A', 10);
        $warehouseB = $this->makeWarehouse('D-B', 20);

        $processed = $this->stockService->deductStock([
            [
                'code'          => 'D-A',
                'used_quantity' => 0,   // skip
                'employee_id'   => $this->employee->id,
                'notes'         => 'Zero',
            ],
            [
                'code'          => 'D-B',
                'used_quantity' => 5,   // process
                'employee_id'   => $this->employee->id,
                'notes'         => 'Five',
            ],
        ]);

        $this->assertCount(1, $processed);
        $this->assertEquals('D-B', $processed[0]['code']);
        $this->assertEquals(5, $processed[0]['used_quantity']);

        $this->assertEquals(10, $warehouseA->fresh()->quantity, 'Zero-qty part must be untouched');
        $this->assertEquals(15, $warehouseB->fresh()->quantity, 'Positive-qty part was deducted');
    }

    // ==================================================================
    // 2. FULL COMPLAINT CREATION WITH ZERO-QUANTITY DETAILS
    // ==================================================================

    public function test_complaint_creation_with_mixed_quantities_succeeds(): void
    {
        $this->makeWarehouse('MIX-1', 10);
        $this->makeWarehouse('MIX-2', 20);

        $complaint = $this->service->create(
            $this->baseData(),
            [
                [
                    'code'          => 'MIX-1',
                    'used_quantity' => 0,  // skip
                    'employee_id'   => $this->employee->id,
                    'notes'         => 'Not used',
                ],
                [
                    'code'          => 'MIX-2',
                    'used_quantity' => 3,  // keep
                    'employee_id'   => $this->employee->id,
                    'notes'         => 'Used 3',
                ],
            ],
            ['Test complaint']
        );

        // Yalnız 1 detal yaradılmalıdır (MIX-2)
        $details = $complaint->details()->get();
        $this->assertCount(1, $details);
        $this->assertEquals('MIX-2', $details->first()->code);
        $this->assertEquals(3, $details->first()->used_quantity);
    }

    public function test_complaint_creation_with_all_zero_quantities_succeeds_without_details(): void
    {
        $this->makeWarehouse('ALLZERO-1', 10);
        $this->makeWarehouse('ALLZERO-2', 20);

        $complaint = $this->service->create(
            $this->baseData(),
            [
                ['code' => 'ALLZERO-1', 'used_quantity' => 0, 'employee_id' => $this->employee->id, 'notes' => 'X'],
                ['code' => 'ALLZERO-2', 'used_quantity' => 0, 'employee_id' => $this->employee->id, 'notes' => 'Y'],
            ],
            ['Test complaint']
        );

        // Heç bir detal yaradılmamalıdır
        $this->assertEquals(0, $complaint->details()->count());

        // Hər iki stok toxunulmaz qalmalıdır
        $this->assertEquals(10, Warehouse::withoutGlobalScopes()->where('code', 'ALLZERO-1')->first()->quantity);
        $this->assertEquals(20, Warehouse::withoutGlobalScopes()->where('code', 'ALLZERO-2')->first()->quantity);
    }

    // ==================================================================
    // 3. UPDATE WITH ZERO QUANTITY
    // ==================================================================

    public function test_update_removing_a_detail_by_setting_quantity_to_zero(): void
    {
        $warehouse = $this->makeWarehouse('UPD-1', 10);

        $complaint = $this->service->create(
            $this->baseData(),
            [
                [
                    'code'          => 'UPD-1',
                    'used_quantity' => 3,
                    'employee_id'   => $this->employee->id,
                    'notes'         => 'Initial',
                ],
            ],
            ['Test']
        );

        $this->assertEquals(7, $warehouse->fresh()->quantity);
        $this->assertEquals(1, $complaint->details()->count());

        // İndi used_quantity = 0 göndərək → detal silinməlidir, stok geri qaytarılmalıdır
        $this->service->update(
            $complaint,
            $this->baseData(),
            [
                [
                    'code'          => 'UPD-1',
                    'used_quantity' => 0,
                    'employee_id'   => $this->employee->id,
                    'notes'         => 'Removing',
                ],
            ],
            ['Test']
        );

        // Stok geri qayıtmalıdır
        $this->assertEquals(10, $warehouse->fresh()->quantity);

        // Detal silinməlidir (soft delete)
        $this->assertEquals(0, $complaint->fresh()->details()->count());
    }

    // ==================================================================
    // 4. DB CONSTRAINT SANITY CHECK
    // ==================================================================

    public function test_direct_zero_quantity_insert_still_fails_at_db_level(): void
    {
        $complaint = Complaint::create([
            'bus_id'         => $this->bus->id,
            'garage_id'      => $this->garage->id,
            'company_id'     => $this->company->id,
            'yer'            => 'garage',
            'status'         => 'pending',
            'complaint_type' => 'breakdown',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        // DB CHECK constraint (used_quantity > 0) pozulmalıdır
        ComplaintDetail::create([
            'complaint_id'   => $complaint->id,
            'code'           => 'TEST',
            'name'           => 'Test',
            'stock_quantity' => 10,
            'used_quantity'  => 0,  // ← CHECK pozur
            'garage_id'      => $this->garage->id,
            'company_id'     => $this->company->id,
        ]);
    }

    // ==================================================================
    // 5. IMPORT INTEGRATION (B3-dən sonra)
    // ==================================================================

    public function test_import_with_zero_quantity_does_not_crash(): void
    {
        $this->makeWarehouse('IMP-1', 10);

        $import = new \App\Imports\ComplaintsImport(
            $this->garage->id,
            $this->company->id
        );

        $bus = Bus::factory()->create([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
            'dqn'        => 'IMPORT-ZERO',
        ]);

        $import->processRow([
            'dqn'            => 'IMPORT-ZERO',
            'yer'            => 'garage',
            'complaint_type' => 'breakdown',
            'complaints'     => 'Test',
            'status'         => 'pending',
            'part_code'      => 'IMP-1',
            'used_quantity'  => 0,  // ← Problem yaratmamalı
        ], 2);

        $this->assertEquals(1, $import->importedCount);
        $this->assertEmpty($import->skipped);

        // Stok toxunulmaz qalmalıdır
        $this->assertEquals(
            10,
            Warehouse::withoutGlobalScopes()->where('code', 'IMP-1')->first()->quantity
        );
    }
}
