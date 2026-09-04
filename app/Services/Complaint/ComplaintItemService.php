<?php

namespace App\Services\Complaint;

use App\Models\Complaint;

class ComplaintItemService
{
    public function syncItems(Complaint $complaint, array $items, ?string $type): void
    {
        // Gələn məlumatları təmizləyib (boşları silib) indeksləri sıfırlayırıq
        $validItems = array_values(array_filter(array_map('trim', $items)));
        $existingItems = $complaint->items()->orderBy('id')->get();

        foreach ($validItems as $index => $description) {
            if ($existingItems->has($index)) {
                // Mövcud qeyd varsa, sadəcə yenilə
                $item = $existingItems[$index];
                if ($item->description !== $description || $item->type !== $type) {
                    $item->update([
                        'description' => $description,
                        'type' => $type,
                    ]);
                }
            } else {
                // Yeni qeyddirsə, yarat
                $complaint->items()->create([
                    'description' => $description,
                    'type' => $type,
                ]);
            }
        }

        // Əgər köhnə siyahı yenidən uzundursa, artıq qalan qeydləri sil
        if ($existingItems->count() > count($validItems)) {
            $itemsToDelete = $existingItems->slice(count($validItems))->pluck('id');
            $complaint->items()->whereIn('id', $itemsToDelete)->delete();
        }
    }
}
