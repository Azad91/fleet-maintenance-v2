<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ComplaintItem extends Model
{
    use Auditable, HasGarageScope;

    protected $fillable = [
        'complaint_id',
        'description',
        'type',
        'garage_id',
        'company_id',
    ];

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
     * The manual `where('complaints.garage_id', ...)` filter has been
     * removed — HasGarageScope now applies it automatically on the
     * `complaint_items` table itself. This makes the scope bulletproof
     * even if a future refactor drops the explicit join condition.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecurring($query, int $days = 30)
    {
        // Defense-in-depth: this scope has no meaning without a garage
        // context. HasGarageScope deliberately disables itself in the
        // console (including PHPUnit), so relying on it alone would let
        // a missing context return cross-tenant data. We block explicitly.
        $garageId = \App\Services\GarageContext::resolveGarageId();

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
            ->groupBy('complaint_items.description', 'complaints.bus_id')
            ->havingRaw('COUNT(*) >= 2')
            ->orderByDesc('total');
    }
}
