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
            // ✅ Console şərti silindi. GarageContext varsa, HƏMİŞƏ tətbiq et!
            if (GarageContext::has()) {
                $builder->where($builder->getModel()->getTable() . '.garage_id', GarageContext::getGarageId());
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
