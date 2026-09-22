<?php

namespace Tests\Feature\Reports;

use App\Models\Bus;
use App\Models\BusDailyStatus;
use App\Models\Company;
use App\Models\DailyKmRecord;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class DailyReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Garage $garage;
    protected User $admin;
    protected Bus $bus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage  = Garage::factory()->create(['company_id' => $this->company->id]);

        $this->admin = User::factory()->create(['role' => 'user']);
        $this->admin->garages()->attach($this->garage->id, [
            'role'      => 'admin',
            'is_active' => true,
        ]);

        GarageContext::set($this->garage->id, $this->company->id);

        $this->bus = Bus::withoutGlobalScopes()->create([
            'garage_id'    => $this->garage->id,
            'company_id'   => $this->company->id,
            'dqn'          => 'EXP-001',
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
            'current_garage_id'  => $this->garage->id,
            'current_company_id' => $this->company->id,
        ];
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
    // Daily KM
    // ==================================================================

    public function test_daily_km_missing_export_downloads_xlsx(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.daily-km.missing', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertXlsxDownload($response, 'daily-km-missing-');
    }

    public function test_daily_km_top_buses_export_downloads_xlsx(): void
    {
        // Seed a couple of KM records so the report has data.
        foreach ([100000, 100500] as $i => $km) {
            DailyKmRecord::withoutGlobalScopes()->create([
                'bus_id'     => $this->bus->id,
                'garage_id'  => $this->garage->id,
                'company_id' => $this->company->id,
                'date'       => now()->subDays(1 - $i)->toDateString(),
                'km'         => $km,
            ]);
        }

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.daily-km.top-buses', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertXlsxDownload($response, 'daily-km-top-buses-');
    }

    public function test_daily_km_worker_activity_export_downloads_xlsx(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.daily-km.worker-activity', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertXlsxDownload($response, 'daily-km-worker-activity-');
    }

    // ==================================================================
    // Daily Status
    // ==================================================================

    public function test_daily_status_distribution_export_downloads_xlsx(): void
    {
        BusDailyStatus::withoutGlobalScopes()->create([
            'bus_id'     => $this->bus->id,
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
            'date'       => now()->toDateString(),
            'status'     => 'READY FOR ROUTE',
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.daily-status.distribution', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertXlsxDownload($response, 'daily-status-distribution-');
    }

    public function test_daily_status_changes_export_downloads_xlsx(): void
    {
        // Create a status change so the audit log has a row.
        $status = BusDailyStatus::withoutGlobalScopes()->create([
            'bus_id'     => $this->bus->id,
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
            'date'       => now()->toDateString(),
            'status'     => 'READY FOR ROUTE',
        ]);

        $status->update(['status' => 'IN MAINTENANCE']);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.daily-status.changes', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertXlsxDownload($response, 'daily-status-changes-');
    }

    public function test_daily_status_worker_activity_export_downloads_xlsx(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.daily-status.worker-activity', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertXlsxDownload($response, 'daily-status-worker-activity-');
    }

    // ==================================================================
    // Director-level
    // ==================================================================

    public function test_director_can_export_daily_km_missing(): void
    {
        $director = User::factory()->create(['role' => 'user']);
        $this->company->users()->attach($director->id, [
            'role'      => 'director',
            'is_active' => true,
        ]);

        $response = $this->actingAs($director)
            ->get(route('director.reports.daily-km.missing', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertXlsxDownload($response, 'director-daily-km-missing-');
    }

    public function test_director_can_export_daily_status_distribution(): void
    {
        $director = User::factory()->create(['role' => 'user']);
        $this->company->users()->attach($director->id, [
            'role'      => 'director',
            'is_active' => true,
        ]);

        $response = $this->actingAs($director)
            ->get(route('director.reports.daily-status.distribution', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertXlsxDownload($response, 'director-daily-status-distribution-');
    }

    // ==================================================================
    // Non-export fallback
    // ==================================================================

    public function test_daily_km_missing_without_export_returns_view(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.daily-km.missing'));

        $response->assertOk();
        $response->assertViewIs('reports.daily-km.missing');
    }

    public function test_daily_status_distribution_without_export_returns_view(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.daily-status.distribution'));

        $response->assertOk();
        $response->assertViewIs('reports.daily-status.distribution');
    }

    // ==================================================================
    // Access Control
    // ==================================================================

    public function test_guest_cannot_export_daily_km(): void
    {
        $this->get(route('reports.daily-km.missing', ['export' => 'xlsx']))
            ->assertRedirect(route('login'));
    }

    public function test_warehouse_manager_cannot_export_daily_km(): void
    {
        $manager = User::factory()->create(['role' => 'user']);
        $manager->garages()->attach($this->garage->id, [
            'role'      => 'warehouse_manager',
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->withSession($this->garageSession())
            ->get(route('reports.daily-km.missing', ['export' => 'xlsx']))
            ->assertForbidden();
    }
}
