<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\Driver;
use Illuminate\Support\Facades\DB;

class ComplaintService
{
    public function __construct(
        protected ComplaintStockService $stockService,
        protected ComplaintItemService $itemService
    ) {}

    public function create(array $data, array $detallar = null, array $shikayet = []): Complaint
    {
        // Sürücü məlumatlarını hazırla
        if (($data['yer'] ?? null) === 'yol' && !empty($data['driver_id'])) {
            $driver = Driver::active()->findOrFail($data['driver_id']);
            $data['surucu_adi'] = $driver->full_name;
        } else {
            $data['driver_id'] = null;
            $data['surucu_adi'] = null;
        }

        // Şikayət arrayini string-ə çevir
        if (!empty($shikayet) && is_array($shikayet)) {
            $data['shikayet'] = implode("\n", array_filter($shikayet));
        }

        // Detalları JSON olaraq saxla
        if (!empty($detallar) && is_array($detallar)) {
            $data['detallar'] = $this->stockService->deductStock($detallar);
        } else {
            $data['detallar'] = null;
        }

        $data['created_by'] = auth()->id();

        $complaint = DB::transaction(function () use ($data, $shikayet) {
            $complaint = Complaint::create($data);
            $this->itemService->syncItems($complaint, $shikayet, $data['sikayet_tipi'] ?? null);
            return $complaint;
        });

        return $complaint;
    }

    public function update(Complaint $complaint, array $data, array $detallar = null, array $shikayet = []): Complaint
    {
        // Sürücü məlumatlarını hazırla
        if (($data['yer'] ?? null) === 'yol' && !empty($data['driver_id'])) {
            $driver = Driver::active()->findOrFail($data['driver_id']);
            $data['surucu_adi'] = $driver->full_name;
        } else {
            $data['driver_id'] = null;
            $data['surucu_adi'] = null;
        }

        // Şikayət arrayini string-ə çevir
        if (!empty($shikayet) && is_array($shikayet)) {
            $data['shikayet'] = implode("\n", array_filter($shikayet));
        }

        return DB::transaction(function () use ($complaint, $data, $detallar, $shikayet) {
            // Köhnə detalları geri qaytar
            if (!empty($complaint->detallar)) {
                $this->stockService->restoreStock($complaint->detallar);
            }

            // Yeni detalları tətbiq et
            if (!empty($detallar) && is_array($detallar)) {
                $data['detallar'] = $this->stockService->deductStock($detallar);
            } else {
                $data['detallar'] = null;
            }

            $complaint->update($data);
            $this->itemService->syncItems($complaint, $shikayet, $data['sikayet_tipi'] ?? null);

            return $complaint;
        });
    }

    public function close(Complaint $complaint, array $data): Complaint
    {
        $complaint->update([
            'status' => 'həll olundu',
            'is_bitme_tarix' => $data['is_bitme_tarix'],
            'is_bitme_saat' => $data['is_bitme_saat'],
            'kim_is_gorub' => $data['gorulen_is'],
            'closed_at' => now(),
            'closed_by' => auth()->id(),
        ]);

        return $complaint;
    }

    public function delete(Complaint $complaint): void
    {
        DB::transaction(function () use ($complaint) {
            // Detalları geri qaytar
            if (!empty($complaint->detallar)) {
                $this->stockService->restoreStock($complaint->detallar);
            }
            $complaint->delete();
        });
    }
}
