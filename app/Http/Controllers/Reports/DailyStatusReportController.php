<?php

namespace App\Http\Controllers\Reports;

use App\Services\Reports\DailyStatusReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DailyStatusReportController extends ReportController
{
    public function __construct(
        protected DailyStatusReportService $service
    ) {}

    public function distribution(Request $request): View
    {
        $period = $this->period($request);
        $scope  = $this->scope('daily_status');

        return $this->render('distribution', $period, $scope, [
            'data' => $this->service->distribution($period, $scope),
        ]);
    }

    public function changes(Request $request): View
    {
        $period = $this->period($request);
        $scope  = $this->scope('daily_status');

        return $this->render('changes', $period, $scope, [
            'logs' => $this->service->changes($period, $scope),
        ]);
    }

    public function workerActivity(Request $request): View
    {
        $period = $this->period($request);
        $scope  = $this->scope('daily_status');

        return $this->render('worker-activity', $period, $scope, [
            'rows' => $this->service->workerActivity($period, $scope),
        ]);
    }

    private function render(string $view, $period, $scope, array $data): View
    {
        return view("reports.daily-status.{$view}", array_merge($data, [
            'domain'       => 'daily_status',
            'activeReport' => $view,
            'period'       => $period,
            'scope'        => $scope,
        ]));
    }
}
