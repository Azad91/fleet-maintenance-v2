<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasGarageScope
{
    protected static function bootHasGarageScope()
    {
        static::addGlobalScope('garage', function (Builder $builder) {
            if (session('current_garage_id')) {
                $builder->where('garage_id', session('current_garage_id'));
            }
        });
    }

    public function garage()
    {
        return $this->belongsTo(\App\Models\Garage::class);
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
}
