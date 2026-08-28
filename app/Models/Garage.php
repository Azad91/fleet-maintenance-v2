<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Garage extends Model
{
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
}
