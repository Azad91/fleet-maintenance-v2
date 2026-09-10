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

class ComplaintTypesImport implements OnEachRow, SkipsEmptyRows, SkipsOnFailure, WithChunkReading, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public array $skipped = [];
    public int $importedCount = 0;

    public function chunkSize(): int
    {
        return 100;
    }

    public function onRow(Row $row)
    {
        $rowArray = $row->toArray();
        $name = trim((string) ($rowArray['name'] ?? ''));

        if ($name === '') {
            $this->skipped[] = [
                'row'    => $row->getIndex(),
                'dqn'    => '—',
                'reason' => __('messages.imports.reasons.name_empty'),
            ];
            return;
        }

        ComplaintType::updateOrCreate(['name' => $name]);

        $this->importedCount++;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }
}