<?php

namespace App\Imports;

use App\Models\ComplaintType;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Row;

class ComplaintTypesImport implements OnEachRow, WithHeadingRow, WithValidation, SkipsEmptyRows, ShouldQueue, WithChunkReading
{
    public function chunkSize(): int
    {
        return 100;
    }

    public function onRow(Row $row)
    {
        $rowArray = $row->toArray();

        $name = trim((string) ($rowArray['name'] ?? ''));
        if ($name === '') {
            return;
        }

        ComplaintType::updateOrCreate(
            ['name' => $name]
        );
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }
}
