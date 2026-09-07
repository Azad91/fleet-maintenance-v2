<?php

namespace App\Models\Traits;

use App\Services\GarageContext;
use Illuminate\Database\Eloquent\Builder;

trait HasGarageScope
{
    protected static function bootHasGarageScope()
    {
        // 🔥 GLOBAL SCOPE (OXUYANDA)
        static::addGlobalScope('garage', function (Builder $builder) {
            // Əgər konsoldadırsa (migrate, seed və s.) və context yoxdursa, filtri tətbiq etmə
            if (app()->runningInConsole() && ! GarageContext::has()) {
                return;
            }

            if (GarageContext::has()) {
                $builder->where($builder->getModel()->getTable().'.garage_id', GarageContext::getGarageId());
            }
        });

        // 🔥 YARADANDA AVTOMATİK YAZ
        static::creating(function ($model) {
            if (GarageContext::has()) {
                $model->garage_id = GarageContext::getGarageId();
                $model->company_id = GarageContext::getCompanyId();
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
