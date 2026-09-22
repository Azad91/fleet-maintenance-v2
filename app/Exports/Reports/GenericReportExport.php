<?php

namespace App\Exports\Reports;

use App\Support\Excel\SafeCell;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Generic Excel export for report pages.
 *
 * Every report already produces rows in a tabular shape; this class
 * takes that shape as-is and writes it to a styled .xlsx file.
 *
 * Security: every cell passes through SafeCell::sanitize() so a value
 * like "=cmd|..." cannot turn into an Excel formula when the file is
 * opened (CSV/Excel injection defense).
 */
class GenericReportExport implements FromArray, WithColumnWidths, WithHeadings, WithStyles
{
    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<int, string>             $headings
     */
    public function __construct(
        protected array $rows,
        protected array $headings,
    ) {}

    public function array(): array
    {
        return array_map(function (array $row) {
            return array_map(
                fn ($value) => SafeCell::sanitize($value),
                array_values($row)
            );
        }, $this->rows);
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function columnWidths(): array
    {
        $widths = [];

        foreach (range(0, count($this->headings) - 1) as $index) {
            $letter = Coordinate::stringFromColumnIndex($index + 1);
            $widths[$letter] = 22;
        }

        return $widths;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E293B'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
