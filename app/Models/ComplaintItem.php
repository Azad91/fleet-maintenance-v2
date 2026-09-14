<?php

namespace App\Models;

use App\Services\GarageContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ComplaintItem extends Model
{
    protected $fillable = ['complaint_id', 'description', 'type'];

    // ==================== RELATIONS ====================

    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }

    // ==================== SCOPES ====================

    /**
     * Find recurring complaints on the same bus.
     *
     * A complaint is considered "recurring" if:
     *  - within the last $days days on the same bus,
     *  - with the same description,
     *  - at least twice,
     *  - and not yet in "completed" status.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecurring($query, int $days = 30)
    {
        $garageId = GarageContext::resolveGarageId();

        if (! $garageId) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->select(
                'complaint_items.description',
                'complaints.bus_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('MAX(complaints.created_at) as last_occurrence')
            )
            ->join('complaints', 'complaints.id', '=', 'complaint_items.complaint_id')
            ->whereNull('complaints.deleted_at')
            ->where('complaints.created_at', '>=', now()->subDays($days))
            ->where(function ($q) {
                $q->where('complaints.status', '!=', \App\Enums\ComplaintStatus::Completed->value)
                    ->orWhereNull('complaints.status');
            })
            ->where('complaints.garage_id', $garageId)
            ->groupBy('complaint_items.description', 'complaints.bus_id')
            ->havingRaw('COUNT(*) >= 2')
            ->orderByDesc('total');
    }
}
