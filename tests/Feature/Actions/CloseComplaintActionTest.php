<?php

namespace Tests\Feature\Actions;

use App\Actions\Complaints\CloseComplaintAction;
use App\Enums\ComplaintStatus;
use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\Garage;
use App\Services\Complaint\ComplaintPdfService;
use App\Services\Complaint\ComplaintService;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CloseComplaintActionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected Bus $bus;

    protected CloseComplaintAction $action;

    protected ComplaintService $service;

    protected ComplaintPdfService $pdfService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $this->company->id]);

        GarageContext::set($this->garage->id, $this->company->id);

        $this->bus = Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        $this->service = app(ComplaintService::class);
        $this->pdfService = app(ComplaintPdfService::class);
        $this->action = app(CloseComplaintAction::class);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    private function makeComplaint(string $status = 'pending'): Complaint
    {
        return Complaint::create([
            'bus_id' => $this->bus->id,
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
            'yer' => 'garage',
            'status' => $status,
            'complaint_type' => 'breakdown',
        ]);
    }

    private function closeData(): array
    {
        return [
            'end_date' => now()->toDateString(),
            'end_time' => now()->format('H:i'),
            'work_done' => 'Complaint resolved by test.',
        ];
    }

    // ==================================================================
    // 1. HAPPY PATH
    // ==================================================================

    public function test_it_closes_a_pending_complaint(): void
    {
        $complaint = $this->makeComplaint('pending');

        $result = $this->action->execute($complaint, $this->closeData());

        $this->assertSame(ComplaintStatus::Completed, $result->status);
        $this->assertNotNull($result->closed_at);
        $this->assertSame('Complaint resolved by test.', $result->work_done_by);
    }

    public function test_it_closes_an_in_progress_complaint(): void
    {
        $complaint = $this->makeComplaint('in_progress');

        $result = $this->action->execute($complaint, $this->closeData());

        $this->assertSame(ComplaintStatus::Completed, $result->status);
    }

    public function test_it_returns_a_fresh_model(): void
    {
        $complaint = $this->makeComplaint('pending');

        $result = $this->action->execute($complaint, $this->closeData());

        // A fresh model instance, not the same reference
        $this->assertNotSame($complaint, $result);
        $this->assertTrue($result->exists);
    }

    // ==================================================================
    // 2. GUARD — ALREADY CLOSED
    // ==================================================================

    public function test_it_rejects_closing_an_already_completed_complaint(): void
    {
        $complaint = $this->makeComplaint('completed');

        $this->expectException(ValidationException::class);

        $this->action->execute($complaint, $this->closeData());
    }

    public function test_already_closed_message_is_in_validation_errors(): void
    {
        $complaint = $this->makeComplaint('completed');

        try {
            $this->action->execute($complaint, $this->closeData());
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
            $this->assertSame(
                __('messages.flash.already_closed'),
                $e->errors()['status'][0]
            );
        }
    }

    // ==================================================================
    // 3. STATE MACHINE — CANCELLED IS TERMINAL
    // ==================================================================

    public function test_it_rejects_closing_a_cancelled_complaint(): void
    {
        $complaint = $this->makeComplaint('cancelled');

        $this->expectException(ValidationException::class);

        $this->action->execute($complaint, $this->closeData());
    }

    // ==================================================================
    // 4. PDF FAILURE DOES NOT ROLL BACK THE CLOSE
    // ==================================================================

    public function test_pdf_failure_does_not_roll_back_the_close(): void
    {
        $complaint = $this->makeComplaint('pending');

        // Force the PDF service to throw by using an anonymous subclass.
        $failingPdfService = new class extends ComplaintPdfService
        {
            public function save(Complaint $complaint): string
            {
                throw new \RuntimeException('Simulated PDF failure');
            }
        };

        $action = new CloseComplaintAction($this->service, $failingPdfService);

        $result = $action->execute($complaint, $this->closeData());

        // The complaint is still closed despite the PDF failure.
        $this->assertSame(ComplaintStatus::Completed, $result->status);
        $this->assertNotNull($result->closed_at);
    }

    public function test_pdf_failure_is_logged(): void
    {
        $complaint = $this->makeComplaint('pending');

        $failingPdfService = new class extends ComplaintPdfService
        {
            public function save(Complaint $complaint): string
            {
                throw new \RuntimeException('Simulated PDF failure');
            }
        };

        \Illuminate\Support\Facades\Log::spy();

        $action = new CloseComplaintAction($this->service, $failingPdfService);
        $action->execute($complaint, $this->closeData());

        \Illuminate\Support\Facades\Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function ($message) {
                return $message === 'PDF generation failed';
            });
    }

    // ==================================================================
    // 5. PERSISTENCE
    // ==================================================================

    public function test_changes_are_persisted_to_the_database(): void
    {
        $complaint = $this->makeComplaint('pending');

        $this->action->execute($complaint, $this->closeData());

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'completed',
        ]);
    }
}
