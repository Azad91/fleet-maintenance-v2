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
        return Driver::orderBy('code')->get();
    }

    public function headings(): array
    {
        return ['Kod', 'Ad', 'Soyad', 'Telefon', 'Vəzifəsi', 'Aktiv', 'Qeyd'];
    }

    public function map($driver): array
    {
        return [
            $driver->code,
            $driver->first_name,
            $driver->last_name ?? '',
            $driver->phone ?? '',
            $driver->position ?? '',
            $driver->is_active ? 'Aktiv' : 'Passiv',
            $driver->notes ?? '',
        ];
    }
}
