<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ComplaintPdfService
{
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

    public function save(Complaint $complaint): string
    {
        $pdf = $this->generate($complaint);
        // ✅ private/akt yoluna yönləndiririk
        $relativePath = "private/akt/akt-{$complaint->id}.pdf";

        $fullPath = storage_path("app/{$relativePath}");

        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        Storage::disk('local')->put($relativePath, $pdf->output());

        return $fullPath;
    }

    public function getFilePath(Complaint $complaint): string
    {
        return storage_path("app/private/akt/akt-{$complaint->id}.pdf");
    }

    public function exists(Complaint $complaint): bool
    {
        return Storage::disk('local')->exists("private/akt/akt-{$complaint->id}.pdf");
    }

    public function delete(Complaint $complaint): bool
    {
        return Storage::disk('local')->delete("private/akt/akt-{$complaint->id}.pdf");
    }
}
