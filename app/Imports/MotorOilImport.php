<?php

namespace App\Imports;

use App\Models\MotorOilDetail;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Row;

class MotorOilImport implements OnEachRow, WithHeadingRow, ShouldQueue, WithChunkReading
{
    protected $kmColumns = [];

    public function chunkSize(): int
    {
        return 100;
    }

    public function onRow(Row $row)
    {
        $rowArray = $row->toArray();

        if (empty($this->kmColumns)) {
            foreach ($rowArray as $key => $value) {
                if (is_numeric($key)) {
                    $this->kmColumns[$key] = (int) $key;
                }
            }
        }

        $partCode = $rowArray['part_code'] ?? $rowArray['detal_kodu'] ?? $rowArray['kod'] ?? null;
        $partName = $rowArray['part_name'] ?? $rowArray['detal_adi'] ?? $rowArray['adi'] ?? null;
        $unit = $rowArray['unit'] ?? $rowArray['olcu_vahidi'] ?? null;
        $quantity = (float) ($rowArray['quantity'] ?? $rowArray['miqdar'] ?? 0);

        if (!$partCode) {
            return;
        }

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
            }
        }
    }
}
