<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

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

    /**
     * True when the given throwable is a PostgreSQL unique-violation
     * (SQLSTATE 23505). Used to turn a race condition between an
     * "is this unique?" pre-check and the subsequent insert into a
     * friendly validation error instead of a raw 500 stack trace.
     */
    protected function isUniqueViolation(\Throwable $e): bool
    {
        return $e instanceof QueryException
            && ($e->errorInfo[0] ?? null) === '23505';
    }

    /**
     * True when the request should receive a partial view instead of
     * the full page.
     *
     * The project has two AJAX detection conventions:
     *   1. The standard `X-Requested-With: XMLHttpRequest` header
     *      (sent automatically by jQuery/Axios/Alpine).
     *   2. A fallback `_ajax=1` query/body parameter used by a few
     *      legacy front-end scripts that do not set the header.
     *
     * `$request->ajax()` is deprecated in Laravel 11+, so we check
     * the header directly.
     *
     * Centralized here so every AJAX-aware controller shares the
     * exact same detection rule. Before this refactor the same
     * 3-line method was duplicated across five controllers.
     */
    protected function isAjaxRequest(Request $request): bool
    {
        return $request->header('X-Requested-With') === 'XMLHttpRequest'
            || $request->boolean('_ajax');
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
            'skipped' => [],
            'failed' => [],
        ];

        foreach ($skipped as $row) {
            $report['skipped'][] = [
                'row' => $row['row'] ?? '—',
                'dqn' => $row['dqn'] ?? '—',
                'reason' => $row['reason'] ?? 'Unknown reason',
            ];
        }

        foreach ($failures as $failure) {
            $values = $failure->values();
            $report['failed'][] = [
                'row' => $failure->row(),
                'dqn' => $values['bus_dqn']
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
