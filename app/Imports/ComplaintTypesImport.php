<?php

namespace App\Imports;

use App\Models\ComplaintType;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;

class ComplaintTypesImport extends AbstractImport implements OnEachRow, SkipsEmptyRows, SkipsOnFailure, WithChunkReading, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public function onRow(Row $row): void
    {
        $currentRow = $this->nextRowIndex();
        $rowArray = $row->toArray();
        $name = trim((string) ($rowArray['name'] ?? ''));

        if ($name === '') {
            $this->recordSkip($currentRow, '—', __('messages.imports.reasons.name_empty'));

            return;
        }

        ComplaintType::withoutGlobalScopes()->updateOrCreate(
            [
                'name' => $name,
                'garage_id' => $this->garageId,
            ],
            [
                'company_id' => $this->companyId,
            ]
        );

        $this->incrementImported();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }
}
