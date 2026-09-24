<?php

namespace Tests\Feature\Reports;

use App\Enums\TransferType;
use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Services\GarageContext;
use App\Services\Reports\ReportPeriod;
use App\Services\Reports\ReportScope;
use App\Services\Reports\TransferReportService;
use App\Services\Warehouse\WarehouseTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferReportTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garageA;

    protected Garage $garageB;

    protected Garage $garageC;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garageA = Garage::factory()->create(['company_id' => $this->company->id]);
        $this->garageB = Garage::factory()->create(['company_id' => $this->company->id]);
        $this->garageC = Garage::factory()->create(['company_id' => $this->company->id]);

        GarageContext::set($this->garageA->id, $this->company->id);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    protected function period(): ReportPeriod
    {
        return new ReportPeriod(now()->startOfMonth(), now()->endOfMonth(), 'monthly');
    }

    protected function makeItem(Garage $garage, string $code = 'FILTER-001', int $qty = 100): Warehouse
    {
        return Warehouse::withoutGlobalScopes()->create([
            'garage_id' => $garage->id,
            'company_id' => $this->company->id,
            'code' => $code,
            'name' => "Part {$code}",
            'quantity' => $qty,
        ]);
    }

    protected function createTransfer(Garage $from, Garage $to, int $qty = 5): WarehouseTransfer
    {
        $item = $this->makeItem($from, 'FILTER-'.uniqid());

        GarageContext::set($from->id, $this->company->id);

        $service = app(WarehouseTransferService::class);

        return $service->create([
            'from_garage_id' => $from->id,
            'to_garage_id' => $to->id,
            'type' => TransferType::GarageToGarage->value,
            'items' => [['warehouse_id' => $item->id, 'declared_quantity' => $qty]],
        ], $this->company->id);
    }

    // ==================================================================
    // 1. SUMMARY
    // ==================================================================

    public function test_summary_counts_only_visible_garages(): void
    {
        $this->createTransfer($this->garageA, $this->garageB);
        $this->createTransfer($this->garageB, $this->garageA);
        $this->createTransfer($this->garageC, $this->garageB); // unrelated to A

        $scope = new ReportScope([$this->garageA->id], null, false);
        $service = app(TransferReportService::class);

        $summary = $service->summary($this->period(), $scope);

        // Only the first two transfers involve garage A
        $this->assertSame(2, $summary['total']);
        $this->assertSame(1, $summary['outbound']);
        $this->assertSame(1, $summary['inbound']);
    }

    public function test_summary_with_no_transfers(): void
    {
        $scope = new ReportScope([$this->garageA->id], null, false);
        $service = app(TransferReportService::class);

        $summary = $service->summary($this->period(), $scope);

        $this->assertSame(0, $summary['total']);
        $this->assertSame(0, $summary['disputed']);
        $this->assertSame(0.0, $summary['dispute_rate']);
    }

    // ==================================================================
    // 2. BY ROUTE
    // ==================================================================

    public function test_by_route_groups_by_garage_pair(): void
    {
        $this->createTransfer($this->garageA, $this->garageB);
        $this->createTransfer($this->garageA, $this->garageB);
        $this->createTransfer($this->garageB, $this->garageA);

        $scope = new ReportScope([$this->garageA->id, $this->garageB->id], null, false);
        $service = app(TransferReportService::class);

        $rows = $service->byRoute($this->period(), $scope);

        $this->assertCount(2, $rows);

        // A -> B has 2 transfers
        $rowAB = $rows->firstWhere('from_garage_id', $this->garageA->id);
        $this->assertSame(2, (int) $rowAB->total_transfers);

        // B -> A has 1 transfer
        $rowBA = $rows->firstWhere('from_garage_id', $this->garageB->id);
        $this->assertSame(1, (int) $rowBA->total_transfers);
    }

    // ==================================================================
    // 3. TOP ITEMS
    // ==================================================================

    public function test_top_items_ranks_by_frequency(): void
    {
        // Same item transferred 3 times from A to B
        $item = $this->makeItem($this->garageA, 'FREQ-001', 100);
        $service = app(WarehouseTransferService::class);

        GarageContext::set($this->garageA->id, $this->company->id);

        for ($i = 0; $i < 3; $i++) {
            $service->create([
                'from_garage_id' => $this->garageA->id,
                'to_garage_id' => $this->garageB->id,
                'type' => TransferType::GarageToGarage->value,
                'items' => [['warehouse_id' => $item->id, 'declared_quantity' => 5]],
            ], $this->company->id);
        }

        // Another item transferred once
        $item2 = $this->makeItem($this->garageA, 'RARE-001', 50);

        $service->create([
            'from_garage_id' => $this->garageA->id,
            'to_garage_id' => $this->garageB->id,
            'type' => TransferType::GarageToGarage->value,
            'items' => [['warehouse_id' => $item2->id, 'declared_quantity' => 2]],
        ], $this->company->id);

        $scope = new ReportScope([$this->garageA->id], null, false);
        $report = app(TransferReportService::class);

        $rows = $report->topItems($this->period(), $scope);

        $this->assertGreaterThanOrEqual(1, $rows->count());
        $this->assertSame('FREQ-001', $rows->first()->code);
        $this->assertSame(3, (int) $rows->first()->times_transferred);
    }

    // ==================================================================
    // 4. HTTP
    // ==================================================================

    public function test_garage_admin_can_access_summary_report(): void
    {
        $admin = User::factory()->create(['role' => 'user']);
        $admin->garages()->attach($this->garageA->id, ['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($admin)
            ->withSession([
                'current_garage_id' => $this->garageA->id,
                'current_company_id' => $this->company->id,
            ])
            ->get(route('reports.transfer.summary'));

        $response->assertOk();
    }

    public function test_complaint_worker_cannot_access_transfer_reports(): void
    {
        $worker = User::factory()->create(['role' => 'user']);
        $worker->garages()->attach($this->garageA->id, ['role' => 'complaint_worker', 'is_active' => true]);

        $this->actingAs($worker)
            ->withSession([
                'current_garage_id' => $this->garageA->id,
                'current_company_id' => $this->company->id,
            ])
            ->get(route('reports.transfer.summary'))
            ->assertForbidden();
    }

    public function test_director_can_access_transfer_report(): void
    {
        $director = User::factory()->create(['role' => 'user']);
        $this->company->users()->attach($director->id, ['role' => 'director', 'is_active' => true]);

        $this->actingAs($director)
            ->get(route('director.reports.transfer.summary'))
            ->assertOk();
    }
}
