<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasGarageScope
{
    protected static function bootHasGarageScope()
    {
        // 🔥 GLOBAL SCOPE (OXUYANDA)
        static::addGlobalScope('garage', function (Builder $builder) {
            // ✅ YALNIZ HTTP REQUEST ZAMANI TƏTBİQ ET
            if (!app()->runningInConsole() && session('current_garage_id')) {
                $builder->where('garage_id', session('current_garage_id'));
            }
        });

        // 🔥 YARADANDA AVTOMATİK YAZ
        static::creating(function ($model) {
            // ✅ YALNIZ HTTP REQUEST ZAMANI YAZ
            if (!app()->runningInConsole() && session('current_garage_id')) {
                $model->garage_id = session('current_garage_id');
                $model->company_id = session('current_company_id');
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
