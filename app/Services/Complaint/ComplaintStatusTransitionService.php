<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use Illuminate\Validation\ValidationException;

class ComplaintStatusTransitionService
{
    protected const ALLOWED_TRANSITIONS = [
        'pending'     => ['pending', 'in_progress', 'completed', 'cancelled'],
        'in_progress' => ['in_progress', 'pending', 'completed', 'cancelled'],
        'completed'   => ['completed'],
        'cancelled'   => ['cancelled'],
    ];

    public function canTransition(string $currentStatus, string $newStatus): bool
    {
        $allowed = self::ALLOWED_TRANSITIONS[$currentStatus] ?? [];

        return in_array($newStatus, $allowed, true);
    }

    public function validateTransition(Complaint $complaint, string $newStatus): void
    {
        $currentStatus = $complaint->status ?? 'pending';

        if ($currentStatus === $newStatus) {
            return;
        }

        if (! $this->canTransition($currentStatus, $newStatus)) {
            throw ValidationException::withMessages([
                'status' => __('messages.flash.invalid_status_transition', [
                    'from' => $currentStatus,
                    'to'   => $newStatus,
                ]),
            ]);
        }
    }
}