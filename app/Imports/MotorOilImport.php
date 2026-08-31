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

        $detal_kodu = $rowArray['kod'] ?? null;
        $detal_adi = $rowArray['adi'] ?? null;
        $olcu_vahidi = $rowArray['olcu_vahidi'] ?? null;
        $miqdar = $rowArray['miqdar'] ?? 0;

        if (!$detal_kodu) return;

        foreach ($this->kmColumns as $columnIndex => $km) {
            $say = (int) ($rowArray[$columnIndex] ?? 0);

            if ($say > 0) {
                MotorOilDetail::create([
                    'detal_kodu' => $detal_kodu,
                    'detal_adi' => $detal_adi,
                    'olcu_vahidi' => $olcu_vahidi,
                    'miqdar' => $miqdar,
                    'km' => $km,
                    'say' => $say,
                ]);
            }
        }
    }
}
