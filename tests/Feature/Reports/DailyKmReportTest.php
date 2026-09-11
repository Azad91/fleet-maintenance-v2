<?php

namespace Tests\Feature\Reports;

use App\Models\Bus;
use App\Models\Company;
use App\Models\DailyKmRecord;
use App\Models\Garage;
use App\Services\GarageContext;
use App\Services\Reports\DailyKmReportService;
use App\Services\Reports\ReportPeriod;
use App\Services\Reports\ReportScope;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyKmReportTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garageA;

    protected Garage $garageB;

    protected Bus $busA;

    protected Bus $busB;

    protected DailyKmReportService $service;

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
            'km'         => 10000,
        ]);

        $this->busB = Bus::withoutGlobalScopes()->create([
            'garage_id'  => $this->garageB->id,
            'company_id' => $this->company->id,
            'dqn'        => 'BBB-001',
            'is_active'  => true,
            'km'         => 20000,
        ]);

        $this->service = app(DailyKmReportService::class);
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

    // ==================== MISSING ====================

    public function test_missing_returns_buses_without_km_on_target_date(): void
    {
        // Bus A: has a KM entry today
        DailyKmRecord::withoutGlobalScopes()->create([
            'bus_id'     => $this->busA->id,
            'garage_id'  => $this->garageA->id,
            'company_id' => $this->company->id,
            'date'       => now()->toDateString(),
            'km'         => 10500,
        ]);

        // Bus B: no entry today — but is in another garage → shouldn't appear
        $scope = new ReportScope([$this->garageA->id], null, false);

        $buses = $this->service->missing($this->period(), $scope);

        $this->assertEmpty($buses, 'Bus A has entry; Bus B is other garage; nothing should be missing');
    }

    public function test_missing_detects_buses_without_entry(): void
    {
        // No records → both buses missing, but scope limits to garage A
        $scope = new ReportScope([$this->garageA->id], null, false);

        $buses = $this->service->missing($this->period(), $scope);

        $this->assertCount(1, $buses);
        $this->assertSame('AAA-001', $buses->first()->dqn);
    }

    // ==================== TOP BUSES ====================

    public function test_top_buses_computes_distance_correctly(): void
    {
        // Bus A: 1000 km driven (start 10000, end 11000)
        DailyKmRecord::withoutGlobalScopes()->create([
            'bus_id'     => $this->busA->id,
            'garage_id'  => $this->garageA->id,
            'company_id' => $this->company->id,
            'date'       => now()->startOfMonth()->toDateString(),
            'km'         => 10000,
        ]);
        DailyKmRecord::withoutGlobalScopes()->create([
            'bus_id'     => $this->busA->id,
            'garage_id'  => $this->garageA->id,
            'company_id' => $this->company->id,
            'date'       => now()->toDateString(),
            'km'         => 11000,
        ]);

        $scope = new ReportScope([$this->garageA->id], null, false);

        $rows = $this->service->topBuses($this->period(), $scope);

        $this->assertCount(1, $rows);
        $this->assertSame('AAA-001', $rows->first()->dqn);
        $this->assertSame(1000, $rows->first()->distance);
    }

    public function test_top_buses_does_not_leak_across_garages(): void
    {
        DailyKmRecord::withoutGlobalScopes()->create([
            'bus_id'     => $this->busB->id,
            'garage_id'  => $this->garageB->id,
            'company_id' => $this->company->id,
            'date'       => now()->toDateString(),
            'km'         => 25000,
        ]);

        $scope = new ReportScope([$this->garageA->id], null, false);

        $rows = $this->service->topBuses($this->period(), $scope);

        $this->assertEmpty($rows, 'Cross-tenant leak in top buses report');
    }

    // ==================== WORKER ACTIVITY ====================

    public function test_worker_activity_isolated_by_garage(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        DailyKmRecord::withoutGlobalScopes()->create([
            'bus_id'     => $this->busA->id,
            'garage_id'  => $this->garageA->id,
            'company_id' => $this->company->id,
            'date'       => now()->toDateString(),
            'km'         => 15000,
        ]);

        DailyKmRecord::withoutGlobalScopes()->create([
            'bus_id'     => $this->busB->id,
            'garage_id'  => $this->garageB->id,
            'company_id' => $this->company->id,
            'date'       => now()->toDateString(),
            'km'         => 25000,
        ]);

        $scope = new ReportScope([$this->garageA->id], null, false);

        $rows = $this->service->workerActivity($this->period(), $scope);

        $total = $rows->sum('total_actions');
        $this->assertSame(1, $total, 'Cross-tenant audit leak');
    }
}
