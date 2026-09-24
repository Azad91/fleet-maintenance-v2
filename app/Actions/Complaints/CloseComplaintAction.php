<?php

namespace App\Actions\Complaints;

use App\Models\Complaint;
use App\Services\Complaint\ComplaintPdfService;
use App\Services\Complaint\ComplaintService;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Closes a complaint and generates the corresponding PDF.
 *
 * Extracted from ComplaintController::close() and
 * Api\ComplaintController::close() — both controllers were
 * orchestrating the same five steps:
 *
 *   1. Guard against closing an already-completed complaint
 *   2. Delegate the state transition to ComplaintService
 *   3. Generate the PDF
 *   4. Log PDF failures without rolling back the close
 *   5. Return the fresh model
 *
 * HTTP concerns (route, redirect, response) stay in the controllers.
 */
class CloseComplaintAction
{
    public function __construct(
        protected ComplaintService $complaintService,
        protected ComplaintPdfService $pdfService
    ) {}

    /**
     * Close the complaint and try to generate its PDF.
     *
     * The PDF step is best-effort: if generation fails, the complaint
     * remains closed and the failure is logged. PDF failures never
     * prevent the business operation from completing.
     *
     * @param  array{end_date: string, end_time: string, work_done: string}  $data
     *
     * @throws ValidationException when the status transition is illegal
     *                             (e.g. closing a cancelled complaint).
     */
    public function execute(Complaint $complaint, array $data): Complaint
    {
        if ($complaint->status?->isCompleted()) {
            throw ValidationException::withMessages([
                'status' => __('messages.flash.already_closed'),
            ]);
        }

        // Delegates to ComplaintService::close() which validates the
        // transition and persists the change.
        $this->complaintService->close($complaint, $data);

        $this->generatePdf($complaint);

        return $complaint->fresh();
    }

    /**
     * Generate the PDF as a side effect. Any failure is logged but
     * does not propagate — the complaint is already closed.
     *
     * If generation fails AND a stale PDF from a previous state still
     * exists on disk, we attempt to remove it so the operator cannot
     * accidentally download a pre-close version. This cleanup is also
     * best-effort.
     */
    protected function generatePdf(Complaint $complaint): void
    {
        try {
            $this->pdfService->save($complaint);
        } catch (\Throwable $e) {
            // Best-effort cleanup of any stale PDF left over from a
            // previous (pre-close) state.
            try {
                $this->pdfService->delete($complaint);
            } catch (\Throwable $deleteFailure) {
                Log::warning('Failed to clean up stale complaint PDF after generation failure', [
                    'complaint_id' => $complaint->id,
                    'error' => $deleteFailure->getMessage(),
                    'request_id' => Context::get('request_id'),
                ]);
            }

            Log::error('PDF generation failed', [
                'complaint_id' => $complaint->id,
                'error' => $e->getMessage(),
                'request_id' => Context::get('request_id'),
                'user_id' => auth()->id(),
            ]);
        }
    }
}
