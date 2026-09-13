<?php

namespace App\Imports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployeesImport extends AbstractImport implements SkipsEmptyRows, ToModel, WithChunkReading, WithHeadingRow
{
    public function model(array $row)
    {
        $currentRow = $this->nextRowIndex();

        $firstName = trim((string) ($row['first_name'] ?? ''));
        $lastName  = trim((string) ($row['last_name'] ?? ''));
        $position  = trim((string) ($row['position'] ?? 'other'));

        if (empty($firstName) || empty($lastName)) {
            $this->recordSkip(
                $currentRow,
                trim("{$firstName} {$lastName}") ?: '—',
                empty($firstName)
                    ? __('messages.imports.reasons.first_name_empty')
                    : __('messages.imports.reasons.last_name_empty')
            );

            return null;
        }

        $employee = new Employee([
            'garage_id'  => $this->garageId,
            'company_id' => $this->companyId,
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'position'   => $position,
            'is_active'  => true,
            'notes'      => $row['notes'] ?? null,
        ]);

        $this->incrementImported();

        return $employee;
    }
}
