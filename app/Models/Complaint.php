<?php

namespace App\Models;

use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Complaint extends Model
{
    use HasGarageScope, SoftDeletes;

    protected $fillable = [
        'garage_id',
        'company_id',
        'bus_id',
        'yer',
        'surucu_adi',
        'shikayet',
        'sikayet_tipi',
        'status',
        'detallar',
        'km',
        'bildirilme_tarix',
        'bildirilme_saat',
        'is_baslama_tarix',
        'is_baslama_saat',
        'is_bitme_tarix',
        'is_bitme_saat',
        'kim_is_gorub',
        'employee_id',
        'service_template_id',
        'service_km',
        'qeyd',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'detallar' => 'array',
        'bildirilme_tarix' => 'date',
        'is_baslama_tarix' => 'date',
        'is_bitme_tarix' => 'date',
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
        return $query->where('sikayet_tipi', $type);
    }

    // ==================== AKSESSORLAR ====================
    public function getIsOpenAttribute()
    {
        return $this->status !== 'həll olundu';
    }

    public function getDurationAttribute()
    {
        if ($this->is_baslama_tarix && $this->is_bitme_tarix) {
            return $this->is_baslama_tarix->diffInDays($this->is_bitme_tarix) . ' gün';
        }
        return '-';
    }

    protected static function booted()
    {
        static::created(function (self $complaint) {
            $complaint->writeAudit('created', null, $complaint->getAttributes());
        });

        static::updating(function (self $complaint) {
            $newValues = $complaint->getDirty();
            $oldValues = array_intersect_key($complaint->getOriginal(), $newValues);
            $complaint->writeAudit('updated', $oldValues, $newValues);
        });

        static::deleted(function (self $complaint) {
            $complaint->writeAudit('deleted', $complaint->getOriginal(), null);
        });
    }

    private function writeAudit(string $event, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'garage_id' => $this->garage_id,
            'company_id' => $this->company_id,
            'auditable_type' => self::class,
            'auditable_id' => $this->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
