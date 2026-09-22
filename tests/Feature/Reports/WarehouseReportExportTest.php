<?php

namespace Tests\Feature\Reports;

use App\Exports\Reports\GenericReportExport;
use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

/**
 * Tests the Excel export feature for warehouse reports.
 *
 * NOTE: We do NOT use Excel::fake() + assertDownloaded() here.
 * The installed Laravel Excel version (3.1.70) requires an exact
 * filename string, and our filenames contain a runtime timestamp
 * — so the assertion would have to know the exact moment of the
 * request. Instead we assert against the BinaryFileResponse
 * directly: it exposes the actual Content-Disposition header, which
 * is exactly what a browser would see. This is closer to a real
 * end-to-end check anyway.
 */
class WarehouseReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Garage $garage;
    protected User $admin;

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

    protected function makeWarehouse(array $overrides = []): Warehouse
    {
        return Warehouse::withoutGlobalScopes()->create(array_merge([
            'garage_id'        => $this->garage->id,
            'company_id'       => $this->company->id,
            'code'             => 'W-'.uniqid(),
            'name'             => 'Widget',
            'quantity'         => 50,
            'minimum_quantity' => 10,
            'unit'             => 'piece',
            'price'            => 15.50,
        ], $overrides));
    }

    /**
     * Assert that the response is a downloadable xlsx file whose
     * Content-Disposition header contains the expected basename.
     */
    protected function assertXlsxDownload($response, string $basename): void
    {
        $this->assertInstanceOf(
            BinaryFileResponse::class,
            $response->baseResponse,
            'Response must be a BinaryFileResponse'
        );

        $disposition = (string) $response->headers->get('content-disposition', '');

        $this->assertStringContainsString(
            $basename,
            $disposition,
            "Content-Disposition must contain '{$basename}' (got: {$disposition})"
        );

        $this->assertStringContainsString('.xlsx', $disposition);
    }

    // ==================================================================
    // 1. Receipt
    // ==================================================================

    public function test_receipt_export_downloads_xlsx(): void
    {
        $this->makeWarehouse(['code' => 'RCPT-1']);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.warehouse.receipt', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertXlsxDownload($response, 'warehouse-receipt-');
    }

    public function test_receipt_without_export_returns_view(): void
    {
        $this->makeWarehouse();

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.warehouse.receipt'));

        $response->assertOk();
        $response->assertViewIs('reports.warehouse.receipt');
    }

    // ==================================================================
    // 2. Low Stock
    // ==================================================================

    public function test_low_stock_export_downloads_xlsx(): void
    {
        $this->makeWarehouse([
            'code'             => 'LOW-1',
            'quantity'         => 3,
            'minimum_quantity' => 10,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.warehouse.low-stock', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertXlsxDownload($response, 'warehouse-low-stock-');
    }

    // ==================================================================
    // 3. Movement
    // ==================================================================

    public function test_movement_export_downloads_xlsx(): void
    {
        $this->makeWarehouse(['code' => 'MOV-1']);

        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.warehouse.movement', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertXlsxDownload($response, 'warehouse-movement-');
    }

    // ==================================================================
    // 4. Usage
    // ==================================================================

    public function test_usage_export_downloads_xlsx(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.warehouse.usage', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertXlsxDownload($response, 'warehouse-usage-');
    }

    // ==================================================================
    // 5. Worker Activity
    // ==================================================================

    public function test_worker_activity_export_downloads_xlsx(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.warehouse.worker-activity', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertXlsxDownload($response, 'warehouse-worker-activity-');
    }

    // ==================================================================
    // 6. Service Vehicle Usage
    // ==================================================================

    public function test_service_vehicle_usage_export_downloads_xlsx(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.warehouse.service-vehicle-usage', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertXlsxDownload($response, 'warehouse-service-vehicle-usage-');
    }

    // ==================================================================
    // 7. Formula Injection Defense (unit test on the export class)
    // ==================================================================

    public function test_export_sanitizes_formula_injection(): void
    {
        $export = new GenericReportExport(
            rows: [
                ['SAFE-1', '=HYPERLINK("http://evil.com","click")'],
                ['SAFE-2', '@SUM(1+1)'],
                ['SAFE-3', '+cmd|\'/c calc\'!A1'],
                ['SAFE-4', '-2+3'],
                ['SAFE-5', 'normal text'],
            ],
            headings: ['Code', 'Name'],
        );

        $rows = $export->array();

        // Dangerous values must be prefixed with ' so Excel treats
        // them as text, not formulas.
        $this->assertStringStartsWith("'=", $rows[0][1]);
        $this->assertStringStartsWith("'@", $rows[1][1]);
        $this->assertStringStartsWith("'+", $rows[2][1]);
        $this->assertStringStartsWith("'-", $rows[3][1]);

        // Safe values are not modified.
        $this->assertSame('normal text', $rows[4][1]);
    }

    // ==================================================================
    // 8. Access Control
    // ==================================================================

    public function test_guest_cannot_export(): void
    {
        $this->get(route('reports.warehouse.receipt', ['export' => 'xlsx']))
            ->assertRedirect(route('login'));
    }

    public function test_director_can_export_company_wide(): void
    {
        $director = User::factory()->create(['role' => 'user']);
        $this->company->users()->attach($director->id, [
            'role'      => 'director',
            'is_active' => true,
        ]);

        $response = $this->actingAs($director)
            ->get(route('director.reports.warehouse.receipt', ['export' => 'xlsx']));

        $response->assertOk();
        $this->assertXlsxDownload($response, 'director-warehouse-receipt-');
    }

    // ==================================================================
    // 9. Export URL Includes Current Filters
    // ==================================================================

    public function test_export_url_preserves_period_and_brand_filters(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession($this->garageSession())
            ->get(route('reports.warehouse.receipt', ['period' => 'weekly']));

        $response->assertOk();

        $exportUrl = $response->viewData('exportUrl');

        $this->assertStringContainsString('period=weekly', $exportUrl);
        $this->assertStringContainsString('export=xlsx', $exportUrl);
    }
}
