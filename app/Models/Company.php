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

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
