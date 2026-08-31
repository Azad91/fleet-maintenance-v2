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
    public function chunkSize(): int
    {
        return 100;
    }

    public function model(array $row)
    {
        $kodu = trim($row['kodu'] ?? '');
        $ad = trim($row['ad'] ?? '');

        if (empty($kodu) || empty($ad)) {
            return null;
        }

        return Driver::updateOrCreate(
            ['kodu' => $kodu],
            [
                'ad' => $ad,
                'soyad' => $row['soyad'] ?? null,
                'telefon' => $row['telefon'] ?? null,
                'vezifesi' => $row['vezifesi'] ?? null,
                'aktiv' => true,
                'qeyd' => $row['qeyd'] ?? null,
            ]
        );
    }
}
