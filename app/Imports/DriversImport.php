<?php

namespace App\Imports;

use App\Models\Driver;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DriversImport implements SkipsEmptyRows, ToModel, WithChunkReading, WithHeadingRow
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

        $code = trim((string) ($row['code'] ?? $row['kodu'] ?? ''));
        $firstName = trim((string) ($row['first_name'] ?? $row['ad'] ?? ''));

        if (empty($code) || empty($firstName)) {
            $this->skipped[] = [
                'row'    => $currentRow,
                'dqn'    => $code ?: '—',
                'reason' => empty($code) ? 'Sürücü kodu boşdur' : 'Ad boşdur',
            ];
            return null;
        }

        $driver = Driver::withoutGlobalScopes()->updateOrCreate(
            [
                'code'      => $code,
                'garage_id' => $this->garageId,
            ],
            [
                'company_id' => $this->companyId,
                'first_name' => $firstName,
                'last_name'  => $row['last_name'] ?? $row['soyad'] ?? null,
                'phone'      => $row['phone'] ?? $row['telefon'] ?? null,
                'position'   => $row['position'] ?? $row['vezifesi'] ?? null,
                'is_active'  => true,
                'notes'      => $row['notes'] ?? $row['qeyd'] ?? null,
            ]
        );

        $this->importedCount++;

        return $driver;
    }
}
