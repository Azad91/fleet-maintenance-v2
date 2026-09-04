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
        'yer',               // hələlik saxlanılır (sonra 'location' olacaq)
        'driver_name',       // əvvəl: surucu_adi
        'complaint_type',    // əvvəl: sikayet_tipi
        'status',
        'km',
        'reported_date',     // əvvəl: bildirilme_tarix
        'reported_time',     // əvvəl: bildirilme_saat
        'start_date',        // əvvəl: is_baslama_tarix
        'start_time',        // əvvəl: is_baslama_saat
        'end_date',          // əvvəl: is_bitme_tarix
        'end_time',          // əvvəl: is_bitme_saat
        'work_done_by',      // əvvəl: kim_is_gorub
        'employee_id',
        'service_template_id',
        'service_km',
        'notes',             // əvvəl: qeyd
        'closed_at',
        'closed_by',
        'created_by',
    ];

    protected $casts = [
        'detallar' => 'array', // Bu hələlik JSON olaraq qalır (deprecated, amma hələ var)
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

    public function details()
    {
        return $this->hasMany(ComplaintDetail::class);
    }
}
