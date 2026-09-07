<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use App\Services\GarageContext;

trait HasGarageScope
{
    protected static function bootHasGarageScope()
    {
        // 🔥 GLOBAL SCOPE – HƏMİŞƏ TƏTBİQ EDİLİR
        static::addGlobalScope('garage', function (Builder $builder) {
            $garageId = GarageContext::getGarageId();

            // ✅ DƏYİŞİKLİK: Əgər context yoxdursa, heç bir nəticə qaytarma (0-a bərabər şərt)
            if ($garageId === null) {
                $builder->whereRaw('1 = 0');
                return;
            }

            $builder->where(
                $builder->getModel()->getTable() . '.garage_id',
                $garageId
            );
        });

        // 🔥 YARADANDA AVTOMATİK YAZ
        static::creating(function ($model) {
            $garageId = GarageContext::getGarageId();
            $companyId = GarageContext::getCompanyId();

            if ($garageId === null) {
                // Context yoxdursa, model yaradılmasın – xəta at
                throw new \RuntimeException('Garage context not set. Cannot create model without garage_id.');
            }

            $model->garage_id = $garageId;
            $model->company_id = $companyId;
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
