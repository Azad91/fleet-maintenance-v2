<?php

namespace App\Imports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployeesImport implements SkipsEmptyRows, ToModel, WithChunkReading, WithHeadingRow
{
    public array $skipped = [];
    public int $importedCount = 0;
    private int $rowCounter = 0;

    public function __construct(
        public int $garageId,
        public ?int $companyId = null
    ) {}

    public function chunkSize(): int
    {
        return 100;
    }

    public function model(array $row)
    {
        $this->rowCounter++;
        $currentRow = $this->rowCounter + 1;

        $firstName = trim((string) ($row['first_name'] ?? ''));
        $lastName  = trim((string) ($row['last_name'] ?? ''));
        $position  = trim((string) ($row['position'] ?? 'other'));

        if (empty($firstName) || empty($lastName)) {
            $this->skipped[] = [
                'row'    => $currentRow,
                'dqn'    => trim("{$firstName} {$lastName}") ?: '—',
                'reason' => empty($firstName)
                    ? __('messages.imports.reasons.first_name_empty')
                    : __('messages.imports.reasons.last_name_empty'),
            ];
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

        $this->importedCount++;

        return $employee;
    }
}