<?php

namespace App\Imports;

use App\Models\Driver;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DriversImport extends AbstractImport implements SkipsEmptyRows, ToModel, WithChunkReading, WithHeadingRow
{
    public function model(array $row)
    {
        $currentRow = $this->nextRowIndex();

        $code = trim((string) ($row['code'] ?? ''));
        $firstName = trim((string) ($row['first_name'] ?? ''));

        if (empty($code) || empty($firstName)) {
            $this->recordSkip(
                $currentRow,
                $code ?: '—',
                empty($code)
                    ? __('messages.imports.reasons.driver_code_empty')
                    : __('messages.imports.reasons.first_name_empty')
            );

            return null;
        }

        $driver = Driver::withoutGlobalScopes()->updateOrCreate(
            [
                'code' => $code,
                'garage_id' => $this->garageId,
            ],
            [
                'company_id' => $this->companyId,
                'first_name' => $firstName,
                'last_name' => $row['last_name'] ?? null,
                'phone' => $row['phone'] ?? null,
                'position' => $row['position'] ?? null,
                'is_active' => true,
                'notes' => $row['notes'] ?? null,
            ]
        );

        $this->incrementImported();

        return $driver;
    }
}
