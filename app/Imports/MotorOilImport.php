<?php

namespace App\Imports;

use App\Models\MotorOilDetail;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class MotorOilImport extends AbstractImport implements OnEachRow, WithChunkReading, WithHeadingRow
{
    protected array $kmColumns = [];

    public function onRow(Row $row): void
    {
        $currentRow = $this->nextRowIndex();
        $rowArray = $row->toArray();

        if (empty($this->kmColumns)) {
            foreach ($rowArray as $key => $value) {
                if (is_numeric($key)) {
                    $this->kmColumns[$key] = (int) $key;
                }
            }
        }

        $partCode = $rowArray['part_code'] ?? null;
        $partName = $rowArray['part_name'] ?? null;
        $unit = $rowArray['unit'] ?? null;
        $quantity = (float) ($rowArray['quantity'] ?? 0);

        if (! $partCode) {
            $this->recordSkip($currentRow, '—', __('messages.imports.reasons.part_code_empty'));

            return;
        }

        $createdForThisRow = 0;

        foreach ($this->kmColumns as $columnIndex => $km) {
            $count = (int) ($rowArray[$columnIndex] ?? 0);

            if ($count > 0) {
                MotorOilDetail::create([
                    'part_code' => $partCode,
                    'part_name' => $partName,
                    'unit' => $unit,
                    'quantity' => $quantity,
                    'km' => $km,
                    'count' => $count,
                ]);

                $createdForThisRow++;
            }
        }

        if ($createdForThisRow === 0) {
            $this->recordSkip($currentRow, $partCode, __('messages.imports.reasons.no_km_columns'));

            return;
        }

        // A single source row produced $createdForThisRow records — one
        // for each KM column with a positive count.
        $this->incrementImported($createdForThisRow);
    }
}
