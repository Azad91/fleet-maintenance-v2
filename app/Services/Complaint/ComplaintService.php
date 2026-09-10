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
        if (($data['yer'] ?? null) === 'yol' && ! empty($data['driver_id'])) {
            $driver = Driver::active()->findOrFail($data['driver_id']);
            $data['driver_name'] = $driver->full_name;
        } else {
            $data['driver_id'] = null;
            $data['driver_name'] = null;
        }

        $data['created_by'] = auth()->id();

        return DB::transaction(function () use ($data, $detallar, $shikayet) {
            $processedDetails = [];
            if (! empty($detallar) && is_array($detallar)) {
                $processedDetails = $this->stockService->deductStock($detallar);
            }

            $complaint = Complaint::create($data);

            if (! empty($processedDetails)) {
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

        if (($data['yer'] ?? null) === 'yol' && ! empty($data['driver_id'])) {
            $driver = Driver::active()->findOrFail($data['driver_id']);
            $data['driver_name'] = $driver->full_name;
        } else {
            $data['driver_id'] = null;
            $data['driver_name'] = null;
        }

        return DB::transaction(function () use ($complaint, $data, $detallar, $shikayet) {
            // Köhnə detalları snapshot kimi götür (stock diff üçün)
            $oldDetails = $complaint->details()->orderBy('id')->get()->toArray();

            $processedDetails = null;

            if ($detallar !== null && is_array($detallar)) {
                // ✅ syncStockDiff anbarı düzəldir (restore + deduct)
                $processedDetails = $this->stockService->syncStockDiff($oldDetails, $detallar);
            }

            // Complaint-i yenilə
            $complaint->update($data);

            // ✅ YENİ: Detalları in-place sinxronlaşdır
            if ($processedDetails !== null) {
                $this->syncDetails($complaint, $processedDetails);
            }

            // Şikayətləri sinxronlaşdır (item-lər — bunlar onsuz da in-place sync-dir)
            $this->itemService->syncItems($complaint, $shikayet, $data['complaint_type'] ?? null);

            return $complaint->fresh(['details', 'items']);
        });
    }

    public function close(Complaint $complaint, array $data): Complaint
    {
        $this->transitionService->validateTransition($complaint, 'həll olundu');

        $complaint->update([
            'status'       => 'həll olundu',
            'end_date'     => $data['end_date'],
            'end_time'     => $data['end_time'],
            'work_done_by' => $data['work_done'],
            'closed_at'    => now(),
            'closed_by'    => auth()->id(),
        ]);

        return $complaint;
    }

    public function delete(Complaint $complaint): void
    {
        DB::transaction(function () use ($complaint) {
            // Detalları restore et (stok geri qaytar)
            if ($complaint->details->isNotEmpty()) {
                $this->stockService->restoreStock($complaint->details->toArray());
            }

            // Detalları soft-delete et
            $complaint->details()->delete();

            // Complaint-i soft-delete et
            $complaint->delete();
        });
    }

    /**
     * Detalları in-place sinxronlaşdırır.
     *
     * Mövcud sətirlər `code`-a görə match olunur və yenilənir (ID saxlanılır).
     * Yeni kod-lar əlavə olunur, yox olan kod-lar soft-delete edilir.
     *
     * `code`-based matching-in üstünlüyü: mövqe dəyişsə belə, eyni kodlu
     * detal həmişə eyni DB sətrinə bağlı qalır → ID-lər stabil, audit
     * tarixçəsi təmiz olur.
     *
     * @param  array<int, array<string, mixed>>  $processedDetails
     */
    private function syncDetails(Complaint $complaint, array $processedDetails): void
    {
        // ==================== 0. HAZIRLIQ ====================
        // Mövcud aktiv detalları `code` ilə indekslə
        $existingByCode = $complaint->details()
            ->orderBy('id')
            ->get()
            ->keyBy('code');

        // Yeni detalları `code` ilə indekslə
        $newByCode = [];
        foreach ($processedDetails as $detail) {
            $code = $detail['code'] ?? null;
            if ($code !== null && $code !== '') {
                $newByCode[$code] = $detail;
            }
        }

        $existingCodes = $existingByCode->keys()->all();
        $newCodes = array_keys($newByCode);

        // ==================== 1. MÖVCUD VƏ YENİDƏ OLANLAR: UPDATE ====================
        // Həm mövcud, həm yeni siyahıda olan kod-lar → update
        $codesToUpdate = array_intersect($existingCodes, $newCodes);
        foreach ($codesToUpdate as $code) {
            /** @var ComplaintDetail $detail */
            $detail = $existingByCode[$code];
            $payload = $newByCode[$code];

            // Laravel `update()` yalnız dəyişən sahələri save edir;
            // heç nə dəyişməyibsə `updated` event fire olunmur.
            $detail->update($payload);
        }

        // ==================== 2. YENİ KOD-LAR: CREATE ====================
        $codesToCreate = array_diff($newCodes, $existingCodes);
        $payloadsToCreate = [];
        foreach ($codesToCreate as $code) {
            $payloadsToCreate[] = $newByCode[$code];
        }
        if (! empty($payloadsToCreate)) {
            $complaint->details()->createMany($payloadsToCreate);
        }

        // ==================== 3. YOX OLAN KOD-LAR: SOFT-DELETE ====================
        $codesToDelete = array_diff($existingCodes, $newCodes);
        foreach ($codesToDelete as $code) {
            /** @var ComplaintDetail $detail */
            $detail = $existingByCode[$code];
            $detail->delete();  // Auditable trait → 'deleted' event
        }
    }
}
