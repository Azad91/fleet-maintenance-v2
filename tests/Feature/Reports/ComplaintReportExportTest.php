<?php

namespace Tests\Feature\Reports;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class ComplaintReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Garage $garageA;
    protected Garage $garageB;
    protected User $admin;
    protected Bus $bus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garageA = Garage::factory()->create(['company_id' => $this->company->id]);
        $this->garageB = Garage::factory()->create(['company_id' => $this->company->id]);

        $this->admin = User::factory()->create(['role' => 'user']);
        $this->admin->garages()->attach($this->garageA->id, [
            'role'      => 'admin',
            'is_active' => true,
        ]);

        GarageContext::set($this->garageA->id, $this->company->id);

        $this->bus = Bus::withoutGlobalScopes()->create([
            'garage_id'    => $this->garageA->id,
            'company_id'   => $this->company->id,
            'dqn'          => 'CMP-001',
            'route_number' => '101',
            'is_active'    => true,
        ]);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    protected function garageSession(): array
    {
        return [
            'current_garage_id'  => $this->garageA->id,
            'current_company_id' => $this->company->id,
        ];
    }

    protected function makeComplaint(array $overrides = []): Complaint
    {
        return Complaint::withoutGlobalScopes()->create(array_merge([
            'bus_id'         => $this->bus->id,
            'garage_id'      => $this->garageA->id,
            'company_id'     => $this->company->id,
            'yer'            => 'garage',
            'status'         => 'pending',
            'complaint_type' => 'breakdown',
            'created_at'     => now(),
        ], $overrides));
    }

    protected function assertXlsxDownload($response, string $basename): void
    {
        $this->assertInstanceOf(
            BinaryFileResponse::class,
            $response->baseResponse,
            'Response must be a BinaryFileResponse'
        );

        $disposition = (string) $response->headers->get('content-disposition', '');

        $this->assertStringContainsString($basename, $disposition);
        $this->assertStringContainsString('.xlsx', $disposition);
    }

    // ==================================================================
    // 1. Summary
    // ==================================================================

    public function test_summary_export_downloads_xlsx(): void
    {
        $this->makeComplaint();

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.complaint.summary', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertXlsxDownload($response, 'complaint-summary-');
    }

    // ==================================================================
    // 2. Top Types
    // ==================================================================

    public function test_top_types_export_downloads_xlsx(): void
    {
        $this->makeComplaint(['complaint_type' => 'breakdown']);
        $this->makeComplaint(['complaint_type' => 'accident']);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.complaint.top-types', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertXlsxDownload($response, 'complaint-top-types-');
    }

    // ==================================================================
    // 3. Worker Activity
    // ==================================================================

    public function test_worker_activity_export_downloads_xlsx(): void
    {
        $this->makeComplaint();

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.complaint.worker-activity', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertXlsxDownload($response, 'complaint-worker-activity-');
    }

    // ==================================================================
    // 4. By Bus
    // ==================================================================

    public function test_by_bus_export_downloads_xlsx(): void
    {
        $this->makeComplaint();

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.complaint.by-bus', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertXlsxDownload($response, 'complaint-by-bus-');
    }

    // ==================================================================
    // 5. Average Close Time
    // ==================================================================

    public function test_avg_close_time_export_downloads_xlsx(): void
    {
        $this->makeComplaint([
            'status'    => 'completed',
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.complaint.avg-close-time', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertXlsxDownload($response, 'complaint-avg-close-time-');
    }

    // ==================================================================
    // 6. Non-export request returns view
    // ==================================================================

    public function test_summary_without_export_returns_view(): void
    {
        $this->makeComplaint();

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.complaint.summary'));

        $response->assertOk();
        $response->assertViewIs('reports.complaint.summary');
    }

    // ==================================================================
    // 7. Director export
    // ==================================================================

    public function test_director_can_export_complaint_summary(): void
    {
        $director = User::factory()->create(['role' => 'user']);
        $this->company->users()->attach($director->id, [
            'role'      => 'director',
            'is_active' => true,
        ]);

        $response = $this->actingAs($director)
            ->get(route('director.reports.complaint.summary', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertXlsxDownload($response, 'director-complaint-summary-');
    }

    // ==================================================================
    // 8. Access Control
    // ==================================================================

    public function test_guest_cannot_export(): void
    {
        $this->get(route('reports.complaint.summary', ['export' => 'xlsx']))
            ->assertRedirect(route('login'));
    }

    public function test_warehouse_manager_cannot_access_complaint_export(): void
    {
        $manager = User::factory()->create(['role' => 'user']);
        $manager->garages()->attach($this->garageA->id, [
            'role'      => 'warehouse_manager',
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->withSession($this->garageSession())
            ->get(route('reports.complaint.summary', ['export' => 'xlsx']))
            ->assertForbidden();
    }
}
