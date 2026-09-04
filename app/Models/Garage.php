<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Garage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['company_id', 'name', 'code', 'address', 'phone', 'is_active'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'garage_user')->withPivot('role', 'is_active')->withTimestamps();
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

    public static function getCurrentId()
    {
        return session('current_garage_id') ?? auth()->user()?->current_garage_id;
    }

    public static function getCurrentCompanyId()
    {
        return session('current_company_id') ?? auth()->user()?->current_company_id;
    }
}
