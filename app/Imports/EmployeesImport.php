<?php

namespace App\Imports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\ShouldQueue;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployeesImport implements ShouldQueue, SkipsEmptyRows, ToModel, WithChunkReading, WithHeadingRow
{
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
        $firstName = trim((string) ($row['first_name'] ?? $row['ad'] ?? ''));
        $lastName = trim((string) ($row['last_name'] ?? $row['soyad'] ?? ''));
        $position = trim((string) ($row['position'] ?? $row['vezifesi'] ?? $row['vezife'] ?? 'digər'));

        if (empty($firstName) || empty($lastName)) {
            return null;
        }

        $garageId = $this->garageId;
        $companyId = $this->companyId;

        return new Employee([
            'garage_id' => $garageId,
            'company_id' => $companyId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'position' => $position,
            'is_active' => true,
            'notes' => $row['notes'] ?? $row['qeyd'] ?? null,
        ]);
    }
}
