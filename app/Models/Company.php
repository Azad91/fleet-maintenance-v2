<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
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
