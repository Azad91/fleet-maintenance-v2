<?php

namespace App\Imports;

use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Imports warehouse items from an Excel file.
 *
 * TWO MODES
 * ---------
 * The caller chooses how an EXISTING item's quantity is handled when the
 * same (garage_id, code) pair appears in the spreadsheet:
 *
 *   - OVERWRITE (default): the new quantity REPLACES the stored one.
 *     Use this when the Excel file represents the garage's current,
 *     authoritative inventory snapshot.
 *
 *   - ADD: the new quantity is ADDED to the stored one.
 *     Use this when the Excel file contains newly received parts and
 *     the warehouse already holds some units of the same code.
 *     Example: 500 L in stock + 2000 L arriving → 2500 L total.
 *
 * In both modes, name/unit/price/supplier are updated to the Excel
 * values when the row exists — the operator's latest catalog metadata
 * always wins.
 *
 * Duplicate codes INSIDE the same file are handled correctly: the
 * second occurrence of a code updates the in-memory result of the
 * first (via the DB), so both modes behave consistently.
 */
class WarehouseImport extends AbstractImport implements ToCollection, WithHeadingRow
{
    /**
     * How to treat the quantity of an existing item.
     */
    public const MODE_OVERWRITE = 'overwrite';

    public const MODE_ADD = 'add';

    /**
     * @param  int|null  $garageId  Positive for tenant imports.
     * @param  int|null  $companyId  Optional, used for strict company scoping.
     * @param  string  $mode  One of MODE_OVERWRITE or MODE_ADD.
     */
    public function __construct(
        ?int $garageId = null,
        ?int $companyId = null,
        public readonly string $mode = self::MODE_OVERWRITE,
    ) {
        parent::__construct($garageId, $companyId);

        if (! in_array($mode, [self::MODE_OVERWRITE, self::MODE_ADD], true)) {
            throw new \InvalidArgumentException(sprintf(
                'WarehouseImport: unknown mode [%s]. Expected [%s] or [%s].',
                $mode,
                self::MODE_OVERWRITE,
                self::MODE_ADD,
            ));
        }
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $code = trim((string) ($row['code'] ?? $row['kod'] ?? ''));
            $name = trim((string) ($row['name'] ?? $row['ad'] ?? ''));
            $quantity = (int) ($row['quantity'] ?? $row['miqdar'] ?? 0);
            $unit = $row['unit'] ?? $row['olcu_vahidi'] ?? null;

            $rawPrice = $row['price'] ?? $row['qiymet'] ?? '0';
            $price = $this->parsePrice($rawPrice);

            if (empty($code) || empty($name)) {
                $this->recordSkip(
                    $this->nextRowIndex(),
                    $code ?: '—',
                    empty($code)
                        ? __('messages.imports.reasons.name_empty')
                        : __('messages.imports.reasons.name_empty')
                );

                continue;
            }

            // See DriversImport for the full rationale — searching
            // without global scopes is required to detect the
            // soft-deleted row, but we must explicitly restore it
            // so the update actually brings the row back to life.
            $warehouse = Warehouse::withoutGlobalScopes()
                ->where('garage_id', $this->garageId)
                ->where('code', $code)
                ->first();

            if ($warehouse) {
                // Restore BEFORE computing the new quantity, so
                // MODE_ADD accumulates on top of the previous
                // quantity rather than on top of a hidden row.
                if ($warehouse->trashed()) {
                    $warehouse->restore();
                }

                $newQuantity = $this->mode === self::MODE_ADD
                    ? $warehouse->quantity + $quantity
                    : $quantity;

                $warehouse->update([
                    'name' => $name,
                    'quantity' => $newQuantity,
                    'unit' => $unit,
                    'price' => $price,
                ]);
            } else {
                // New item — always created with the Excel quantity.
                Warehouse::create([
                    'code' => $code,
                    'name' => $name,
                    'quantity' => $quantity,
                    'unit' => $unit,
                    'price' => $price,
                    'garage_id' => $this->garageId,
                    'company_id' => $this->companyId,
                ]);
            }

            $this->incrementImported();
        }
    }

    /**
     * Parse a price value from an Excel cell.
     *
     * Handles the two common locale formats:
     *   - AZ / TR: "1.500,50"  → 1500.50   (nöqtə minliklər, vergül onluq)
     *   - US / EN: "1,500.50"  → 1500.50   (vergül minliklər, nöqtə onluq)
     *   - Plain:   "1500.50"   → 1500.50
     *              "1500,50"   → 1500.50
     *
     * Strategiya: hər iki ayırıcı varsa, SONUNCU olan onluq ayırıcıdır.
     * Yalnız biri varsa, 2 rəqəmdən sonra gəlirsə onluq, əks halda minlik
     * sayılır.
     */
    private function parsePrice(mixed $raw): float
    {
        if ($raw === null || $raw === '') {
            return 0.0;
        }

        $value = trim((string) $raw);

        // Bütün boşluq / valyuta simvollarını sil (₼, $, €, AZN və s.)
        $value = preg_replace('/[^\d.,\-]/u', '', $value);

        if ($value === '' || $value === '-') {
            return 0.0;
        }

        $hasDot = str_contains($value, '.');
        $hasComma = str_contains($value, ',');

        if ($hasDot && $hasComma) {
            // Sonuncu ayırıcı onluq ayırıcıdır.
            $lastDot = strrpos($value, '.');
            $lastComma = strrpos($value, ',');

            if ($lastComma > $lastDot) {
                // "1.500,50" → 1500.50
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                // "1,500.50" → 1500.50
                $value = str_replace(',', '', $value);
            }
        } elseif ($hasComma) {
            // Yalnız vergül: "1500,50" → 1500.50 ; "1,500" → 1500
            $pos = strrpos($value, ',');
            $decimals = strlen($value) - $pos - 1;

            if ($decimals === 3 && $pos > 0) {
                // "1,500" → minliklər
                $value = str_replace(',', '', $value);
            } else {
                // "1500,50" → onluq
                $value = str_replace(',', '.', $value);
            }
        }
        // Yalnız nöqtə varsa və ya heç biri yoxdursa — olduğu kimi qalır.

        return (float) $value;
    }
}
