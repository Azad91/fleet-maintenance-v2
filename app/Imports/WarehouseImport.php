<?php

namespace App\Imports;

use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class WarehouseImport implements ToCollection, WithHeadingRow
{
    public function __construct(
        private int $garageId,
        private ?int $companyId = null
    ) {}

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $code = trim((string) ($row['code'] ?? $row['kod'] ?? ''));
            $name = trim((string) ($row['name'] ?? $row['ad'] ?? ''));
            $quantity = (int) ($row['quantity'] ?? $row['miqdar'] ?? 0);
            $unit = $row['unit'] ?? $row['olcu_vahidi'] ?? null;
            
            $rawPrice = $row['price'] ?? $row['qiymet'] ?? '0';
            $price = (float) str_replace([' ', ','], '', (string) $rawPrice);

            if (empty($code) || empty($name)) {
                continue;
            }

            $warehouse = Warehouse::withoutGlobalScopes()
                ->where('garage_id', $this->garageId)
                ->where('code', $code)
                ->first();

            if ($warehouse) {
                $warehouse->update([
                    'name' => $name,
                    'quantity' => $quantity,
                    'unit' => $unit,
                    'price' => $price,
                ]);
            } else {
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
        }
    }
}