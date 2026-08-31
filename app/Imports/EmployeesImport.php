<?php

namespace App\Imports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class EmployeesImport implements ToModel, WithHeadingRow, SkipsEmptyRows, ShouldQueue, WithChunkReading
{
    public function chunkSize(): int
    {
        return 100;
    }

    public function model(array $row)
    {
        $ad = $row['ad'] ?? null;
        $soyad = $row['soyad'] ?? null;
        $vezife = $row['vezife'] ?? 'digər';

        if (empty($ad)) {
            return null;
        }

        if (empty($soyad)) {
            $soyad = '';
        }

        return new Employee([
            'ad' => $ad,
            'soyad' => $soyad,
            'vezifesi' => $vezife,
            'aktiv' => true,
            'qeyd' => null,
        ]);
    }
}
