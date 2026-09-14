<?php

namespace App\Models\Traits;

use App\Models\AuditLog;
use App\Services\GarageContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

trait Auditable
{
    protected static array $auditBaseExcludedFields = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected static function bootAuditable(): void
    {
        // Create event
        static::created(function (Model $model) {
            $newValues = static::filterAuditValues($model->getAttributes());

            if (empty($newValues)) {
                return;
            }

            $model->writeAudit('created', null, $newValues);
        });

        // Update event — only after successful save
        static::updated(function (Model $model) {
            $newValues = static::filterAuditValues($model->getChanges());

            if (empty($newValues)) {
                return;
            }

            $oldValues = array_intersect_key(
                static::filterAuditValues($model->getOriginal()),
                $newValues
            );

            $model->writeAudit('updated', $oldValues, $newValues);
        });

        // Delete event — soft delete or force delete
        static::deleted(function (Model $model) {
            $isForceDelete = method_exists($model, 'isForceDeleting')
                && $model->isForceDeleting();

            $model->writeAudit(
                $isForceDelete ? 'force_deleted' : 'deleted',
                static::filterAuditValues($model->getOriginal()),
                null
            );
        });

        // Restore event.
        //
        // IMPORTANT: `restored()` is defined on the SoftDeletes trait, NOT
        // on Model. Calling `static::restored(...)` on a model that does
        // not use SoftDeletes would fall through to Model::__callStatic,
        // which constructs a NEW model instance to proxy the call. Since
        // this boot method runs from inside `bootIfNotBooted()`, creating
        // a new instance re-enters `bootIfNotBooted()` while the model is
        // still booting, and PHP throws a LogicException.
        //
        // Guard the registration so it only runs when SoftDeletes is
        // actually in use.
        if (static::usesSoftDeletes()) {
            static::restored(function (Model $model) {
                $model->writeAudit(
                    'restored',
                    ['deleted_at' => $model->getOriginal('deleted_at')],
                    ['deleted_at' => null]
                );
            });
        }
    }

    /**
     * Determine whether the current model uses the SoftDeletes trait.
     */
    protected static function usesSoftDeletes(): bool
    {
        return in_array(
            SoftDeletes::class,
            class_uses_recursive(static::class),
            true
        );
    }

    protected static function filterAuditValues(array $values): array
    {
        $excluded = static::$auditBaseExcludedFields;

        if (property_exists(static::class, 'auditExcluded')) {
            $excluded = array_merge($excluded, static::$auditExcluded);
        }

        return array_diff_key($values, array_flip($excluded));
    }

    protected function writeAudit(string $event, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'garage_id' => $this->resolveAuditGarageId(),
            'company_id' => $this->resolveAuditCompanyId(),
            'auditable_type' => get_class($this),
            'auditable_id' => $this->getKey(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }

    /**
     * Resolve the garage_id to associate with this audit log.
     *
     * Default: the model's own garage_id attribute if present,
     * falling back to the current request's garage context.
     *
     * Models that do not have a `garage_id` attribute but represent
     * a garage themselves (e.g. the Garage model) override this
     * method to return their own primary key.
     */
    protected function resolveAuditGarageId(): ?int
    {
        if (! empty($this->garage_id)) {
            return (int) $this->garage_id;
        }

        return GarageContext::getGarageId();
    }

    /**
     * Resolve the company_id to associate with this audit log.
     *
     * Default: the model's own company_id attribute if present,
     * falling back to the current request's company context.
     *
     * The Company model overrides this to return its own primary key.
     */
    protected function resolveAuditCompanyId(): ?int
    {
        if (! empty($this->company_id)) {
            return (int) $this->company_id;
        }

        return GarageContext::getCompanyId();
    }

    /**
     * Write a single audit log entry per record for a bulk update.
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

            $changed = [];
            foreach ($newValues as $key => $value) {
                $oldValue = $oldArray[$key] ?? null;

                if (static::valuesDiffer($oldValue, $value)) {
                    $changed[$key] = $value;
                }
            }

            if (empty($changed)) {
                continue;
            }

            AuditLog::create([
                'user_id' => auth()->id(),
                'garage_id' => $oldRecord->resolveAuditGarageId(),
                'company_id' => $oldRecord->resolveAuditCompanyId(),
                'auditable_type' => static::class,
                'auditable_id' => $id,
                'event' => $event,
                'old_values' => array_intersect_key($oldArray, $changed),
                'new_values' => $changed,
            ]);
        }
    }

    /**
     * Write one audit log entry per record for a bulk delete.
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
                'user_id' => auth()->id(),
                'garage_id' => $oldRecord->resolveAuditGarageId(),
                'company_id' => $oldRecord->resolveAuditCompanyId(),
                'auditable_type' => static::class,
                'auditable_id' => $oldRecord->getKey(),
                'event' => $event,
                'old_values' => static::filterAuditValues($oldRecord->getOriginal()),
                'new_values' => null,
            ]);
        }
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    // ==================== VALUE COMPARISON ====================

    protected static function valuesDiffer(mixed $old, mixed $new): bool
    {
        return static::normalizeForComparison($old)
            !== static::normalizeForComparison($new);
    }

    protected static function normalizeForComparison(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value) || is_object($value)) {
            return $value;
        }

        return (string) $value;
    }
}
