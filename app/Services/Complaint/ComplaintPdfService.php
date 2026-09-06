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
        $relativePath = "akt/akt-{$complaint->id}.pdf";

        // ✅ PRIVATE diskdə saxla
        $fullPath = storage_path("app/private/{$relativePath}");

        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        Storage::disk('local')->put($relativePath, $pdf->output());

        // ✅ Public-dən sil (əgər varsa)
        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }

        return $fullPath;
    }

    public function getFilePath(Complaint $complaint): string
    {
        return storage_path("app/private/akt/akt-{$complaint->id}.pdf");
    }

    public function exists(Complaint $complaint): bool
    {
        return Storage::disk('local')->exists("akt/akt-{$complaint->id}.pdf");
    }

    public function delete(Complaint $complaint): bool
    {
        return Storage::disk('local')->delete("akt/akt-{$complaint->id}.pdf");
    }
}
