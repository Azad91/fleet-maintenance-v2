<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'email', 'phone', 'address', 'logo', 'is_active'];

    public function garages()
    {
        return $this->hasMany(Garage::class);
    }

    /**
     * Company Directors (via company_user pivot).
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'company_user')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }

    /**
     * Active directors only.
     */
    public function directors()
    {
        return $this->users()
            ->wherePivot('role', 'director')
            ->wherePivot('is_active', true);
    }
}
