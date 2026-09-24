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

        $code = mb_strtoupper(trim((string) ($row['code'] ?? $row['kodu'] ?? '')));
        $firstName = trim((string) ($row['first_name'] ?? ''));
        $lastName = trim((string) ($row['last_name'] ?? ''));
        $position = trim((string) ($row['position'] ?? 'other'));

        if (empty($code) || empty($firstName) || empty($lastName)) {
            $this->recordSkip(
                $currentRow,
                $code ?: trim("{$firstName} {$lastName}") ?: '—',
                empty($code)
                    ? __('messages.imports.reasons.employee_code_empty')
                    : (empty($firstName)
                        ? __('messages.imports.reasons.first_name_empty')
                        : __('messages.imports.reasons.last_name_empty'))
            );

            return null;
        }

        // See DriversImport for the full rationale — we must search
        // without global scopes so a soft-deleted employee with the
        // same (garage_id, code) is found and then explicitly
        // restored. A plain updateOrCreate would leave deleted_at
        // set while flipping is_active back to true.
        $employee = Employee::withoutGlobalScopes()
            ->where('code', $code)
            ->where('garage_id', $this->garageId)
            ->first();

        if ($employee === null) {
            $employee = new Employee;
            $employee->code = $code;
            $employee->garage_id = $this->garageId;
        } elseif ($employee->trashed()) {
            $employee->restore();
        }

        $employee->fill([
            'company_id' => $this->companyId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'position' => $position,
            'is_active' => true,
            'notes' => $row['notes'] ?? null,
        ]);

        $employee->save();

        $this->incrementImported();

        return $employee;
    }
}
