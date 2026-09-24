<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\ComplaintItem;
use App\Models\Employee;
use App\Models\Garage;
use App\Services\Complaint\ComplaintService;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Regression tests for the stale PDF bug:
 *
 * A complaint's PDF is a snapshot of its state at a specific point
 * in time. When the underlying complaint is updated or deleted, the
 * on-disk PDF must be invalidated so the operator never downloads
 * an outdated legal document.
 */
class ComplaintPdfInvalidationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected Bus $bus;

    protected Employee $employee;

    protected ComplaintService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Use an in-memory disk so we can assert against the "akt"
        // directory without touching the real filesystem.
        Storage::fake('local');

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $this->company->id]);

        GarageContext::set($this->garage->id, $this->company->id);

        $this->bus = Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        $this->employee = Employee::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        $this->service = app(ComplaintService::class);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    // ==================================================================
    // HELPERS
    // ==================================================================

    protected function makeComplaint(): Complaint
    {
        return Complaint::create([
            'bus_id' => $this->bus->id,
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'yer' => 'garage',
            'status' => 'pending',
            'complaint_type' => 'breakdown',
            'km' => 1000,
        ]);
    }

    protected function pdfPath(Complaint $complaint): string
    {
        return "akt/akt-{$complaint->id}.pdf";
    }

    protected function seedFakePdf(Complaint $complaint): void
    {
        Storage::disk('local')->put($this->pdfPath($complaint), 'fake-pdf-content');
    }

    protected function pdfExists(Complaint $complaint): bool
    {
        return Storage::disk('local')->exists($this->pdfPath($complaint));
    }

    protected function baseData(): array
    {
        return [
            'bus_id' => $this->bus->id,
            'yer' => 'garage',
            'status' => 'pending',
            'complaint_type' => 'breakdown',
            'km' => 1000,
        ];
    }

    // ==================================================================
    // 1. UPDATE invalidates the PDF
    // ==================================================================

    public function test_update_deletes_stale_pdf(): void
    {
        $complaint = $this->makeComplaint();
        $this->seedFakePdf($complaint);

        $this->assertTrue($this->pdfExists($complaint), 'Setup: fake PDF must exist');

        $this->service->update($complaint, $this->baseData(), [], ['Updated complaint']);

        $this->assertFalse(
            $this->pdfExists($complaint),
            'Stale PDF must be deleted after a successful update'
        );
    }

    public function test_update_without_existing_pdf_does_not_crash(): void
    {
        $complaint = $this->makeComplaint();

        // No PDF seeded — invalidate should be a silent no-op.
        $this->service->update($complaint, $this->baseData(), [], ['Update']);

        $this->assertFalse($this->pdfExists($complaint));
    }

    // ==================================================================
    // 2. UPDATE failure does NOT invalidate
    // ==================================================================

    public function test_update_failure_does_not_delete_pdf(): void
    {
        $complaint = $this->makeComplaint();
        $this->seedFakePdf($complaint);

        // Force the inner transaction to fail AFTER the complaint row
        // is updated. syncItems() creates ComplaintItem rows, so a
        // `creating` listener is the most reliable injection point.
        ComplaintItem::creating(function () {
            throw new \RuntimeException('Simulated failure');
        });

        try {
            $this->service->update(
                $complaint,
                $this->baseData(),
                [],
                ['This will fail']
            );
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated failure', $e->getMessage());
        }

        $this->assertTrue(
            $this->pdfExists($complaint),
            'PDF must survive when the update transaction rolls back'
        );
    }

    // ==================================================================
    // 3. DELETE invalidates the PDF
    // ==================================================================

    public function test_delete_deletes_stale_pdf(): void
    {
        $complaint = $this->makeComplaint();
        $this->seedFakePdf($complaint);

        $this->assertTrue($this->pdfExists($complaint));

        $this->service->delete($complaint);

        $this->assertFalse(
            $this->pdfExists($complaint),
            'Stale PDF must be deleted after a successful delete'
        );
    }

    public function test_bulk_delete_deletes_all_pdfs(): void
    {
        $c1 = $this->makeComplaint();
        $c2 = $this->makeComplaint();

        $this->seedFakePdf($c1);
        $this->seedFakePdf($c2);

        $this->service->bulkDelete([$c1->id, $c2->id]);

        $this->assertFalse($this->pdfExists($c1), 'PDF for c1 must be deleted');
        $this->assertFalse($this->pdfExists($c2), 'PDF for c2 must be deleted');
    }

    // ==================================================================
    // 4. CLOSE does not delete the PDF directly
    //    (CloseComplaintAction regenerates it via save())
    // ==================================================================

    public function test_close_does_not_delete_existing_pdf(): void
    {
        $complaint = $this->makeComplaint();
        $this->seedFakePdf($complaint);

        $this->service->close($complaint, [
            'end_date' => now()->toDateString(),
            'end_time' => now()->format('H:i'),
            'work_done' => 'Fixed.',
        ]);

        $this->assertTrue(
            $this->pdfExists($complaint),
            'close() must not delete the PDF — CloseComplaintAction regenerates it'
        );
    }
}
