<?php

namespace Tests\Feature\Imports;

use App\Imports\ComplaintsImport;
use App\Models\Bus;
use App\Models\Complaint;
use App\Models\Company;
use App\Models\Garage;
use App\Models\Warehouse;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintsImportAtomicityTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected Bus $bus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage  = Garage::factory()->create(['company_id' => $this->company->id]);

        GarageContext::set($this->garage->id, $this->company->id);

        $this->bus = Bus::factory()->create([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
            'dqn'        => 'ATOMIC-001',
        ]);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    private function makeWarehouse(int $quantity): Warehouse
    {
        return Warehouse::withoutGlobalScopes()->create([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
            'code'       => 'ATOM-W-1',
            'name'       => 'Atomic Part',
            'quantity'   => $quantity,
        ]);
    }

    private function makeImport(): ComplaintsImport
    {
        return new ComplaintsImport($this->garage->id, $this->company->id);
    }

    private function makeRow(array $overrides = []): array
    {
        return array_merge([
            'dqn'            => 'ATOMIC-001',
            'yer'            => 'garage',
            'complaint_type' => 'breakdown',
            'complaints'     => 'Test complaint',
            'status'         => 'pending',
        ], $overrides);
    }

    // ==================================================================
    // 1. HAPPY PATH — STOK VƏ KART BİRLİKDƏ YARANIR
    // ==================================================================

    public function test_happy_path_creates_complaint_and_deducts_stock(): void
    {
        $warehouse = $this->makeWarehouse(10);

        $import = $this->makeImport();
        $import->processRow($this->makeRow([
            'part_code'     => 'ATOM-W-1',
            'used_quantity' => 3,
        ]), 2);

        $this->assertEquals(1, $import->importedCount);
        $this->assertEmpty($import->skipped);

        $this->assertEquals(7, $warehouse->fresh()->quantity, 'Stock should be 10 - 3 = 7');
        $this->assertEquals(1, Complaint::count());
    }

    // ==================================================================
    // 2. ATOMICITY — COMPLAINT FAIL OLSA STOK GERİ QAYTARILIR
    // ==================================================================

    public function test_stock_is_rolled_back_when_complaint_creation_fails(): void
    {
        $warehouse = $this->makeWarehouse(10);

        // Force Complaint::create to throw after stock was decremented.
        Complaint::creating(function () {
            throw new \RuntimeException('Simulated complaint creation failure');
        });

        $import = $this->makeImport();

        try {
            $import->processRow($this->makeRow([
                'part_code'     => 'ATOM-W-1',
                'used_quantity' => 3,
            ]), 2);
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated complaint creation failure', $e->getMessage());
        }

        // ✅ Stock must remain at original value — rollback worked
        $this->assertEquals(
            10,
            $warehouse->fresh()->quantity,
            'Stock decrement must roll back when complaint creation fails'
        );

        // ✅ No complaint created
        $this->assertEquals(0, Complaint::count());

        // ✅ Import counter not incremented
        $this->assertEquals(0, $import->importedCount);
    }

    public function test_stock_is_rolled_back_when_item_creation_fails(): void
    {
        $warehouse = $this->makeWarehouse(10);

        \App\Models\ComplaintItem::creating(function () {
            throw new \RuntimeException('Simulated item failure');
        });

        $import = $this->makeImport();

        try {
            $import->processRow($this->makeRow([
                'part_code'     => 'ATOM-W-1',
                'used_quantity' => 4,
                'complaints'    => 'Will fail here',
            ]), 2);
        } catch (\RuntimeException $e) {
            // expected
        }

        $this->assertEquals(10, $warehouse->fresh()->quantity);
        $this->assertEquals(0, Complaint::count());
    }

    // ==================================================================
    // 3. STOK YOXDURSA — HİÇ NƏ YARANMIR
    // ==================================================================

    public function test_insufficient_stock_skips_row_without_mutation(): void
    {
        $warehouse = $this->makeWarehouse(2);

        $import = $this->makeImport();
        $import->processRow($this->makeRow([
            'part_code'     => 'ATOM-W-1',
            'used_quantity' => 5, // more than available
        ]), 2);

        $this->assertEquals(0, $import->importedCount);
        $this->assertCount(1, $import->skipped);
        $this->assertEquals(2, $warehouse->fresh()->quantity, 'Stock must not change');
        $this->assertEquals(0, Complaint::count(), 'No complaint should be created');
    }

    public function test_unknown_part_skips_row_without_mutation(): void
    {
        $import = $this->makeImport();
        $import->processRow($this->makeRow([
            'part_code'     => 'NON-EXISTENT',
            'used_quantity' => 1,
        ]), 2);

        $this->assertEquals(0, $import->importedCount);
        $this->assertCount(1, $import->skipped);
        $this->assertEquals(0, Complaint::count());
    }

    // ==================================================================
    // 4. DQN YOXDURSA — HEÇ NƏ İŞLƏMİR
    // ==================================================================

    public function test_unknown_dqn_skips_row(): void
    {
        $import = $this->makeImport();
        $import->processRow($this->makeRow([
            'dqn' => 'UNKNOWN-999',
        ]), 2);

        $this->assertEquals(0, $import->importedCount);
        $this->assertCount(1, $import->skipped);
        $this->assertEquals(0, Complaint::count());
    }

    public function test_empty_dqn_skips_row(): void
    {
        $import = $this->makeImport();
        $import->processRow($this->makeRow(['dqn' => '']), 2);

        $this->assertEquals(0, $import->importedCount);
        $this->assertCount(1, $import->skipped);
    }

    // ==================================================================
    // 5. DETAILS DA TRANSACTION İÇİNDƏ
    // ==================================================================

    public function test_complaint_detail_is_created_with_stock_snapshot(): void
    {
        $warehouse = $this->makeWarehouse(20);

        $import = $this->makeImport();
        $import->processRow($this->makeRow([
            'part_code'     => 'ATOM-W-1',
            'used_quantity' => 5,
        ]), 2);

        $complaint = Complaint::with('details')->first();

        $this->assertNotNull($complaint);
        $this->assertCount(1, $complaint->details);
        $this->assertEquals(20, $complaint->details->first()->stock_quantity, 'Snapshot must be pre-decrement');
        $this->assertEquals(5, $complaint->details->first()->used_quantity);
        $this->assertEquals(15, $warehouse->fresh()->quantity, 'Stock after decrement');
    }

    // ==================================================================
    // 6. ROLLBACK — DETALLAR YOXDUR (sıfırdan yoxlanır)
    // ==================================================================

    public function test_failure_rolls_back_details_and_items_too(): void
    {
        $warehouse = $this->makeWarehouse(10);

        // Fail AFTER items + details were created — that is, at the very
        // end of the transaction. Force an exception on a second complaint
        // so we know the first one rolled back entirely.
        $callCount = 0;
        Complaint::created(function () use (&$callCount) {
            $callCount++;
            if ($callCount === 1) {
                throw new \RuntimeException('Fail after all mutations');
            }
        });

        $import = $this->makeImport();

        try {
            $import->processRow($this->makeRow([
                'part_code'     => 'ATOM-W-1',
                'used_quantity' => 3,
                'complaints'    => 'Trigger rollback',
            ]), 2);
        } catch (\RuntimeException $e) {
            // expected
        }

        // ✅ Stock rolled back
        $this->assertEquals(10, $warehouse->fresh()->quantity);

        // ✅ No complaint, no items, no details
        $this->assertEquals(0, Complaint::count());
        $this->assertEquals(0, \App\Models\ComplaintItem::count());
        $this->assertEquals(0, \App\Models\ComplaintDetail::count());

        // ✅ Import counter stays at 0
        $this->assertEquals(0, $import->importedCount);
    }
}
