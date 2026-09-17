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
    public const MODE_ADD       = 'add';

    /**
     * @param  int|null  $garageId   Positive for tenant imports.
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
            $price = (float) str_replace([' ', ','], '', (string) $rawPrice);

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

            $warehouse = Warehouse::withoutGlobalScopes()
                ->where('garage_id', $this->garageId)
                ->where('code', $code)
                ->first();

            if ($warehouse) {
                // Existing item — update catalog fields, and combine
                // the quantity according to the selected mode.
                $newQuantity = $this->mode === self::MODE_ADD
                    ? $warehouse->quantity + $quantity
                    : $quantity;

                $warehouse->update([
                    'name'     => $name,
                    'quantity' => $newQuantity,
                    'unit'     => $unit,
                    'price'    => $price,
                ]);
            } else {
                // New item — always created with the Excel quantity.
                Warehouse::create([
                    'code'       => $code,
                    'name'       => $name,
                    'quantity'   => $quantity,
                    'unit'       => $unit,
                    'price'      => $price,
                    'garage_id'  => $this->garageId,
                    'company_id' => $this->companyId,
                ]);
            }

            $this->incrementImported();
        }
    }
}
