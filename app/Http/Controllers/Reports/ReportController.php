<?php

namespace App\Http\Controllers\Reports;

use App\Exports\Reports\GenericReportExport;
use App\Http\Controllers\Controller;
use App\Services\Reports\ReportPeriod;
use App\Services\Reports\ReportScope;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

abstract class ReportController extends Controller
{
    /**
     * Resolve the report period from the request.
     */
    protected function period(Request $request): ReportPeriod
    {
        return ReportPeriod::fromRequest($request);
    }

    /**
     * Resolve the report scope for the given domain.
     *
     * Domains: 'complaint', 'warehouse', 'daily_km', 'daily_status', 'transfer'.
     *
     * The optional $request is used to read the `brand_id` filter.
     * Only the three bus-related domains use it; passing null keeps
     * the previous behavior (no brand filter).
     */
    protected function scope(string $domain, ?Request $request = null): ReportScope
    {
        $brandId = null;

        if ($request && $request->filled('brand_id')) {
            $brandId = (int) $request->input('brand_id');
        }

        $scope = ReportScope::for(auth()->user(), $domain, $brandId);

        abort_unless($scope->hasAccess(), 403, __('messages.reports.no_scope'));

        return $scope;
    }

    /**
     * Return an Excel download when the request carries ?export=xlsx,
     * or null otherwise. Controllers call this immediately after
     * computing their data and short-circuit when a download object
     * is returned.
     *
     * Contract:
     *   - $basename is a short slug (no extension); a timestamp and
     *     .xlsx extension are appended automatically.
     *   - $headings is an ordered list of translated column labels.
     *   - $rows is an ordered list of row arrays; each row array must
     *     have the same length as $headings.
     *
     * This keeps the export shape 1:1 with what the operator sees on
     * screen, so no separate "export logic" can drift out of sync.
     */
    protected function maybeExport(
        Request $request,
        string $basename,
        array $headings,
        array $rows,
    ): ?BinaryFileResponse {
        if ($request->input('export') !== 'xlsx') {
            return null;
        }

        $filename = $basename.'-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(
            new GenericReportExport($rows, $headings),
            $filename
        );
    }
}
