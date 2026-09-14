<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\HasCreatedBy;
use App\Services\GarageContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Garage extends Model
{
    use Auditable, HasCreatedBy, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'address',
        'phone',
        'is_active',
        // 'created_by' — HasCreatedBy trait tərəfindən avtomatik doldurulur.
        // Mass-assignment spoofing-inin qarşısını almaq üçün fillable DEYİL.
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'garage_user')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }

    public function buses()
    {
        return $this->hasMany(Bus::class);
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }

    public function dailyKmRecords()
    {
        return $this->hasMany(DailyKmRecord::class);
    }

    public function dailyStatuses()
    {
        return $this->hasMany(BusDailyStatus::class);
    }

    /**
     * A Garage IS a garage, so its audit logs are attached to its
     * own id (not a related garage). The trait's default resolver
     * would return null for `garage_id` since the Garage model has
     * no such attribute.
     */
    protected function resolveAuditGarageId(): ?int
    {
        return $this->id ? (int) $this->id : null;
    }

    /**
     * @deprecated Use GarageContext::resolveGarageId() directly.
     *
     * Kept for backward compatibility. Delegates to the centralized
     * resolution chain in GarageContext.
     */
    public static function getCurrentId(): ?int
    {
        return GarageContext::resolveGarageId();
    }

    /**
     * @deprecated Use GarageContext::resolveCompanyId() directly.
     */
    public static function getCurrentCompanyId(): ?int
    {
        return GarageContext::resolveCompanyId();
    }
}
