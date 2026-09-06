<?php

namespace App\Imports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class EmployeesImport implements ToModel, WithHeadingRow, SkipsEmptyRows, ShouldQueue, WithChunkReading
{
    public function __construct(
        public ?int $garageId = null,
        public ?int $companyId = null
    ) {
        $this->garageId ??= (int) session('current_garage_id');
        $this->companyId ??= session('current_company_id') ? (int) session('current_company_id') : null;
    }

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
