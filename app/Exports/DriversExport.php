<?php

namespace App\Exports;

use App\Models\Driver;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DriversExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Driver::orderBy('kodu')->get();
    }

    public function headings(): array
    {
        return ['Kod', 'Ad', 'Soyad', 'Telefon', 'Vəzifəsi', 'Aktiv', 'Qeyd'];
    }

    public function map($driver): array
    {
        return [
            $driver->kodu,
            $driver->ad,
            $driver->soyad ?? '',
            $driver->telefon ?? '',
            $driver->vezifesi ?? '',
            $driver->aktiv ? 'Aktiv' : 'Passiv',
            $driver->qeyd ?? '',
        ];
    }
}
