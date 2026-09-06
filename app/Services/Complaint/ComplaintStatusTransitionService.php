<?php

namespace App\Services\Complaint;

use App\Models\Complaint;
use Illuminate\Validation\ValidationException;

class ComplaintStatusTransitionService
{
    /**
     * İcazə verilən status keçidləri xəritəsi
     */
    protected const ALLOWED_TRANSITIONS = [
        'gözləmədə' => ['gözləmədə', 'işdə', 'həll olundu', 'ləğv edildi'],
        'işdə'      => ['işdə', 'gözləmədə', 'həll olundu', 'ləğv edildi'],
        'həll olundu' => ['həll olundu'], // Bağlanmış kart dəyişdirilə bilməz
        'ləğv edildi' => ['ləğv edildi'],
    ];

    /**
     * Keçidin mümkünlüyünü yoxlayır
     */
    public function canTransition(string $currentStatus, string $newStatus): bool
    {
        $allowed = self::ALLOWED_TRANSITIONS[$currentStatus] ?? [];
        return in_array($newStatus, $allowed, true);
    }

    /**
     * Keçidi yoxlayır və uyğunsuzluq olduqda exception atır
     */
    public function validateTransition(Complaint $complaint, string $newStatus): void
    {
        $currentStatus = $complaint->status ?? 'gözləmədə';

        if ($currentStatus === $newStatus) {
            return;
        }

        if (!$this->canTransition($currentStatus, $newStatus)) {
            throw ValidationException::withMessages([
                'status' => "'{$currentStatus}' statusundan '{$newStatus}' statusuna keçid icazəli deyil."
            ]);
        }
    }
}
