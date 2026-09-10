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
        $complaint->loadMissing(['details.employee', 'bus', 'creator', 'closer']);

        // Bütün əlaqəli employee ID-lərini topla (details-dən + əsas employee_id)
        $employeeIds = $complaint->details
            ->pluck('employee_id')
            ->filter()
            ->unique();

        if ($complaint->employee_id) {
            $employeeIds->push($complaint->employee_id);
        }

        $employeesById = Employee::withoutGlobalScopes()
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
        $relativePath = self::PDF_DIR . "/akt-{$complaint->id}.pdf";

        Storage::disk('local')->put($relativePath, $pdf->output());

        return Storage::disk('local')->path($relativePath);
    }

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
