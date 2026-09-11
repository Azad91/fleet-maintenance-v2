<?php

namespace Tests\Feature\Reports;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\GarageContext;
use App\Services\Reports\ReportPeriod;
use App\Services\Reports\ReportScope;
use App\Services\Reports\WarehouseReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseReportTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garageA;

    protected Garage $garageB;

    protected WarehouseReportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garageA = Garage::factory()->create(['company_id' => $this->company->id]);
        $this->garageB = Garage::factory()->create(['company_id' => $this->company->id]);

        GarageContext::set($this->garageA->id, $this->company->id);

        $this->service = app(WarehouseReportService::class);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    private function period(): ReportPeriod
    {
        return new ReportPeriod(
            now()->startOfMonth(),
            now()->endOfMonth(),
            'monthly'
        );
    }

    // ==================== RECEIPT — CROSS-TENANT ISOLATION ====================

    public function test_receipt_only_shows_current_garage_items(): void
    {
        Warehouse::withoutGlobalScopes()->create([
            'garage_id'  => $this->garageA->id,
            'company_id' => $this->company->id,
            'code'       => 'GA-001',
            'name'       => 'Garage A Item',
            'quantity'   => 10,
        ]);

        Warehouse::withoutGlobalScopes()->create([
            'garage_id'  => $this->garageB->id,
            'company_id' => $this->company->id,
            'code'       => 'GB-001',
            'name'       => 'Garage B Item',
            'quantity'   => 20,
        ]);

        $scope = new ReportScope(
            garageIds: [$this->garageA->id],
            userId: null,
            readOnly: false
        );

        $items = $this->service->receipt($this->period(), $scope);

        $this->assertCount(1, $items);
        $this->assertSame('GA-001', $items->first()->code);
    }

    public function test_receipt_worker_filter_only_returns_own_items(): void
    {
        $worker = User::factory()->create(['role' => 'user']);
        $other  = User::factory()->create(['role' => 'user']);

        Warehouse::withoutGlobalScopes()->create([
            'garage_id'  => $this->garageA->id,
            'company_id' => $this->company->id,
            'created_by' => $worker->id,
            'code'       => 'W-001',
            'name'       => 'Worker Item',
            'quantity'   => 10,
        ]);

        Warehouse::withoutGlobalScopes()->create([
            'garage_id'  => $this->garageA->id,
            'company_id' => $this->company->id,
            'created_by' => $other->id,
            'code'       => 'O-001',
            'name'       => 'Other Item',
            'quantity'   => 20,
        ]);

        $scope = new ReportScope(
            garageIds: [$this->garageA->id],
            userId: $worker->id,
            readOnly: false
        );

        $items = $this->service->receipt($this->period(), $scope);

        $this->assertCount(1, $items);
        $this->assertSame('W-001', $items->first()->code);
    }

    // ==================== LOW STOCK ====================

    public function test_low_stock_returns_only_items_at_or_below_threshold(): void
    {
        Warehouse::withoutGlobalScopes()->create([
            'garage_id'        => $this->garageA->id,
            'company_id'       => $this->company->id,
            'code'             => 'OK-1',
            'name'             => 'Normal',
            'quantity'         => 50,
            'minimum_quantity' => 10,
        ]);

        Warehouse::withoutGlobalScopes()->create([
            'garage_id'        => $this->garageA->id,
            'company_id'       => $this->company->id,
            'code'             => 'LOW-1',
            'name'             => 'Low',
            'quantity'         => 5,
            'minimum_quantity' => 10,
        ]);

        Warehouse::withoutGlobalScopes()->create([
            'garage_id'        => $this->garageA->id,
            'company_id'       => $this->company->id,
            'code'             => 'ZERO-1',
            'name'             => 'Empty',
            'quantity'         => 0,
            'minimum_quantity' => 5,
        ]);

        $scope = new ReportScope([$this->garageA->id], null, false);

        $items = $this->service->lowStock($scope);

        $this->assertCount(2, $items);
        $this->assertTrue($items->contains('code', 'LOW-1'));
        $this->assertTrue($items->contains('code', 'ZERO-1'));
        $this->assertFalse($items->contains('code', 'OK-1'));
    }

    public function test_low_stock_does_not_leak_other_garage(): void
    {
        Warehouse::withoutGlobalScopes()->create([
            'garage_id'        => $this->garageB->id,
            'company_id'       => $this->company->id,
            'code'             => 'B-LOW',
            'name'             => 'Other Garage Low',
            'quantity'         => 1,
            'minimum_quantity' => 10,
        ]);

        $scope = new ReportScope([$this->garageA->id], null, false);

        $items = $this->service->lowStock($scope);

        $this->assertEmpty($items);
    }

    // ==================== WORKER ACTIVITY ====================

    public function test_worker_activity_isolated_by_garage(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user);

        // Create in garage A
        Warehouse::withoutGlobalScopes()->create([
            'garage_id'  => $this->garageA->id,
            'company_id' => $this->company->id,
            'code'       => 'ACT-A',
            'name'       => 'Activity A',
            'quantity'   => 1,
        ]);

        // Create in garage B (should not appear)
        Warehouse::withoutGlobalScopes()->create([
            'garage_id'  => $this->garageB->id,
            'company_id' => $this->company->id,
            'code'       => 'ACT-B',
            'name'       => 'Activity B',
            'quantity'   => 1,
        ]);

        $scope = new ReportScope([$this->garageA->id], null, false);

        $rows = $this->service->workerActivity($this->period(), $scope);

        // Total actions across all users = 1 (only garage A)
        $totalActions = $rows->sum('total_actions');
        $this->assertSame(1, $totalActions, 'Cross-tenant audit log leak detected');
    }

    // ==================== MOVEMENT ====================

    public function test_movement_logs_do_not_leak_across_garages(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        Warehouse::withoutGlobalScopes()->create([
            'garage_id'  => $this->garageA->id,
            'company_id' => $this->company->id,
            'code'       => 'M-A',
            'name'       => 'Movement A',
            'quantity'   => 1,
        ]);

        Warehouse::withoutGlobalScopes()->create([
            'garage_id'  => $this->garageB->id,
            'company_id' => $this->company->id,
            'code'       => 'M-B',
            'name'       => 'Movement B',
            'quantity'   => 1,
        ]);

        $scope = new ReportScope([$this->garageA->id], null, false);

        $logs = $this->service->movement($this->period(), $scope);

        foreach ($logs as $log) {
            $this->assertSame(
                $this->garageA->id,
                $log->garage_id,
                'Movement log leaked from another garage'
            );
        }
    }
}
