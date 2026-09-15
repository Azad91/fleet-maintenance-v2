<?php

namespace App\Exports;

use App\Models\DailyKmRecord;
use App\Support\Excel\SafeCell;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Exports the currently-filtered daily-km-records list to Excel.
 *
 * Mirrors DailyKmRecordController::index() filters:
 *   - $date (Y-m-d, null = all dates)
 *   - $dqn  (partial match, case-insensitive)
 *
 * Uses FromQuery to stream large datasets in chunks.
 */
class DailyKmRecordsExport implements FromQuery, WithColumnWidths, WithHeadings, WithMapping
{
    public function __construct(
        protected ?string $date,
        protected ?string $dqn,
    ) {}

    public function query()
    {
        $query = DailyKmRecord::query()->with('bus');

        if ($this->date) {
            $query->whereDate('date', $this->date);
        }

        if ($this->dqn) {
            $query->whereHas('bus', fn ($q) => $q->where('dqn', 'ILIKE', "%{$this->dqn}%"));
        }

        return $query
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');
    }

    public function headings(): array
    {
        return [
            'DQN',
            'Route',
            'Date',
            'KM',
            'Notes',
        ];
    }

    public function map($row): array
    {
        return [
            SafeCell::sanitize($row->bus?->dqn ?? ''),
            SafeCell::sanitize($row->bus?->route_number ?? ''),
            $row->date ? \Carbon\Carbon::parse($row->date)->format('Y-m-d') : '',
            (int) $row->km,
            SafeCell::sanitize($row->notes ?? ''),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,  // DQN
            'B' => 10,  // Route
            'C' => 12,  // Date
            'D' => 15,  // KM
            'E' => 40,  // Notes
        ];
    }
}