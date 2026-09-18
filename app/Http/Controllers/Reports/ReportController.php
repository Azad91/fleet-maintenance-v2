<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\ReportPeriod;
use App\Services\Reports\ReportScope;
use Illuminate\Http\Request;

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
}
