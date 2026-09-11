<?php

namespace App\Models\Traits;

use App\Models\User;

/**
 * Automatically populates the `created_by` column on model creation
 * and exposes a `creator()` relation.
 */
trait HasCreatedBy
{
    protected static function bootHasCreatedBy(): void
    {
        static::creating(function ($model) {
            if ($model->created_by === null && auth()->check()) {
                $model->created_by = auth()->id();
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
