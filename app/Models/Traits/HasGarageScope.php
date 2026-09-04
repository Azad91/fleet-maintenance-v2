<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Garage;

trait HasGarageScope
{
    protected static function bootHasGarageScope()
    {
        // 🔥 GLOBAL SCOPE (OXUYANDA)
        static::addGlobalScope('garage', function (Builder $builder) {
            $garageId = Garage::getCurrentId();
            if (!app()->runningInConsole() && $garageId) {
                $builder->where('garage_id', $garageId);
            }
        });

        // 🔥 YARADANDA AVTOMATİK YAZ
        static::creating(function ($model) {
            $garageId = Garage::getCurrentId();
            if (!app()->runningInConsole() && $garageId) {
                $model->garage_id = $garageId;
                $model->company_id = Garage::getCurrentCompanyId();
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
