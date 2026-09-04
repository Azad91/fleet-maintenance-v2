<?php

namespace App\Models;

use App\Models\Traits\HasGarageScope;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Complaint extends Model
{
    use HasGarageScope, SoftDeletes, Auditable;

    protected $fillable = [
        'garage_id',
        'company_id',
        'bus_id',
        'driver_id',
        'yer',
        'driver_name',
        'complaint_type',
        'status',
        'km',
        'reported_date',
        'reported_time',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'work_done_by',
        'employee_id',
        'service_template_id',
        'service_km',
        'notes',
        'closed_at',
        'closed_by',
        'created_by',
    ];

    protected $casts = [
        'reported_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
    ];

    // ==================== ƏLAQƏLƏR ====================
    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function serviceTemplate()
    {
        return $this->belongsTo(ServiceTemplate::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function items()
    {
        return $this->hasMany(ComplaintItem::class);
    }

    public function details()
    {
        return $this->hasMany(ComplaintDetail::class);
    }

    // ==================== SKOPLAR ====================
    public function scopeOpen($query)
    {
        return $query->where('status', '!=', 'həll olundu');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'həll olundu');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('complaint_type', $type);
    }

    // ==================== AKSESSORLAR ====================
    public function getIsOpenAttribute()
    {
        return $this->status !== 'həll olundu';
    }

    public function getDurationAttribute()
    {
        if ($this->start_date && $this->end_date) {
            return $this->start_date->diffInDays($this->end_date) . ' gün';
        }
        return '-';
    }
}
