<?php

namespace Tests\Feature\Imports;

use App\Imports\ComplaintsImport;
use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\ComplaintDetail;
use App\Models\Garage;
use App\Models\Warehouse;
use App\Services\Complaint\ComplaintItemService;
use App\Services\Complaint\ComplaintService;
use App\Services\Complaint\ComplaintStatusTransitionService;
use App\Services\Complaint\ComplaintStockService;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintsImportHistoricalTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Garage $garage;
    protected Bus $bus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $this->company->id]);

        GarageContext::set($this->garage->id, $this->company->id);

        $this->bus = Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'dqn' => 'HIST-001',
        ]);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    protected function makeWarehouse(int $qty): Warehouse
    {
        return Warehouse::withoutGlobalScopes()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'code' => 'HIST-PART-1',
            'name' => 'Historical Part',
            'quantity' => $qty,
        ]);
    }

    protected function row(): array
    {
        return [
            'dqn' => 'HIST-001',
            'yer' => 'garage',
            'complaint_type' => 'breakdown',
            'complaints' => 'Old complaint',
            'status' => 'completed',
            'part_code' => 'HIST-PART-1',
            'used_quantity' => 5,
        ];
    }

    // ==================================================================
    // 1. HISTORICAL MODE — STOCK IS UNTOUCHED
    // ==================================================================

    public function test_historical_import_does_not_deduct_stock(): void
    {
        $warehouse = $this->makeWarehouse(100);

        $import = new ComplaintsImport(
            $this->garage->id,
            $this->company->id,
            deductStock: false,
        );

        $import->processRow($this->row(), 2);

        // Warehouse untouched
        $this->assertSame(100, $warehouse->fresh()->quantity);

        // Complaint + detail created
        $this->assertSame(1, Complaint::count());

        $detail = ComplaintDetail::withoutGlobalScopes()->first();
        $this->assertNotNull($detail);
        $this->assertSame('historical', $detail->source_type);
        $this->assertSame(5, $detail->used_quantity);
    }

    public function test_normal_import_still_deducts_stock(): void
    {
        $warehouse = $this->makeWarehouse(100);

        $import = new ComplaintsImport(
            $this->garage->id,
            $this->company->id,
            deductStock: true,
        );

        $import->processRow($this->row(), 2);

        $this->assertSame(95, $warehouse->fresh()->quantity);

        $detail = ComplaintDetail::withoutGlobalScopes()->first();
        $this->assertSame('warehouse', $detail->source_type);
    }

    // ==================================================================
    // 2. DELETE — NO RESTORE FOR HISTORICAL
    // ==================================================================

    public function test_deleting_historical_complaint_does_not_restore_stock(): void
    {
        $warehouse = $this->makeWarehouse(100);

        $import = new ComplaintsImport($this->garage->id, $this->company->id, deductStock: false);
        $import->processRow($this->row(), 2);

        $complaint = Complaint::first();
        $this->assertNotNull($complaint);

        // Delete via service
        $service = new ComplaintService(
            new ComplaintStockService,
            new ComplaintItemService,
            new ComplaintStatusTransitionService
        );

        $service->delete($complaint);

        // Stock still 100 — no restore.
        $this->assertSame(100, $warehouse->fresh()->quantity);
    }

    // ==================================================================
    // 3. UPDATE — NO STOCK DIFF FOR HISTORICAL
    // ==================================================================

    public function test_updating_historical_complaint_does_not_touch_stock(): void
    {
        $warehouse = $this->makeWarehouse(100);

        $import = new ComplaintsImport($this->garage->id, $this->company->id, deductStock: false);
        $import->processRow($this->row(), 2);

        $complaint = Complaint::first();

        $service = new ComplaintService(
            new ComplaintStockService,
            new ComplaintItemService,
            new ComplaintStatusTransitionService
        );

        // Try to change quantity 5 → 8
        $service->update(
            $complaint,
            [
                'bus_id' => $this->bus->id,
                'yer' => 'garage',
                'status' => 'completed',
                'complaint_type' => 'breakdown',
            ],
            [
                [
                    'shikayet_index' => 0,
                    'code' => 'HIST-PART-1',
                    'used_quantity' => 8,
                    'notes' => 'Changed',
                ],
            ],
            ['Old complaint']
        );

        // Stock untouched
        $this->assertSame(100, $warehouse->fresh()->quantity);
    }

    // ==================================================================
    // 4. HISTORICAL IMPORT WITH MISSING STOCK STILL SUCCEEDS
    // ==================================================================

    public function test_historical_import_succeeds_even_when_stock_is_zero(): void
    {
        $warehouse = $this->makeWarehouse(0);   // stock depleted

        $import = new ComplaintsImport($this->garage->id, $this->company->id, deductStock: false);
        $import->processRow($this->row(), 2);

        $this->assertSame(1, $import->importedCount);
        $this->assertEmpty($import->skipped);

        $detail = ComplaintDetail::withoutGlobalScopes()->first();
        $this->assertNotNull($detail);
        $this->assertSame(0, $warehouse->fresh()->quantity, 'Stock must remain at 0');
    }

    public function test_normal_import_fails_when_stock_is_zero(): void
    {
        $this->makeWarehouse(0);

        $import = new ComplaintsImport($this->garage->id, $this->company->id, deductStock: true);
        $import->processRow($this->row(), 2);

        $this->assertSame(0, $import->importedCount);
        $this->assertCount(1, $import->skipped);
    }
}
