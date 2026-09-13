<?php

namespace App\Services\Complaint;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use Illuminate\Validation\ValidationException;

class ComplaintStatusTransitionService
{
    /**
     * Validate that the requested status change is legal.
     *
     * `$complaint->status` is now cast to ComplaintStatus via the
     * model, so we can read it directly. If the DB value is unknown
     * (should not happen — enforced by a CHECK constraint), we fall
     * back to Pending.
     */
    public function validateTransition(Complaint $complaint, string|ComplaintStatus $newStatus): void
    {
        $current = $complaint->status instanceof ComplaintStatus
            ? $complaint->status
            : ComplaintStatus::Pending;

        $next = $newStatus instanceof ComplaintStatus
            ? $newStatus
            : ComplaintStatus::tryFrom($newStatus);

        if ($next === null) {
            throw ValidationException::withMessages([
                'status' => __('messages.flash.invalid_status_transition', [
                    'from' => $current->value,
                    'to'   => (string) $newStatus,
                ]),
            ]);
        }

        if ($current === $next) {
            return;
        }

        if (! $current->canTransitionTo($next)) {
            throw ValidationException::withMessages([
                'status' => __('messages.flash.invalid_status_transition', [
                    'from' => $current->value,
                    'to'   => $next->value,
                ]),
            ]);
        }
    }
}
