<?php

namespace Tests\Feature\Reports;

use App\Enums\ComplaintType;
use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\ComplaintDetail;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use App\Services\Reports\MaintenanceReportService;
use App\Services\Reports\ReportPeriod;
use App\Services\Reports\ReportScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceReportTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Garage $garageA;
    protected Garage $garageB;
    protected Bus $busA;
    protected Bus $busB;
    protected MaintenanceReportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garageA = Garage::factory()->create(['company_id' => $this->company->id]);
        $this->garageB = Garage::factory()->create(['company_id' => $this->company->id]);

        GarageContext::set($this->garageA->id, $this->company->id);

        $this->busA = Bus::withoutGlobalScopes()->create([
            'garage_id' => $this->garageA->id,
            'company_id' => $this->company->id,
            'dqn' => 'MA-001',
            'route_number' => '101',
            'is_active' => true,
        ]);

        $this->busB = Bus::withoutGlobalScopes()->create([
            'garage_id' => $this->garageB->id,
            'company_id' => $this->company->id,
            'dqn' => 'MB-001',
            'route_number' => '202',
            'is_active' => true,
        ]);

        $this->service = app(MaintenanceReportService::class);
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

    protected function makeComplaint(Bus $bus, array $overrides = []): Complaint
    {
        return Complaint::withoutGlobalScopes()->create(array_merge([
            'bus_id' => $bus->id,
            'garage_id' => $bus->garage_id,
            'company_id' => $this->company->id,
            'yer' => 'garage',
            'status' => 'pending',
            'complaint_type' => 'breakdown',
            'created_at' => now(),
        ], $overrides));
    }

    protected function makeDetail(Complaint $complaint, array $overrides = []): ComplaintDetail
    {
        return ComplaintDetail::withoutGlobalScopes()->create(array_merge([
            'complaint_id' => $complaint->id,
            'shikayet_index' => 0,
            'code' => 'PART-001',
            'name' => 'Test Part',
            'stock_quantity' => 100,
            'used_quantity' => 1,
            'price_at_use' => 10.00,
            'source_type' => 'warehouse',
            'garage_id' => $complaint->garage_id,
            'company_id' => $this->company->id,
        ], $overrides));
    }

    // ==================================================================
    // 1. SUMMARY
    // ==================================================================

    public function test_summary_counts_cards_and_cost_in_scope(): void
    {
        $c1 = $this->makeComplaint($this->busA);
        $c2 = $this->makeComplaint($this->busA, ['status' => 'completed', 'closed_at' => now()]);
        $this->makeDetail($c1, ['used_quantity' => 3, 'price_at_use' => 20.00]);
        $this->makeDetail($c2, ['used_quantity' => 2, 'price_at_use' => 15.00]);

        $scope = new ReportScope([$this->garageA->id], null, false);
        $summary = $this->service->summary($this->period(), $scope);

        $this->assertSame(2, $summary['opened']);
        $this->assertSame(1, $summary['closed']);
        $this->assertSame(5, $summary['total_quantity']);
        $this->assertSame(90.0, $summary['total_cost']);
    }

    public function test_summary_does_not_leak_other_garage(): void
    {
        $this->makeComplaint($this->busB, ['status' => 'completed', 'closed_at' => now()]);

        $scope = new ReportScope([$this->garageA->id], null, false);
        $summary = $this->service->summary($this->period(), $scope);

        $this->assertSame(0, $summary['opened']);
        $this->assertSame(0, $summary['closed']);
    }

    // ==================================================================
    // 2. PER BUS
    // ==================================================================

    public function test_per_bus_groups_correctly(): void
    {
        $this->makeComplaint($this->busA);
        $this->makeComplaint($this->busA, ['status' => 'completed', 'closed_at' => now()]);

        $scope = new ReportScope([$this->garageA->id], null, false);
        $rows = $this->service->perBus($this->period(), $scope);

        $this->assertCount(1, $rows);
        $this->assertSame('MA-001', $rows->first()->bus->dqn);
        $this->assertSame(2, $rows->first()->cards_opened);
        $this->assertSame(1, $rows->first()->cards_closed);
    }

    public function test_per_bus_excludes_buses_without_activity(): void
    {
        Bus::withoutGlobalScopes()->create([
            'garage_id' => $this->garageA->id,
            'company_id' => $this->company->id,
            'dqn' => 'MA-002',
            'is_active' => true,
        ]);

        $this->makeComplaint($this->busA);

        $scope = new ReportScope([$this->garageA->id], null, false);
        $rows = $this->service->perBus($this->period(), $scope);

        $this->assertCount(1, $rows);
        $this->assertSame('MA-001', $rows->first()->bus->dqn);
    }

    // ==================================================================
    // 3. PER PART
    // ==================================================================

    public function test_per_part_groups_by_code(): void
    {
        $c = $this->makeComplaint($this->busA);
        $this->makeDetail($c, ['code' => 'A-1', 'used_quantity' => 3, 'price_at_use' => 10.00]);
        $this->makeDetail($c, ['code' => 'A-1', 'used_quantity' => 2, 'price_at_use' => 10.00]);
        $this->makeDetail($c, ['code' => 'B-1', 'used_quantity' => 1, 'price_at_use' => 50.00]);

        $scope = new ReportScope([$this->garageA->id], null, false);
        $items = $this->service->perPart($this->period(), $scope);

        $this->assertCount(2, $items);

        $itemA = $items->firstWhere('code', 'A-1');
        $this->assertSame(5, (int) $itemA->total_qty);
        $this->assertSame(50.0, (float) $itemA->total_cost);

        $itemB = $items->firstWhere('code', 'B-1');
        $this->assertSame(1, (int) $itemB->total_qty);
        $this->assertSame(50.0, (float) $itemB->total_cost);
    }

    public function test_per_part_splits_by_source_type(): void
    {
        $c = $this->makeComplaint($this->busA);
        $this->makeDetail($c, ['code' => 'SRC-1', 'used_quantity' => 5, 'source_type' => 'warehouse']);
        $this->makeDetail($c, ['code' => 'SRC-1', 'used_quantity' => 3, 'source_type' => 'service_vehicle']);
        $this->makeDetail($c, ['code' => 'SRC-1', 'used_quantity' => 2, 'source_type' => 'historical']);

        $scope = new ReportScope([$this->garageA->id], null, false);
        $items = $this->service->perPart($this->period(), $scope);
        $item = $items->firstWhere('code', 'SRC-1');

        $this->assertSame(5, (int) $item->qty_warehouse);
        $this->assertSame(3, (int) $item->qty_service_vehicle);
        $this->assertSame(2, (int) $item->qty_historical);
    }

    // ==================================================================
    // 4. MOTOR OIL
    // ==================================================================

    public function test_motor_oil_returns_only_maintenance_complaints_with_service_km(): void
    {
        $this->makeComplaint($this->busA, ['complaint_type' => ComplaintType::Maintenance->value, 'service_km' => 36000]);
        $this->makeComplaint($this->busA, ['complaint_type' => ComplaintType::Maintenance->value, 'service_km' => 72000]);
        $this->makeComplaint($this->busA, ['complaint_type' => ComplaintType::Maintenance->value]);
        $this->makeComplaint($this->busA, ['complaint_type' => ComplaintType::Breakdown->value]);

        $scope = new ReportScope([$this->garageA->id], null, false);
        $rows = $this->service->motorOil($this->period(), $scope);

        $this->assertCount(2, $rows);
    }

    public function test_motor_oil_includes_details_and_total_cost(): void
    {
        $c = $this->makeComplaint($this->busA, [
            'complaint_type' => ComplaintType::Maintenance->value,
            'service_km' => 36000,
        ]);
        $this->makeDetail($c, ['used_quantity' => 5, 'price_at_use' => 20.00]);
        $this->makeDetail($c, ['used_quantity' => 1, 'price_at_use' => 100.00]);

        $scope = new ReportScope([$this->garageA->id], null, false);
        $rows = $this->service->motorOil($this->period(), $scope);

        $this->assertCount(1, $rows);
        $this->assertSame(2, $rows->first()->parts_count);
        $this->assertSame(200.0, $rows->first()->total_cost);
    }

    // ==================================================================
    // 5. MOST REPAIRED
    // ==================================================================

    public function test_most_repaired_ranks_by_card_count(): void
    {
        $bus2 = Bus::withoutGlobalScopes()->create([
            'garage_id' => $this->garageA->id,
            'company_id' => $this->company->id,
            'dqn' => 'MA-002',
            'is_active' => true,
        ]);

        $this->makeComplaint($this->busA);
        $this->makeComplaint($this->busA);
        $this->makeComplaint($this->busA);
        $this->makeComplaint($bus2);

        $scope = new ReportScope([$this->garageA->id], null, false);
        $rows = $this->service->mostRepaired($this->period(), $scope);

        $this->assertCount(2, $rows);
        $this->assertSame('MA-001', $rows->first()->bus->dqn);
        $this->assertSame(3, $rows->first()->cards_count);
    }

    // ==================================================================
    // 6. HTTP ACCESS
    // ==================================================================

    public function test_admin_can_access_all_5_tabs(): void
    {
        $admin = User::factory()->create(['role' => 'user']);
        $admin->garages()->attach($this->garageA->id, ['role' => 'admin', 'is_active' => true]);

        $session = [
            'current_garage_id' => $this->garageA->id,
            'current_company_id' => $this->company->id,
        ];

        foreach ([
            'reports.maintenance.summary',
            'reports.maintenance.per-bus',
            'reports.maintenance.per-part',
            'reports.maintenance.motor-oil',
            'reports.maintenance.most-repaired',
        ] as $routeName) {
            $this->actingAs($admin)
                ->withSession($session)
                ->get(route($routeName))
                ->assertOk();
        }
    }

    public function test_warehouse_manager_cannot_access_maintenance_reports(): void
    {
        $manager = User::factory()->create(['role' => 'user']);
        $manager->garages()->attach($this->garageA->id, ['role' => 'warehouse_manager', 'is_active' => true]);

        $this->actingAs($manager)
            ->withSession([
                'current_garage_id' => $this->garageA->id,
                'current_company_id' => $this->company->id,
            ])
            ->get(route('reports.maintenance.summary'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('reports.maintenance.summary'))
            ->assertRedirect(route('login'));
    }
}
