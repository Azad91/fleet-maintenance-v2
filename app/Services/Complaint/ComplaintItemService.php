<?php

namespace App\Services\Complaint;

use App\Models\Complaint;

class ComplaintItemService
{
    public function syncItems(Complaint $complaint, array $items, ?string $type): void
    {
        $validItems = array_values(array_filter(array_map('trim', $items)));
        $existingItems = $complaint->items()->orderBy('id')->get();

        foreach ($validItems as $index => $description) {
            if ($existingItems->has($index)) {
                $item = $existingItems[$index];
                if ($item->description !== $description || $item->type !== $type) {
                    $item->update([
                        'description' => $description,
                        'type' => $type,
                    ]);
                }
            } else {
                $complaint->items()->create([
                    'description' => $description,
                    'type' => $type,
                ]);
            }
        }

        if ($existingItems->count() > count($validItems)) {
            $itemsToDelete = $existingItems->slice(count($validItems))->pluck('id');
            $complaint->items()->whereIn('id', $itemsToDelete)->delete();
        }
    }
}
