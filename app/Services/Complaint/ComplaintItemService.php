<?php

namespace App\Services\Complaint;

use App\Models\Complaint;

class ComplaintItemService
{
    public function syncItems(Complaint $complaint, array $items, ?string $type): void
    {
        $complaint->items()->delete();

        foreach (array_filter(array_map('trim', $items)) as $description) {
            $complaint->items()->create([
                'description' => $description,
                'type' => $type,
            ]);
        }
    }
}
