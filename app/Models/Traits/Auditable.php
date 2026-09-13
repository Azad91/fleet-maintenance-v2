<?php

namespace App\Models\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

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

        // Restore event (only for models with SoftDeletes)
        static::restored(function (Model $model) {
            $model->writeAudit(
                'restored',
                ['deleted_at' => $model->getOriginal('deleted_at')],
                ['deleted_at' => null]
            );
        });
    }

    protected static function filterAuditValues(array $values): array
    {
        $excluded = static::$auditBaseExcludedFields;

        // Alt-model əlavə sahələr təyin edibsə, onları da əlavə et
        if (property_exists(static::class, 'auditExcluded')) {
            $excluded = array_merge($excluded, static::$auditExcluded);
        }

        return array_diff_key($values, array_flip($excluded));
    }

    protected function writeAudit(string $event, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'garage_id' => $this->garage_id ?? null,
            'company_id' => $this->company_id ?? null,
            'auditable_type' => get_class($this),
            'auditable_id' => $this->getKey(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }

    /**
     * Write a single audit log entry per record for a bulk update.
     *
     * Only fields whose values genuinely changed are recorded. Values are
     * normalized before comparison so that DB-returned strings ('1') and
     * PHP-native scalars (1, true) are treated as equal — since they
     * represent the same underlying value.
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

            // Detect genuinely changed fields
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
                'garage_id' => $oldRecord->garage_id ?? null,
                'company_id' => $oldRecord->company_id ?? null,
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
     *
     * Each record's original values are snapshotted before deletion so
     * the audit trail preserves what was removed.
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
                'garage_id' => $oldRecord->garage_id ?? null,
                'company_id' => $oldRecord->company_id ?? null,
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

    /**
     * Determine whether two values differ for audit purposes.
     *
     * Values are normalized first, so that type mismatches alone (e.g.
     * DB returning '1' vs PHP int 1, or PostgreSQL returning true vs
     * PHP int 1) do not trigger a false-positive audit entry.
     *
     * Genuine differences (null vs '', null vs 0, 1 vs 2, etc.) are
     * still correctly detected.
     */
    protected static function valuesDiffer(mixed $old, mixed $new): bool
    {
        return static::normalizeForComparison($old)
            !== static::normalizeForComparison($new);
    }

    /**
     * Normalize a value for comparison.
     *
     * Rules:
     *   - null stays null (null must remain distinct from '', 0, false)
     *   - bool → '1' or '0' (PostgreSQL returns true/false for booleans)
     *   - array/object → returned as-is (PHP's === handles them)
     *   - scalar (int/float/string) → cast to string for comparison
     */
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
