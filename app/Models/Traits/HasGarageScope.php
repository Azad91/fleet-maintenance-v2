<?php

namespace App\Models\Traits;

use App\Services\GarageContext;
use Illuminate\Database\Eloquent\Builder;

trait HasGarageScope
{
    protected static function bootHasGarageScope()
    {
        static::addGlobalScope('garage', function (Builder $builder) {
            if (app()->runningInConsole() && ! GarageContext::has()) {
                return;
            }

            if (GarageContext::has()) {
                $builder->where($builder->getModel()->getTable().'.garage_id', GarageContext::getGarageId());
            }
        });

        static::creating(function ($model) {
            // Əgər modelə artıq əl ilə garage_id təyin olunubsa, onu dəyişmə
            if ($model->garage_id !== null) {
                return;
            }

            // Əgər context varsa, ondan götür
            if (GarageContext::has()) {
                $model->garage_id = GarageContext::getGarageId();
                $model->company_id = GarageContext::getCompanyId();
            } else {
                // Konsolda (məsələn, seeder) işləyirsə, xəta atma, amma xəbərdar et
                if (! app()->runningInConsole()) {
                    throw new \Exception('Qaraj konteksti təyin edilməyib və model üçün garage_id null qala bilər.');
                }
                // Konsolda olduqda, default olaraq ilk qarajı təyin et (isteğe bağlı)
                // $model->garage_id = 1; // İstəsən aktivləşdir
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
