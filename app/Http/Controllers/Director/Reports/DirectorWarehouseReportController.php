<?php

namespace App\Http\Controllers\Director\Reports;

use App\Http\Controllers\Reports\ReportController;
use App\Services\Reports\WarehouseReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only warehouse reports for Company Directors.
 *
 * Reuses the exact same report services and Blade views as the
 * garage-level WarehouseReportController. The only difference is
 * the route name prefix (director.reports.*) and the fact that
 * DirectorReportScope already aggregates across every garage in
 * the director's company.
 */
class DirectorWarehouseReportController extends ReportController
{
    public function __construct(
        protected WarehouseReportService $service
    ) {}

    public function receipt(Request $request): View
    {
        $period = $this->period($request);
        $scope = $this->scope('warehouse');

        return $this->render('receipt', $period, $scope, [
            'items' => $this->service->receipt($period, $scope),
        ]);
    }

    public function usage(Request $request): View
    {
        $period = $this->period($request);
        $scope = $this->scope('warehouse');

        return $this->render('usage', $period, $scope, [
            'items' => $this->service->usage($period, $scope),
        ]);
    }

    public function workerActivity(Request $request): View
    {
        $period = $this->period($request);
        $scope = $this->scope('warehouse');

        return $this->render('worker-activity', $period, $scope, [
            'rows' => $this->service->workerActivity($period, $scope),
        ]);
    }

    public function lowStock(Request $request): View
    {
        $period = $this->period($request);
        $scope = $this->scope('warehouse');

        return $this->render('low-stock', $period, $scope, [
            'items' => $this->service->lowStock($scope),
        ]);
    }

    public function movement(Request $request): View
    {
        $period = $this->period($request);
        $scope = $this->scope('warehouse');

        return $this->render('movement', $period, $scope, [
            'logs' => $this->service->movement($period, $scope),
        ]);
    }

    public function serviceVehicleUsage(Request $request): View
    {
        $period = $this->period($request);
        $scope = $this->scope('warehouse');

        return $this->render('service-vehicle-usage', $period, $scope, [
            'rows' => $this->service->serviceVehicleUsage($period, $scope),
        ]);
    }

    private function render(string $view, $period, $scope, array $data): View
    {
        return view("reports.warehouse.{$view}", array_merge($data, [
            'domain' => 'warehouse',
            'activeReport' => $view,
            'period' => $period,
            'scope' => $scope,
        ]));
    }
}
