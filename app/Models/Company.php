<?php

namespace App\Models;

use App\Models\Traits\HasCreatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasCreatedBy, HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'logo',
        'is_active',
        'created_by',
    ];

    public function garages()
    {
        return $this->hasMany(Garage::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'company_user')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }

    public function directors()
    {
        return $this->users()
            ->wherePivot('role', 'director')
            ->wherePivot('is_active', true);
    }
}
