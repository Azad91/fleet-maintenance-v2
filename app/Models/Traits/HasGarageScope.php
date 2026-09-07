<?php

namespace App\Models\Traits;

use App\Models\Company;
use App\Models\Garage;
use App\Services\GarageContext;
use Illuminate\Database\Eloquent\Builder;

trait HasGarageScope
{
    protected static function bootHasGarageScope()
    {
        static::addGlobalScope('garage', function (Builder $builder) {
            $garageId = GarageContext::getGarageId();
            if (! $garageId) {
                throw new \RuntimeException('Garage context not set. Cannot execute query without garage_id.');
            }
            $builder->where($builder->getModel()->getTable().'.garage_id', $garageId);
        });

        static::creating(function ($model) {
            if (GarageContext::has()) {
                $model->garage_id ??= GarageContext::getGarageId();
                $model->company_id ??= GarageContext::getCompanyId();
            }

            if (empty($model->garage_id)) {
                throw new \RuntimeException('Garage context not set. Cannot create model without garage_id.');
            }
        });
    }

    public function garage()
    {
        return $this->belongsTo(Garage::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
