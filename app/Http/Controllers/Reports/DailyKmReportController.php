<?php

namespace App\Http\Controllers\Reports;

use App\Services\Reports\DailyKmReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DailyKmReportController extends ReportController
{
    public function __construct(
        protected DailyKmReportService $service
    ) {}

    public function missing(Request $request): View
    {
        $period = $this->period($request);
        $scope  = $this->scope('daily_km');

        return $this->render('missing', $period, $scope, [
            'buses' => $this->service->missing($period, $scope),
        ]);
    }

    public function topBuses(Request $request): View
    {
        $period = $this->period($request);
        $scope  = $this->scope('daily_km');

        return $this->render('top-buses', $period, $scope, [
            'buses' => $this->service->topBuses($period, $scope),
        ]);
    }

    public function workerActivity(Request $request): View
    {
        $period = $this->period($request);
        $scope  = $this->scope('daily_km');

        return $this->render('worker-activity', $period, $scope, [
            'rows' => $this->service->workerActivity($period, $scope),
        ]);
    }

    private function render(string $view, $period, $scope, array $data): View
    {
        return view("reports.daily-km.{$view}", array_merge($data, [
            'domain'       => 'daily_km',
            'activeReport' => $view,
            'period'       => $period,
            'scope'        => $scope,
        ]));
    }
}
