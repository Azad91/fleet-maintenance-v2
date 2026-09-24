<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Garage;
use App\Models\ServiceVehicle;
use App\Models\ServiceVehicleStock;
use App\Models\Warehouse;
use App\Services\Complaint\ComplaintStockService;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression guard for the deadlock bug in ComplaintStockService.
 *
 * deductStock() acquires row-level locks via lockForUpdate(). If two
 * concurrent transactions lock the same rows in different orders,
 * PostgreSQL aborts one with a deadlock error.
 *
 * The fix sorts the input array by `code` before entering the loop,
 * guaranteeing a deterministic lock order across all transactions.
 *
 * These tests verify the ORDER, not the deadlock itself — the actual
 * deadlock can only be triggered with two real concurrent DB sessions,
 * which the Laravel test runner cannot easily simulate.
 */
class ComplaintStockDeadlockOrderTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Garage $garage;
    protected Bus $bus;
    protected Employee $employee;
    protected ComplaintStockService $stockService;

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

        $this->stockService = new ComplaintStockService;
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    // ==================================================================
    // HELPERS
    // ==================================================================

    protected function makeWarehouse(string $code, int $qty = 100): Warehouse
    {
        return Warehouse::withoutGlobalScopes()->create([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
            'code'       => $code,
            'name'       => "Part {$code}",
            'quantity'   => $qty,
        ]);
    }

    protected function detail(string $code, int $qty = 1): array
    {
        return [
            'shikayet_index' => 0,
            'code'           => $code,
            'used_quantity'  => $qty,
            'employee_id'    => $this->employee->id,
            'notes'          => 'Test',
        ];
    }

    /**
     * Capture the order in which the row-level locks were acquired.
     *
     * A `lockForUpdate()` query can carry the target `code` in any
     * binding position — for warehouses it is binding 0, for
     * service_vehicle_stocks the WHERE clause starts with
     * `service_vehicle_id` and `code` becomes binding 1. To stay
     * robust, we scan every binding of every "for update" query and
     * pick the string values that match the parts we are testing.
     *
     * @param  array<int, string>  $codesOfInterest
     * @return array<int, string>
     */
    protected function captureLockOrder(callable $action, array $codesOfInterest): array
    {
        $locks = [];

        DB::listen(function ($query) use (&$locks, $codesOfInterest) {
            if (! str_contains(strtolower($query->sql), 'for update')) {
                return;
            }

            foreach ($query->bindings as $binding) {
                if (is_string($binding) && in_array($binding, $codesOfInterest, true)) {
                    $locks[] = $binding;
                }
            }
        });

        $action();

        return $locks;
    }

    // ==================================================================
    // 1. WAREHOUSE — lock order is alphabetical
    // ==================================================================

    public function test_deduct_stock_locks_warehouse_rows_in_sorted_order(): void
    {
        $this->makeWarehouse('A-001', 100);
        $this->makeWarehouse('M-001', 100);
        $this->makeWarehouse('Z-001', 100);

        // Pass the details in REVERSE alphabetical order — the fix
        // must reorder them internally.
        $codes = $this->captureLockOrder(function () {
            $this->stockService->deductStock([
                $this->detail('Z-001'),
                $this->detail('M-001'),
                $this->detail('A-001'),
            ]);
        }, ['A-001', 'M-001', 'Z-001']);

        $this->assertSame(
            ['A-001', 'M-001', 'Z-001'],
            $codes,
            'Warehouse locks must be acquired in alphabetical order by code'
        );
    }

    public function test_deduct_stock_preserves_lock_order_for_two_codes(): void
    {
        $this->makeWarehouse('B-002', 100);
        $this->makeWarehouse('A-001', 100);

        $codes = $this->captureLockOrder(function () {
            $this->stockService->deductStock([
                $this->detail('B-002'),
                $this->detail('A-001'),
            ]);
        }, ['A-001', 'B-002']);

        $this->assertSame(['A-001', 'B-002'], $codes);
    }

    // ==================================================================
    // 2. SERVICE VEHICLE — lock order is alphabetical
    // ==================================================================

    public function test_deduct_stock_locks_service_vehicle_rows_in_sorted_order(): void
    {
        $vehicle = ServiceVehicle::withoutGlobalScopes()->create([
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
            'name'       => 'Test Vehicle',
            'is_active'  => true,
        ]);

        foreach (['A-001', 'M-001', 'Z-001'] as $code) {
            ServiceVehicleStock::withoutGlobalScopes()->create([
                'service_vehicle_id' => $vehicle->id,
                'garage_id'          => $this->garage->id,
                'company_id'         => $this->company->id,
                'code'               => $code,
                'name'               => "Part {$code}",
                'quantity'           => 50,
            ]);
        }

        $codes = $this->captureLockOrder(function () use ($vehicle) {
            $this->stockService->deductStock(
                [
                    $this->detail('Z-001'),
                    $this->detail('M-001'),
                    $this->detail('A-001'),
                ],
                location: 'road',
                serviceVehicleId: $vehicle->id,
            );
        }, ['A-001', 'M-001', 'Z-001']);

        $this->assertSame(['A-001', 'M-001', 'Z-001'], $codes);
    }

    // ==================================================================
    // 3. OUTPUT is still correct (regression)
    // ==================================================================

    public function test_deduct_stock_still_returns_correct_processed_rows(): void
    {
        $this->makeWarehouse('C-003', 50);

        $processed = $this->stockService->deductStock([
            $this->detail('C-003', 5),
        ]);

        $this->assertCount(1, $processed);
        $this->assertSame('C-003', $processed[0]['code']);
        $this->assertSame(5, $processed[0]['used_quantity']);
        $this->assertSame('warehouse', $processed[0]['source_type']);

        $this->assertSame(
            45,
            Warehouse::withoutGlobalScopes()->where('code', 'C-003')->value('quantity')
        );
    }

    // ==================================================================
    // 4. INSPECTION ROWS do not break the sort
    // ==================================================================

    public function test_sort_handles_mixed_consumption_and_inspection_rows(): void
    {
        // Both warehouse rows must exist before the deduction runs.
        $this->makeWarehouse('A-001', 100);
        $this->makeWarehouse('B-001', 100);

        // Input order: inspection, warehouse, inspection, warehouse
        $processed = $this->stockService->deductStock([
            $this->detail('B-002', 0),   // inspection
            $this->detail('A-001', 3),   // warehouse
            $this->detail('A-002', 0),   // inspection
            $this->detail('B-001', 2),   // warehouse
        ]);

        // Result is in sorted order: A-001, A-002, B-001, B-002
        $this->assertSame(
            ['A-001', 'A-002', 'B-001', 'B-002'],
            array_column($processed, 'code')
        );

        $this->assertSame('warehouse',  $processed[0]['source_type']);
        $this->assertSame('inspection', $processed[1]['source_type']);
        $this->assertSame('warehouse',  $processed[2]['source_type']);
        $this->assertSame('inspection', $processed[3]['source_type']);
    }

    // ==================================================================
    // 5. Empty / null codes are still skipped
    // ==================================================================

    public function test_sort_does_not_break_empty_code_handling(): void
    {
        $this->makeWarehouse('A-001', 100);

        $processed = $this->stockService->deductStock([
            ['code' => '', 'used_quantity' => 5, 'notes' => 'empty'],
            ['code' => 'A-001', 'used_quantity' => 2, 'employee_id' => $this->employee->id, 'notes' => 'ok'],
        ]);

        $this->assertCount(1, $processed);
        $this->assertSame('A-001', $processed[0]['code']);
    }
}