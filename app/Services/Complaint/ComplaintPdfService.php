<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ComplaintPdfService
{
    /**
     * PDF-in saxlanacağı qovluq (local disk daxilində).
     * local disk root = storage/app/private
     * Ona görə burada yalnız "akt" yazırıq.
     */
    private const PDF_DIR = 'akt';

    public function generate(Complaint $complaint): \Barryvdh\DomPDF\PDF
    {
        $complaint->loadMissing(['details.employee', 'bus', 'creator', 'closer']);

        $employeeIds = $complaint->details->pluck('employee_id')->filter()->unique();
        if ($complaint->employee_id) {
            $employeeIds->push($complaint->employee_id);
        }

        $employeesById = Employee::withoutGlobalScopes()
            ->whereIn('id', $employeeIds->unique())
            ->get()
            ->keyBy('id');

        return Pdf::loadView('complaints.akt', [
            'complaint' => $complaint,
            'company' => $complaint->company,
            'garage' => $complaint->garage,
            'employeesById' => $employeesById,
        ]);
    }

    /**
     * PDF-i saxlayır və tam fayl yolunu qaytarır.
     */
    public function save(Complaint $complaint): string
    {
        $pdf = $this->generate($complaint);
        $relativePath = self::PDF_DIR . "/akt-{$complaint->id}.pdf";

        Storage::disk('local')->put($relativePath, $pdf->output());

        return Storage::disk('local')->path($relativePath);
    }

    /**
     * PDF-in tam fayl yolunu qaytarır (mövcud olub-olmamasından asılı olmayaraq).
     */
    public function getFilePath(Complaint $complaint): string
    {
        return Storage::disk('local')->path(
            self::PDF_DIR . "/akt-{$complaint->id}.pdf"
        );
    }

    public function exists(Complaint $complaint): bool
    {
        return Storage::disk('local')->exists(
            self::PDF_DIR . "/akt-{$complaint->id}.pdf"
        );
    }

    public function delete(Complaint $complaint): bool
    {
        return Storage::disk('local')->delete(
            self::PDF_DIR . "/akt-{$complaint->id}.pdf"
        );
    }
}
