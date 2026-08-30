<?php

namespace App\Models\Traits;

use App\Models\AuditLog;

trait Auditable
{
    protected static function bootAuditable()
    {
        static::created(function ($model) {
            $model->writeAudit('created', null, $model->getAttributes());
        });

        static::updating(function ($model) {
            $newValues = $model->getDirty();
            $oldValues = array_intersect_key($model->getOriginal(), $newValues);
            $model->writeAudit('updated', $oldValues, $newValues);
        });

        static::deleted(function ($model) {
            $model->writeAudit('deleted', $model->getOriginal(), null);
        });
    }

    protected function writeAudit(string $event, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'garage_id' => $this->garage_id ?? null,
            'company_id' => $this->company_id ?? null,
            'auditable_type' => get_class($this),
            'auditable_id' => $this->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
