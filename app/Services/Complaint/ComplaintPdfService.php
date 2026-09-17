<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

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

    public function delete(Complaint $complaint): bool
    {
        return Storage::disk('local')->delete(
            self::PDF_DIR."/akt-{$complaint->id}.pdf"
        );
    }
    /**
     * Bulk soft-delete multiple complaints.
     *
     * Each complaint is deleted through the existing delete() method
     * so that stock restoration, detail cascades, and per-row audit
     * logging all run exactly as they do for single-row deletes.
     *
     * The whole batch runs inside a single DB transaction: if any
     * single delete fails (e.g. a stock restore throws), the entire
     * batch rolls back and no complaint is removed.
     *
     * @param  array<int>  $ids
     * @return int  Number of complaints actually deleted
     */
    public function bulkDelete(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $deleted = 0;

        DB::transaction(function () use ($ids, &$deleted) {
            $complaints = Complaint::whereIn('id', $ids)->get();

            foreach ($complaints as $complaint) {
                $this->delete($complaint);
                $deleted++;
            }
        });

        return $deleted;
    }
}
