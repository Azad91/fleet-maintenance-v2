<?php

namespace App\Imports;

use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class WarehouseImport implements ToCollection, ShouldQueue, WithChunkReading, WithHeadingRow, WithValidation
{
    public function __construct(
        private int $garageId,
        private ?int $companyId = null
    ) {}

    public function chunkSize(): int
    {
        return 500; // Performans üçün bloku 500-ə qaldırdıq
    }

    public function collection(Collection $rows)
    {
        // 1. Sətirlərdəki bütün "code" (və ya "kod") dəyərlərini bir yerə yığırıq
        $codes = $rows->map(function ($row) {
            return trim((string) ($row['code'] ?? $row['kod'] ?? ''));
        })->filter()->unique()->toArray();

        if (empty($codes)) {
            return;
        }

        // 2. N+1 Probleminin Həlli: Mövcud qeydlərin hamısını TƏK SQL sorğusu ilə çəkirik
        $existingWarehouses = Warehouse::withoutGlobalScopes()
            ->withTrashed()
            ->where('garage_id', $this->garageId)
            ->whereIn('code', $codes)
            ->get()
            ->keyBy('code');

        foreach ($rows as $row) {
            $code = trim((string) ($row['code'] ?? $row['kod'] ?? ''));
            if (empty($code)) continue;

            $quantity = (int) ($row['quantity'] ?? $row['miqdar'] ?? 0);

            // Qiymət formatındakı vergül/boşluq probleminin həlli
            $rawPrice = $row['price'] ?? $row['qiymet'] ?? '0';
            $price = (float) str_replace([' ', ','], '', (string) $rawPrice);

            $name = trim((string) ($row['name'] ?? $row['ad'] ?? ''));
            $unit = $row['unit'] ?? $row['olcu_vahidi'] ?? null;
            $category = $row['category'] ?? $row['kateqoriya'] ?? null;
            $minimumQuantity = isset($row['minimum_quantity']) ? (int) $row['minimum_quantity'] : (isset($row['minimum_miqdar']) ? (int) $row['minimum_miqdar'] : 0);
            $supplier = $row['supplier'] ?? $row['tedarikci'] ?? null;
            $notes = $row['notes'] ?? $row['qeyd'] ?? null;

            // Əvvəlcədən çəkdiyimiz kolleksiyadan yoxlayırıq (Bazaya müraciət getmir)
            $warehouse = $existingWarehouses->get($code);

            if ($warehouse) {
                if ($warehouse->trashed()) {
                    $warehouse->restore();
                }
                $warehouse->update([
                    'quantity' => $quantity,
                    'price' => $price ?: $warehouse->price,
                    'name' => $name ?: $warehouse->name,
                    'unit' => $unit ?? $warehouse->unit,
                    'category' => $category ?? $warehouse->category,
                    'minimum_quantity' => $minimumQuantity ?: $warehouse->minimum_quantity,
                    'supplier' => $supplier ?? $warehouse->supplier,
                    'notes' => $notes ?? $warehouse->notes,
                ]);
            } else {
                Warehouse::create([
                    'code' => $code,
                    'name' => $name,
                    'quantity' => $quantity,
                    'unit' => $unit,
                    'price' => $price,
                    'category' => $category,
                    'minimum_quantity' => $minimumQuantity,
                    'supplier' => $supplier,
                    'notes' => $notes,
                    'garage_id' => $this->garageId,
                    'company_id' => $this->companyId,
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'code' => 'sometimes|nullable|string|max:255',
            'kod' => 'sometimes|nullable|string|max:255',
            'name' => 'sometimes|nullable|string|max:255',
            'ad' => 'sometimes|nullable|string|max:255',
            'quantity' => 'nullable|numeric|min:0',
            'miqdar' => 'nullable|numeric|min:0',
        ];
    }
}
