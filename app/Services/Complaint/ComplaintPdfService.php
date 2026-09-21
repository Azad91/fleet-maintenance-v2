<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ComplaintPdfService
{
    private const PDF_DIR = 'akt';

    public function generate(Complaint $complaint): \Barryvdh\DomPDF\PDF
    {
        $complaint->loadMissing(['details.employee', 'bus', 'creator', 'closer', 'serviceVehicle']);

        // Collect every related employee id — both from details and
        // from the top-level employee_id field.
        $employeeIds = $complaint->details
            ->pluck('employee_id')
            ->filter()
            ->unique();

        if ($complaint->employee_id) {
            $employeeIds->push($complaint->employee_id);
        }

        // Filter by the complaint's own garage_id so a detail row that
        // (through an old bug or manual DB edit) references an employee
        // from another tenant cannot leak that employee's name into
        // the PDF. Soft-deleted employees are still resolved so
        // historical complaints render their original names.
        $employeesById = Employee::withoutGlobalScopes()
            ->where('garage_id', $complaint->garage_id)
            ->whereIn('id', $employeeIds->unique()->values())
            ->get()
            ->keyBy('id');

        return Pdf::loadView('complaints.akt', [
            'complaint' => $complaint,
            'company' => $complaint->company,
            'garage' => $complaint->garage,
            'employeesById' => $employeesById,
        ]);
    }

    public function save(Complaint $complaint): string
    {
        $pdf = $this->generate($complaint);
        $relativePath = self::PDF_DIR."/akt-{$complaint->id}.pdf";

        Storage::disk('local')->put($relativePath, $pdf->output());

        return Storage::disk('local')->path($relativePath);
    }

    public function getFilePath(Complaint $complaint): string
    {
        return Storage::disk('local')->path(
            self::PDF_DIR."/akt-{$complaint->id}.pdf"
        );
    }

    public function exists(Complaint $complaint): bool
    {
        return Storage::disk('local')->exists(
            self::PDF_DIR."/akt-{$complaint->id}.pdf"
        );
    }

    /**
     * Delete the PDF file for the given complaint.
     *
     * NOTE: This method deletes ONLY the generated PDF file, not the
     * complaint row. Complaint deletion lives in ComplaintService.
     *
     * ✅ P2 FIX: The old bulkDelete() method was removed because it
     * silently called this method inside a loop and returned a "deleted
     * count" that misleadingly looked like complaint-row deletions.
     * ComplaintService::bulkDelete() is the single source of truth.
     */
    public function delete(Complaint $complaint): bool
    {
        return Storage::disk('local')->delete(
            self::PDF_DIR."/akt-{$complaint->id}.pdf"
        );
    }
}
