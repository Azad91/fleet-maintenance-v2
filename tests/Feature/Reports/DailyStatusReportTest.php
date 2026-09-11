<?php

namespace Tests\Feature\Reports;

use App\Models\Bus;
use App\Models\BusDailyStatus;
use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use App\Services\Reports\DailyStatusReportService;
use App\Services\Reports\ReportPeriod;
use App\Services\Reports\ReportScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyStatusReportTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garageA;

    protected Garage $garageB;

    protected Bus $busA;

    protected Bus $busB;

    protected DailyStatusReportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garageA = Garage::factory()->create(['company_id' => $this->company->id]);
        $this->garageB = Garage::factory()->create(['company_id' => $this->company->id]);

        GarageContext::set($this->garageA->id, $this->company->id);

        $this->busA = Bus::withoutGlobalScopes()->create([
            'garage_id'  => $this->garageA->id,
            'company_id' => $this->company->id,
            'dqn'        => 'AAA-001',
            'is_active'  => true,
        ]);

        $this->busB = Bus::withoutGlobalScopes()->create([
            'garage_id'  => $this->garageB->id,
            'company_id' => $this->company->id,
            'dqn'        => 'BBB-001',
            'is_active'  => true,
        ]);

        $this->service = app(DailyStatusReportService::class);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    private function period(): ReportPeriod
    {
        return new ReportPeriod(now()->startOfMonth(), now()->endOfMonth(), 'monthly');
    }

    // ==================== DISTRIBUTION ====================

    public function test_distribution_groups_statuses_correctly(): void
    {
        BusDailyStatus::withoutGlobalScopes()->create([
            'bus_id'     => $this->busA->id,
            'garage_id'  => $this->garageA->id,
            'company_id' => $this->company->id,
            'date'       => now()->toDateString(),
            'status'     => 'READY',
        ]);
        BusDailyStatus::withoutGlobalScopes()->create([
            'bus_id'     => $this->busA->id,
            'garage_id'  => $this->garageA->id,
            'company_id' => $this->company->id,
            'date'       => now()->subDay()->toDateString(),
            'status'     => 'READY',
        ]);
        BusDailyStatus::withoutGlobalScopes()->create([
            'bus_id'     => $this->busA->id,
            'garage_id'  => $this->garageA->id,
            'company_id' => $this->company->id,
            'date'       => now()->subDays(2)->toDateString(),
            'status'     => 'REPAIR',
        ]);

        // Other garage
        BusDailyStatus::withoutGlobalScopes()->create([
            'bus_id'     => $this->busB->id,
            'garage_id'  => $this->garageB->id,
            'company_id' => $this->company->id,
            'date'       => now()->toDateString(),
            'status'     => 'READY',
        ]);

        $scope = new ReportScope([$this->garageA->id], null, false);

        $data = $this->service->distribution($this->period(), $scope);

        $this->assertSame(3, $data['total']);
        $this->assertCount(2, $data['rows']);

        $readyRow = $data['rows']->firstWhere('status', 'READY');
        $this->assertSame(2, (int) $readyRow->total);

        $repairRow = $data['rows']->firstWhere('status', 'REPAIR');
        $this->assertSame(1, (int) $repairRow->total);
    }

    // ==================== CHANGES ====================

    public function test_changes_only_shows_current_garage_audit_logs(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        BusDailyStatus::withoutGlobalScopes()->create([
            'bus_id'     => $this->busA->id,
            'garage_id'  => $this->garageA->id,
            'company_id' => $this->company->id,
            'date'       => now()->toDateString(),
            'status'     => 'READY',
        ]);

        BusDailyStatus::withoutGlobalScopes()->create([
            'bus_id'     => $this->busB->id,
            'garage_id'  => $this->garageB->id,
            'company_id' => $this->company->id,
            'date'       => now()->toDateString(),
            'status'     => 'READY',
        ]);

        $scope = new ReportScope([$this->garageA->id], null, false);

        $logs = $this->service->changes($this->period(), $scope);

        foreach ($logs as $log) {
            $this->assertSame(
                $this->garageA->id,
                $log->garage_id,
                'Status change log leaked from another garage'
            );
        }
    }

    // ==================== WORKER ACTIVITY ====================

    public function test_worker_activity_isolated_by_garage(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        BusDailyStatus::withoutGlobalScopes()->create([
            'bus_id'     => $this->busA->id,
            'garage_id'  => $this->garageA->id,
            'company_id' => $this->company->id,
            'date'       => now()->toDateString(),
            'status'     => 'READY',
        ]);

        BusDailyStatus::withoutGlobalScopes()->create([
            'bus_id'     => $this->busB->id,
            'garage_id'  => $this->garageB->id,
            'company_id' => $this->company->id,
            'date'       => now()->toDateString(),
            'status'     => 'READY',
        ]);

        $scope = new ReportScope([$this->garageA->id], null, false);

        $rows = $this->service->workerActivity($this->period(), $scope);

        $total = $rows->sum('total_actions');
        $this->assertSame(1, $total, 'Cross-tenant audit leak');
    }
}
