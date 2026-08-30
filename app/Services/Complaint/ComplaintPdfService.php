<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;

class ComplaintPdfService
{
    public function generate(Complaint $complaint): \Barryvdh\DomPDF\PDF
    {
        $employeesById = Employee::whereIn(
            'id',
            collect($complaint->detallar ?? [])->pluck('employee_id')->filter()->unique()
        )->get()->keyBy('id');

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
        $path = storage_path("app/public/akt/akt-{$complaint->id}.pdf");

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $pdf->save($path);
        return $path;
    }
}
