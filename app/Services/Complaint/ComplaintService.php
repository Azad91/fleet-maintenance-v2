<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\Driver;
use Illuminate\Support\Facades\DB;

class ComplaintService
{
    public function __construct(
        protected ComplaintStockService $stockService,
        protected ComplaintItemService $itemService,
        protected ComplaintStatusTransitionService $transitionService
    ) {}

    public function create(array $data, ?array $detallar = null, array $shikayet = []): Complaint
    {
        if (($data['yer'] ?? null) === 'yol' && !empty($data['driver_id'])) {
            $driver = Driver::active()->findOrFail($data['driver_id']);
            $data['driver_name'] = $driver->full_name;
        } else {
            $data['driver_id'] = null;
            $data['driver_name'] = null;
        }

        $data['created_by'] = auth()->id();

        return DB::transaction(function () use ($data, $detallar, $shikayet) {
            $processedDetails = [];
            if (!empty($detallar) && is_array($detallar)) {
                $processedDetails = $this->stockService->deductStock($detallar);
            }

            $complaint = Complaint::create($data);

            if (!empty($processedDetails)) {
                $complaint->details()->createMany($processedDetails);
            }

            $this->itemService->syncItems($complaint, $shikayet, $data['complaint_type'] ?? null);

            return $complaint;
        });
    }

    public function update(Complaint $complaint, array $data, ?array $detallar = null, array $shikayet = []): Complaint
    {
        if (isset($data['status'])) {
            $this->transitionService->validateTransition($complaint, $data['status']);
        }

        if (($data['yer'] ?? null) === 'yol' && !empty($data['driver_id'])) {
            $driver = Driver::active()->findOrFail($data['driver_id']);
            $data['driver_name'] = $driver->full_name;
        } else {
            $data['driver_id'] = null;
            $data['driver_name'] = null;
        }

        return DB::transaction(function () use ($complaint, $data, $detallar, $shikayet) {
            $processedDetails = [];

            // 1. Əgər yeni detallar varsa, köhnələri geri qaytar və yenilərini sil
            if ($detallar !== null && is_array($detallar)) {
                // Köhnə detalları geri qaytar
                if ($complaint->details->isNotEmpty()) {
                    $this->stockService->restoreStock($complaint->details->toArray());
                    $complaint->details()->delete();
                }

                // Yeni detalları anbardan sil
                if (!empty($detallar)) {
                    $processedDetails = $this->stockService->deductStock($detallar);
                }
            }

            // 2. Complaint-i yenilə
            $complaint->update($data);

            // 3. Yeni detalları əlavə et
            if (!empty($processedDetails)) {
                $complaint->details()->createMany($processedDetails);
            }

            // 4. Şikayətləri sinxronlaşdır
            $this->itemService->syncItems($complaint, $shikayet, $data['complaint_type'] ?? null);

            return $complaint;
        });
    }

    public function close(Complaint $complaint, array $data): Complaint
    {
        $complaint->update([
            'status' => 'həll olundu',
            'end_date' => $data['end_date'],
            'end_time' => $data['end_time'],
            'work_done_by' => $data['work_done'],
            'closed_at' => now(),
            'closed_by' => auth()->id(),
        ]);

        return $complaint;
    }

    public function delete(Complaint $complaint): void
    {
        DB::transaction(function () use ($complaint) {
            if ($complaint->details->isNotEmpty()) {
                $this->stockService->restoreStock($complaint->details->toArray());
                $complaint->details()->delete();
            }
            $complaint->delete();
        });
    }
}
