<?php

namespace App\Http\Controllers\Reports;

use App\Services\Reports\MaintenanceReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceReportController extends ReportController
{
    public function __construct(
        protected MaintenanceReportService $service
    ) {}

    public function summary(Request $request): View
    {
        $period = $this->period($request);
        $scope = $this->scope('maintenance', $request);

        return $this->render('summary', $period, $scope, [
            'summary' => $this->service->summary($period, $scope),
        ]);
    }

    public function perBus(Request $request): View
    {
        $period = $this->period($request);
        $scope = $this->scope('maintenance', $request);

        return $this->render('per-bus', $period, $scope, [
            'rows' => $this->service->perBus($period, $scope),
        ]);
    }

    public function perPart(Request $request): View
    {
        $period = $this->period($request);
        $scope = $this->scope('maintenance', $request);

        return $this->render('per-part', $period, $scope, [
            'items' => $this->service->perPart($period, $scope),
        ]);
    }

    public function motorOil(Request $request): View
    {
        $period = $this->period($request);
        $scope = $this->scope('maintenance', $request);

        return $this->render('motor-oil', $period, $scope, [
            'rows' => $this->service->motorOil($period, $scope),
        ]);
    }

    public function mostRepaired(Request $request): View
    {
        $period = $this->period($request);
        $scope = $this->scope('maintenance', $request);

        return $this->render('most-repaired', $period, $scope, [
            'rows' => $this->service->mostRepaired($period, $scope),
        ]);
    }

    private function render(string $view, $period, $scope, array $data): View
    {
        return view("reports.maintenance.{$view}", array_merge($data, [
            'domain' => 'maintenance',
            'activeReport' => $view,
            'period' => $period,
            'scope' => $scope,
        ]));
    }
}
