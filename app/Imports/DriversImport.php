<?php

namespace App\Imports;

use App\Models\Driver;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class DriversImport implements ToModel, WithHeadingRow, SkipsEmptyRows, ShouldQueue, WithChunkReading
{
    public function __construct(
        public ?int $garageId = null,
        public ?int $companyId = null
    ) {
        $this->garageId ??= session('current_garage_id');
        $this->companyId ??= session('current_company_id');
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function model(array $row)
    {
        $code = trim((string) ($row['code'] ?? $row['kodu'] ?? ''));
        $firstName = trim((string) ($row['first_name'] ?? $row['ad'] ?? ''));

        if (empty($code) || empty($firstName)) {
            return null;
        }

        $garageId = $this->garageId;
        $companyId = $this->companyId;

        return Driver::withoutGlobalScopes()->updateOrCreate(
            [
                'garage_id' => $garageId,
                'code' => $code,
            ],
            [
                'company_id' => $companyId,
                'first_name' => $firstName,
                'last_name' => $row['last_name'] ?? $row['soyad'] ?? null,
                'phone' => $row['phone'] ?? $row['telefon'] ?? null,
                'position' => $row['position'] ?? $row['vezifesi'] ?? null,
                'is_active' => true,
                'notes' => $row['notes'] ?? $row['qeyd'] ?? null,
            ]
        );
    }
}

