<?php

namespace App\Exports;

use App\Models\Driver;
use App\Support\Excel\SafeCell;
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
            SafeCell::sanitize($driver->code),
            SafeCell::sanitize($driver->first_name),
            SafeCell::sanitize($driver->last_name ?? ''),
            SafeCell::sanitize($driver->phone ?? ''),
            SafeCell::sanitize($driver->position ?? ''),
            $driver->is_active ? 'Aktiv' : 'Passiv',
            SafeCell::sanitize($driver->notes ?? ''),
        ];
    }
}
