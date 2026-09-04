<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use App\Services\GarageContext;

trait HasGarageScope
{
    protected static function bootHasGarageScope()
    {
        // 🔥 GLOBAL SCOPE (OXUYANDA)
        static::addGlobalScope('garage', function (Builder $builder) {
            // ✅ YALNIZ HTTP REQUEST ZAMANI TƏTBİQ ET
            if (!app()->runningInConsole() && GarageContext::has()) {
                $builder->where('garage_id', GarageContext::getGarageId());
            }
        });

        // 🔥 YARADANDA AVTOMATİK YAZ
        static::creating(function ($model) {
            // ✅ YALNIZ HTTP REQUEST ZAMANI YAZ
            if (!app()->runningInConsole() && GarageContext::has()) {
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
