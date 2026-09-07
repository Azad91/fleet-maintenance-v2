<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use App\Services\GarageContext;

trait HasGarageScope
{
    protected static function bootHasGarageScope()
    {
        static::addGlobalScope('garage', function (Builder $builder) {
            if (GarageContext::has()) {
                $builder->where($builder->getModel()->getTable() . '.garage_id', GarageContext::getGarageId());
            }
        });

        static::creating(function ($model) {
            // Əgər kontekst varsa, avtomatik set et
            if (GarageContext::has()) {
                $model->garage_id ??= GarageContext::getGarageId();
                $model->company_id ??= GarageContext::getCompanyId();
            }

            // Əgər nə modeldə, nə də kontekstdə garage_id yoxdursa, xəta at
            if (empty($model->garage_id)) {
                throw new \RuntimeException('Garage context not set. Cannot create model without garage_id.');
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
