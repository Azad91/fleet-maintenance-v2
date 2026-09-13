<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\Garage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Ensure the authenticated user is a super admin.
     *
     * Note: Super-admin routes are already protected by the
     * `super.admin` middleware. This method is a defense-in-depth
     * guard for code paths that may not be behind that middleware.
     */
    protected function ensureSuperAdmin(): void
    {
        abort_unless(
            auth()->user()?->isSuperAdmin() === true,
            403,
            __('messages.flash.permission_denied')
        );
    }

    protected function addGarageContext(array $data): array
    {
        $data['garage_id']  = Garage::getCurrentId();
        $data['company_id'] = Garage::getCurrentCompanyId();

        return $data;
    }

    protected function getRoleString(array|string $roles): string
    {
        if (is_string($roles)) {
            return $roles;
        }

        return implode(',', $roles);
    }

    /**
     * Build a structured import report.
     *
     * @param  int  $imported  Successful rows count
     * @param  array<int, array{row: int, dqn: string, reason: string}>  $skipped  Manually skipped rows
     * @param  \Illuminate\Support\Collection  $failures  Validation failures
     * @return array{imported: int, skipped: array, failed: array}
     */
    protected function buildImportReport(int $imported, array $skipped, $failures): array
    {
        $report = [
            'imported' => $imported,
            'skipped'  => [],
            'failed'   => [],
        ];

        foreach ($skipped as $row) {
            $report['skipped'][] = [
                'row'    => $row['row'] ?? '—',
                'dqn'    => $row['dqn'] ?? '—',
                'reason' => $row['reason'] ?? 'Unknown reason',
            ];
        }

        foreach ($failures as $failure) {
            $values = $failure->values();
            $report['failed'][] = [
                'row'    => $failure->row(),
                'dqn'    => $values['bus_dqn']
                    ?? $values['dqn']
                    ?? $values['kodu']
                    ?? $values['code']
                    ?? $values['ad']
                    ?? $values['first_name']
                    ?? '—',
                'reason' => implode(', ', $failure->errors()),
            ];
        }

        return $report;
    }
}
