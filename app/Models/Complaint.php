<?php

namespace App\Models;

use App\Enums\ComplaintStatus;
use App\Enums\ComplaintType;
use App\Enums\Location;
use App\Models\Traits\Auditable;
use App\Models\Traits\HasGarageScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Complaint extends Model
{
    use Auditable, HasGarageScope, SoftDeletes;

    protected $fillable = [
        'garage_id',
        'company_id',
        'bus_id',
        'driver_id',
        'service_vehicle_id',
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
        'status' => ComplaintStatus::class,
        'complaint_type' => ComplaintType::class,
        'yer' => Location::class,
        'reported_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
    ];

    // ==================== RELATIONS ====================

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

    /**
     * The specific service vehicle that performed the work on the road.
     *
     * Only populated when `yer = 'road'`. Garage complaints have no
     * service vehicle — their stock is sourced from the warehouse.
     */
    public function serviceVehicle()
    {
        return $this->belongsTo(ServiceVehicle::class, 'service_vehicle_id');
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

    // ==================== SCOPES ====================

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [
            ComplaintStatus::Pending->value,
            ComplaintStatus::InProgress->value,
        ]);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', [
            ComplaintStatus::Completed->value,
            ComplaintStatus::Cancelled->value,
        ]);
    }

    public function scopeByType($query, ComplaintType|string $type)
    {
        $value = $type instanceof ComplaintType ? $type->value : $type;

        return $query->where('complaint_type', $value);
    }

    // ==================== ACCESSORS ====================

    public function getIsOpenAttribute(): bool
    {
        return $this->status instanceof ComplaintStatus
            ? $this->status->isOpen()
            : false;
    }

    /**
     * Human-readable duration between start_date and end_date.
     * Uses the localized 'days' translation string.
     */
    public function getDurationAttribute(): string
    {
        if ($this->start_date && $this->end_date) {
            $days = $this->start_date->diffInDays($this->end_date);

            return $days.' '.__('messages.common.days');
        }

        return '-';
    }

    /**
     * True when the complaint is being worked on the road and must be
     * tied to a specific service vehicle.
     */
    public function requiresServiceVehicle(): bool
    {
        return $this->yer instanceof Location && $this->yer->isRoad();
    }
}
