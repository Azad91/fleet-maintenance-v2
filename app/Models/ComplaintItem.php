<?php

namespace App\Models;

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
     * Eyni avtobusda təkrar olunan şikayətləri tapır.
     *
     * Bir şikayət "təkrar" sayılır əgər:
     *  - son $days gün ərzində eyni avtobusda,
     *  - eyni mətnlə,
     *  - ən azı 2 dəfə,
     *  - hələ "həll olundu" statusuna keçməyibsə.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecurring($query, int $days = 30)
    {
        $garageId = Garage::getCurrentId();

        // ✅ QORUMA: Qaraj konteksti yoxdursa, heç nə qaytarma
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
            // ✅ Soft-deleted complaints-ləri sayma
            ->whereNull('complaints.deleted_at')
            // ✅ Yalnız son $days gün
            ->where('complaints.created_at', '>=', now()->subDays($days))
            // ✅ "həll olundu" OLMAYAN (NULL-ları da daxil etməklə)
            ->where(function ($q) {
                $q->where('complaints.status', '!=', 'həll olundu')
                  ->orWhereNull('complaints.status');
            })
            // ✅ Cari qarajla məhdudlaşdır
            ->where('complaints.garage_id', $garageId)
            ->groupBy('complaint_items.description', 'complaints.bus_id')
            ->havingRaw('COUNT(*) >= 2')
            ->orderByDesc('total');
    }
}
