<?php

namespace App\Models\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

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

        // ✅ Bulk update üçün
        static::updating(function ($model) {
            // Bulk update-ləri tutmaq üçün
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

    /**
     * Bulk update əməliyyatını audit et
     */
    public static function auditBulkUpdate(array $ids, array $newValues, string $event = 'bulk_updated'): void
    {
        $model = new static;
        $table = $model->getTable();

        // Köhnə dəyərləri al
        $oldRecords = DB::table($table)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id')
            ->toArray();

        foreach ($oldRecords as $id => $oldRecord) {
            $oldArray = (array) $oldRecord;
            $newArray = array_merge($oldArray, $newValues);

            // Yalnız dəyişən sahələri tap
            $changed = array_intersect_key($newValues, array_diff_assoc($oldArray, $newArray));

            if (! empty($changed)) {
                AuditLog::create([
                    'user_id' => auth()->id(),
                    'garage_id' => $model->garage_id ?? null,
                    'company_id' => $model->company_id ?? null,
                    'auditable_type' => get_class($model),
                    'auditable_id' => $id,
                    'event' => $event,
                    'old_values' => array_intersect_key($oldArray, $changed),
                    'new_values' => array_intersect_key($newArray, $changed),
                ]);
            }
        }
    }

    /**
     * Bulk delete əməliyyatını audit et
     */
    public static function auditBulkDelete(array $ids, string $event = 'bulk_deleted'): void
    {
        $model = new static;
        $table = $model->getTable();

        $oldRecords = DB::table($table)
            ->whereIn('id', $ids)
            ->get();

        foreach ($oldRecords as $oldRecord) {
            $oldArray = (array) $oldRecord;
            AuditLog::create([
                'user_id' => auth()->id(),
                'garage_id' => $model->garage_id ?? null,
                'company_id' => $model->company_id ?? null,
                'auditable_type' => get_class($model),
                'auditable_id' => $oldRecord->id,
                'event' => $event,
                'old_values' => $oldArray,
                'new_values' => null,
            ]);
        }
    }

    /**
     * Model üçün audit log-ları
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
