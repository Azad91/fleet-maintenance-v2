<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use App\Models\ComplaintItem;

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
                    // garage_id and company_id are set by HasGarageScope's
                    // creating event via GarageContext, but we pass them
                    // explicitly so the row is correct even when the
                    // calling code runs outside a normal web request
                    // (e.g. queue jobs, console imports).
                    'garage_id' => $complaint->garage_id,
                    'company_id' => $complaint->company_id,
                ]);
            }
        }

        if ($existingItems->count() > count($validItems)) {
            $idsToDelete = $existingItems->slice(count($validItems))->pluck('id')->all();

            // IMPORTANT: `$complaint->items()->whereIn(...)->delete()`
            // would run as a Query Builder delete and skip Eloquent's
            // `deleted` event — meaning no audit log would be written.
            // We load the models and delete them one by one so the
            // Auditable trait fires for each row.
            ComplaintItem::whereIn('id', $idsToDelete)->get()->each->delete();
        }
    }
}
