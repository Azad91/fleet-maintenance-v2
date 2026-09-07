<?php

namespace App\Imports;

use App\Models\Warehouse;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Row;
use Illuminate\Support\Facades\Log;

class WarehouseImport implements OnEachRow, WithHeadingRow, WithValidation, SkipsEmptyRows, ShouldQueue, WithChunkReading
{
    public function __construct(
        public int $garageId,
        public ?int $companyId = null
    ) {}

    public function chunkSize(): int
    {
        return 100;
    }

    public function onRow(Row $row)
    {
        $rowArray = $row->toArray();

        $code = trim((string) ($rowArray['code'] ?? $rowArray['kod'] ?? ''));

        if (empty($code)) {
            Log::warning('Boş kod sətri keçildi');
            return;
        }

        $garageId = $this->garageId;
        $companyId = $this->companyId;

        // ✅ DB::transaction ÇIXARILDI – chunk artıq transaction təmin edir
        $warehouse = Warehouse::withoutGlobalScopes()
            ->withTrashed()
            ->where('code', $code)
            ->when($garageId, fn($q) => $q->where('garage_id', $garageId))
            ->lockForUpdate()
            ->first();

        $quantity = (int) ($rowArray['quantity'] ?? $rowArray['miqdar'] ?? 0);
        $price = isset($rowArray['price']) ? (float) $rowArray['price'] : (isset($rowArray['qiymet']) ? (float) $rowArray['qiymet'] : 0);
        $name = trim((string) ($rowArray['name'] ?? $rowArray['ad'] ?? ''));
        $unit = $rowArray['unit'] ?? $rowArray['olcu_vahidi'] ?? null;
        $category = $rowArray['category'] ?? $rowArray['kateqoriya'] ?? null;
        $minimumQuantity = $rowArray['minimum_quantity'] ?? $rowArray['minimum_miqdar'] ?? null;
        $supplier = $rowArray['supplier'] ?? $rowArray['tedarikci'] ?? null;
        $notes = $rowArray['notes'] ?? $rowArray['qeyd'] ?? null;

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
                'minimum_quantity' => $minimumQuantity ?? $warehouse->minimum_quantity,
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
                'garage_id' => $garageId,
                'company_id' => $companyId,
            ]);
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
            'price' => 'nullable|numeric|min:0',
            'qiymet' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'olcu_vahidi' => 'nullable|string|max:50',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'code.required' => 'Kod sütunu boş ola bilməz.',
            'name.required' => 'Ad sütunu boş ola bilməz.',
            'quantity.numeric' => 'Miqdar yalnız rəqəm ola bilər.',
            'quantity.min' => 'Miqdar 0-dan kiçik ola bilməz.',
            'price.numeric' => 'Qiymət yalnız rəqəm ola bilər.',
            'price.min' => 'Qiymət 0-dan kiçik ola bilməz.',
        ];
    }
}
