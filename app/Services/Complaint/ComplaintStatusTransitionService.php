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
     * The `$complaint->status` value is stored as a plain string
     * (no model cast yet), so we resolve it to a ComplaintStatus
     * via tryFrom() and default to Pending if the value is null
     * or unrecognized.
     */
    public function validateTransition(Complaint $complaint, string $newStatusValue): void
    {
        $current = ComplaintStatus::tryFrom((string) $complaint->status)
            ?? ComplaintStatus::Pending;

        $next = ComplaintStatus::tryFrom($newStatusValue);

        if ($next === null) {
            throw ValidationException::withMessages([
                'status' => __('messages.flash.invalid_status_transition', [
                    'from' => $current->value,
                    'to'   => $newStatusValue,
                ]),
            ]);
        }

        // Self-transition is a no-op — always allowed.
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
