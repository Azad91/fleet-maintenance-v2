<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Helper for logging audit entries on PIVOT table changes.
 *
 * Eloquent does not fire model events for pivot table operations
 * (`attach`, `detach`, `sync`, `updateExistingPivot`), so the Auditable
 * trait cannot see them. This service provides a single, consistent
 * entry point that all call sites use, so the audit shape stays uniform
 * and the logic is easy to find and review.
 *
 * Long-term alternative: custom events or a dedicated pivot model.
 * For now, explicit calls are simpler and more discoverable.
 */
class PivotAuditService
{
    /**
     * Record a pivot change in the audit log.
     *
     * @param  Model  $subject  The parent model the pivot belongs to
     *                          (Company for company_user, Garage for
     *                          garage_user, User for cross-references).
     * @param  string  $event  Semantic event name, e.g. 'director_assigned'.
     * @param  array|null  $oldValues  Previous state (null when newly attached).
     * @param  array|null  $newValues  New state (null when removed).
     * @param  int|null  $garageId  Garage context for the audit row.
     * @param  int|null  $companyId  Company context for the audit row.
     */
    public function log(
        Model $subject,
        string $event,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $garageId = null,
        ?int $companyId = null,
    ): void {
        AuditLog::create([
            'user_id' => auth()->id(),
            'garage_id' => $garageId,
            'company_id' => $companyId,
            'auditable_type' => get_class($subject),
            'auditable_id' => $subject->getKey(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
