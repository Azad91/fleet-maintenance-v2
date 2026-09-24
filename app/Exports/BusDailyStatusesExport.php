<?php

namespace App\Exports;

use App\Models\BusDailyStatus;
use App\Support\Excel\SafeCell;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Exports the currently-filtered bus-daily-statuses list to Excel.
 *
 * The filters accepted here mirror BusDailyStatusController::index():
 *   - $date   (Y-m-d, null = all dates)
 *   - $dqn    (partial match, case-insensitive)
 *   - $status (exact value)
 *
 * Uses FromQuery rather than FromCollection so Laravel-Excel streams
 * the rows in chunks — the dataset can grow to hundreds of thousands
 * of rows after a year of daily imports.
 */
class BusDailyStatusesExport implements FromQuery, WithColumnWidths, WithHeadings, WithMapping
{
    public function __construct(
        protected ?string $date,
        protected ?string $dqn,
        protected ?string $status,
    ) {}

    public function query()
    {
        $query = BusDailyStatus::query()->with('bus');

        if ($this->date) {
            $query->whereDate('date', $this->date);
        }

        if ($this->dqn) {
            $query->whereHas('bus', fn ($q) => $q->where('dqn', 'ILIKE', "%{$this->dqn}%"));
        }

        if ($this->status) {
            $query->where('status', $this->status);
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
            'Status',
            'Notes',
        ];
    }

    /**
     * Every string field goes through SafeCell so a malicious value
     * such as "=cmd|..." cannot turn into a formula when the file is
     * opened in Excel (CSV/Excel injection defense).
     */
    public function map($row): array
    {
        return [
            SafeCell::sanitize($row->bus?->dqn ?? ''),
            SafeCell::sanitize($row->bus?->route_number ?? ''),
            $row->date ? \Carbon\Carbon::parse($row->date)->format('Y-m-d') : '',
            SafeCell::sanitize($row->status ?? ''),
            SafeCell::sanitize($row->notes ?? ''),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,  // DQN
            'B' => 10,  // Route
            'C' => 12,  // Date
            'D' => 25,  // Status
            'E' => 40,  // Notes
        ];
    }
}
