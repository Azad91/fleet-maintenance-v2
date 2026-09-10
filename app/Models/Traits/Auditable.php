<?php

namespace App\Models\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    /**
     * Bu sahələr heç vaxt audit loglarına yazılmır.
     * Alt-modellər öz `$auditExcluded` property-si ilə genişləndirə bilər.
     */
    protected static array $auditBaseExcludedFields = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected static function bootAuditable(): void
    {
        // ✅ YARADILMA
        static::created(function (Model $model) {
            $newValues = static::filterAuditValues($model->getAttributes());

            if (empty($newValues)) {
                return;
            }

            $model->writeAudit('created', null, $newValues);
        });

        // ✅ YENİLƏNMƏ — yalnız save müvəffəqiyyətli olduqdan sonra
        static::updated(function (Model $model) {
            $newValues = static::filterAuditValues($model->getChanges());

            if (empty($newValues)) {
                return; // Heç nə dəyişməyibsə, log yazma
            }

            // `updated` event-i əsnasında getOriginal() hələ köhnə dəyərləri saxlayır
            $oldValues = array_intersect_key(
                static::filterAuditValues($model->getOriginal()),
                $newValues
            );

            $model->writeAudit('updated', $oldValues, $newValues);
        });

        // ✅ SİLİNMƏ — soft / force ayırd edilir
        static::deleted(function (Model $model) {
            $isForceDelete = method_exists($model, 'isForceDeleting')
                && $model->isForceDeleting();

            $model->writeAudit(
                $isForceDelete ? 'force_deleted' : 'deleted',
                static::filterAuditValues($model->getOriginal()),
                null
            );
        });

        // ✅ BƏRPA (yalnız SoftDeletes olan modellər üçün)
        static::restored(function (Model $model) {
            $model->writeAudit(
                'restored',
                ['deleted_at' => $model->getOriginal('deleted_at')],
                ['deleted_at' => null]
            );
        });
    }

    /**
     * Həssas və mənasız sahələri audit payload-indən çıxarır.
     */
    protected static function filterAuditValues(array $values): array
    {
        $excluded = static::$auditBaseExcludedFields;

        // Alt-model əlavə sahələr təyin edibsə, onları da əlavə et
        if (property_exists(static::class, 'auditExcluded')) {
            $excluded = array_merge($excluded, static::$auditExcluded);
        }

        return array_diff_key($values, array_flip($excluded));
    }

    /**
     * Audit logu yazır.
     */
    protected function writeAudit(string $event, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'user_id'        => auth()->id(),
            'garage_id'      => $this->garage_id ?? null,
            'company_id'     => $this->company_id ?? null,
            'auditable_type' => get_class($this),
            'auditable_id'   => $this->getKey(),
            'event'          => $event,
            'old_values'     => $oldValues,
            'new_values'     => $newValues,
        ]);
    }

    /**
     * Toplu yeniləmə əməliyyatını audit edir.
     */
    public static function auditBulkUpdate(
        array $ids,
        array $newValues,
        string $event = 'bulk_updated'
    ): void {
        $newValues = static::filterAuditValues($newValues);

        if (empty($newValues)) {
            return;
        }

        $oldRecords = static::whereIn('id', $ids)->get()->keyBy('id');

        foreach ($oldRecords as $id => $oldRecord) {
            $oldArray = static::filterAuditValues($oldRecord->getOriginal());

            // Faktiki olaraq dəyişən sahələri tap
            $changed = [];
            foreach ($newValues as $key => $value) {
                $oldValue = $oldArray[$key] ?? null;

                // Sərt tip yoxlaması — 0 === '0' kimi problemlərin qarşısını alır
                if ($oldValue !== $value) {
                    $changed[$key] = $value;
                }
            }

            if (empty($changed)) {
                continue;
            }

            AuditLog::create([
                'user_id'        => auth()->id(),
                'garage_id'      => $oldRecord->garage_id ?? null,
                'company_id'     => $oldRecord->company_id ?? null,
                'auditable_type' => static::class,
                'auditable_id'   => $id,
                'event'          => $event,
                'old_values'     => array_intersect_key($oldArray, $changed),
                'new_values'     => $changed,
            ]);
        }
    }

    /**
     * Toplu silmə əməliyyatını audit edir.
     */
    public static function auditBulkDelete(
        array $ids,
        string $event = 'bulk_deleted'
    ): void {
        if (empty($ids)) {
            return;
        }

        $oldRecords = static::whereIn('id', $ids)->get();

        foreach ($oldRecords as $oldRecord) {
            AuditLog::create([
                'user_id'        => auth()->id(),
                'garage_id'      => $oldRecord->garage_id ?? null,
                'company_id'     => $oldRecord->company_id ?? null,
                'auditable_type' => static::class,
                'auditable_id'   => $oldRecord->getKey(),
                'event'          => $event,
                'old_values'     => static::filterAuditValues($oldRecord->getOriginal()),
                'new_values'     => null,
            ]);
        }
    }

    /**
     * Model üçün audit logları.
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
