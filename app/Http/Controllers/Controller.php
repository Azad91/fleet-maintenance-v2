<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\Garage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Cari qaraj və company ID-lərini götürüb data-ya əlavə edir.
     */
    protected function addGarageContext(array $data): array
    {
        $data['garage_id'] = Garage::getCurrentId();
        $data['company_id'] = Garage::getCurrentCompanyId();

        return $data;
    }

    /**
     * Rol string-lərini RoleEnum-dan al.
     */
    protected function getRoleString(array|string $roles): string
    {
        if (is_string($roles)) {
            return $roles;
        }

        return implode(',', $roles);
    }

    /**
     * İdxal nəticəsini strukturlaşdırılmış formada qurur.
     *
     * @param  int  $imported  Uğurlu idxal sayı
     * @param  array<int, array{row: int, dqn: string, reason: string}>  $skipped  Manual atlanan sətirlər
     * @param  \Illuminate\Support\Collection  $failures  Validation xətaları
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
                'reason' => $row['reason'] ?? 'Naməlum səbəb',
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
