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
     * Domains: 'complaint', 'warehouse', 'daily_km', 'daily_status'.
     */
    protected function scope(string $domain): ReportScope
    {
        $scope = ReportScope::for(auth()->user(), $domain);

        abort_unless($scope->hasAccess(), 403, __('messages.reports.no_scope'));

        return $scope;
    }
}
