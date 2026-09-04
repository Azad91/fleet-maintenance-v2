<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ComplaintItem extends Model
{
    protected $fillable = ['complaint_id', 'description', 'type'];

    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }

    public function scopeRecurring($query, $days = 30)
    {
        return $query->select(
                'complaint_items.description',
                'complaints.bus_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('MAX(complaints.created_at) as last_occurrence')
            )
            ->join('complaints', 'complaints.id', '=', 'complaint_items.complaint_id')
            ->where('complaints.created_at', '>=', now()->subDays($days))
            ->where('complaints.status', '!=', 'həll olundu')
            ->where('complaints.garage_id', \App\Models\Garage::getCurrentId())
            ->groupBy('complaint_items.description', 'complaints.bus_id')
            ->having(DB::raw('COUNT(*)'), '>=', 2)
            ->with('complaint.bus');
    }
}
