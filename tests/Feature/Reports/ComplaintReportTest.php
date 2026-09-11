<?php

namespace Tests\Feature\Reports;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use App\Services\Reports\ComplaintReportService;
use App\Services\Reports\ReportPeriod;
use App\Services\Reports\ReportScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ComplaintReportTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garageA;

    protected Garage $garageB;

    protected Bus $busA;

    protected Bus $busB;

    protected ComplaintReportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garageA = Garage::factory()->create(['company_id' => $this->company->id]);
        $this->garageB = Garage::factory()->create(['company_id' => $this->company->id]);

        GarageContext::set($this->garageA->id, $this->company->id);

        $this->busA = Bus::withoutGlobalScopes()->create([
            'garage_id'    => $this->garageA->id,
            'company_id'   => $this->company->id,
            'dqn'          => 'AA-001',
            'route_number' => '101',
            'is_active'    => true,
        ]);

        $this->busB = Bus::withoutGlobalScopes()->create([
            'garage_id'    => $this->garageB->id,
            'company_id'   => $this->company->id,
            'dqn'          => 'BB-001',
            'route_number' => '202',
            'is_active'    => true,
        ]);

        $this->service = app(ComplaintReportService::class);
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

    // ==================== SUMMARY ====================

    public function test_summary_counts_only_current_garage(): void
    {
        // Garage A: 2 open
        Complaint::withoutGlobalScopes()->create([
            'bus_id'         => $this->busA->id,
            'garage_id'      => $this->garageA->id,
            'company_id'     => $this->company->id,
            'yer'            => 'garage',
            'status'         => 'pending',
            'complaint_type' => 'breakdown',
        ]);
        Complaint::withoutGlobalScopes()->create([
            'bus_id'         => $this->busA->id,
            'garage_id'      => $this->garageA->id,
            'company_id'     => $this->company->id,
            'yer'            => 'garage',
            'status'         => 'in_progress',
            'complaint_type' => 'breakdown',
        ]);

        // Garage B: 1 open
        Complaint::withoutGlobalScopes()->create([
            'bus_id'         => $this->busB->id,
            'garage_id'      => $this->garageB->id,
            'company_id'     => $this->company->id,
            'yer'            => 'garage',
            'status'         => 'pending',
            'complaint_type' => 'breakdown',
        ]);

        $scope = new ReportScope([$this->garageA->id], null, false);

        $summary = $this->service->summary($this->period(), $scope);

        $this->assertSame(2, $summary['opened']);
        $this->assertSame(2, $summary['open_now']);
    }

    // ==================== TOP TYPES ====================

    public function test_top_types_isolated_by_garage(): void
    {
        Complaint::withoutGlobalScopes()->create([
            'bus_id'         => $this->busA->id,
            'garage_id'      => $this->garageA->id,
            'company_id'     => $this->company->id,
            'yer'            => 'garage',
            'status'         => 'pending',
            'complaint_type' => 'breakdown',
        ]);
        Complaint::withoutGlobalScopes()->create([
            'bus_id'         => $this->busA->id,
            'garage_id'      => $this->garageA->id,
            'company_id'     => $this->company->id,
            'yer'            => 'garage',
            'status'         => 'pending',
            'complaint_type' => 'breakdown',
        ]);
        Complaint::withoutGlobalScopes()->create([
            'bus_id'         => $this->busA->id,
            'garage_id'      => $this->garageA->id,
            'company_id'     => $this->company->id,
            'yer'            => 'garage',
            'status'         => 'pending',
            'complaint_type' => 'accident',
        ]);
        // Garage B: 5 of maintenance (should NOT appear)
        for ($i = 0; $i < 5; $i++) {
            Complaint::withoutGlobalScopes()->create([
                'bus_id'         => $this->busB->id,
                'garage_id'      => $this->garageB->id,
                'company_id'     => $this->company->id,
                'yer'            => 'garage',
                'status'         => 'pending',
                'complaint_type' => 'maintenance',
            ]);
        }

        $scope = new ReportScope([$this->garageA->id], null, false);

        $rows = $this->service->topTypes($this->period(), $scope);

        $this->assertCount(2, $rows);
        $this->assertSame('breakdown', $rows->first()->complaint_type);
        $this->assertSame(2, (int) $rows->first()->total);
        $this->assertFalse($rows->contains('complaint_type', 'maintenance'));
    }

    // ==================== BY BUS ====================

    public function test_by_bus_groups_correctly(): void
    {
        Complaint::withoutGlobalScopes()->create([
            'bus_id'         => $this->busA->id,
            'garage_id'      => $this->garageA->id,
            'company_id'     => $this->company->id,
            'yer'            => 'garage',
            'status'         => 'completed',
            'complaint_type' => 'breakdown',
        ]);
        Complaint::withoutGlobalScopes()->create([
            'bus_id'         => $this->busA->id,
            'garage_id'      => $this->garageA->id,
            'company_id'     => $this->company->id,
            'yer'            => 'garage',
            'status'         => 'pending',
            'complaint_type' => 'breakdown',
        ]);

        $scope = new ReportScope([$this->garageA->id], null, false);

        $rows = $this->service->byBus($this->period(), $scope);

        $this->assertCount(1, $rows);
        $this->assertSame('AA-001', $rows->first()->dqn);
        $this->assertSame(2, (int) $rows->first()->total);
        $this->assertSame(1, (int) $rows->first()->completed);
    }

    // ==================== AVG CLOSE TIME ====================

    public function test_avg_close_time_with_empty_data(): void
    {
        $scope = new ReportScope([$this->garageA->id], null, false);

        $data = $this->service->avgCloseTime($this->period(), $scope);

        $this->assertNull($data['overall_avg_hours']);
        $this->assertSame(0, $data['sample_count']);
    }

    public function test_avg_close_time_computes_correctly(): void
    {
        $complaint = Complaint::withoutGlobalScopes()->create([
            'bus_id'         => $this->busA->id,
            'garage_id'      => $this->garageA->id,
            'company_id'     => $this->company->id,
            'yer'            => 'garage',
            'status'         => 'completed',
            'complaint_type' => 'breakdown',
        ]);

        // Force historical timestamps via direct DB write.
        // `created_at` is guarded by Laravel's automatic timestamp handling
        // on create() — passing it in the create array would be silently
        // overwritten by now().
        DB::table('complaints')->where('id', $complaint->id)->update([
            'created_at' => now()->subDays(3),
            'closed_at'  => now()->subDay(),
        ]);

        $scope = new ReportScope([$this->garageA->id], null, false);

        $data = $this->service->avgCloseTime($this->period(), $scope);

        $this->assertSame(1, $data['sample_count']);
        $this->assertSame(48.0, $data['overall_avg_hours']);
    }
}
