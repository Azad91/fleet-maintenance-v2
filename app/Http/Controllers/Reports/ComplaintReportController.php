<?php

namespace App\Http\Controllers\Reports;

use App\Services\Reports\ComplaintReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComplaintReportController extends ReportController
{
    public function __construct(
        protected ComplaintReportService $service
    ) {}

    public function summary(Request $request): View
    {
        $period = $this->period($request);
        $scope  = $this->scope('complaint');

        return $this->render('summary', $period, $scope, [
            'summary' => $this->service->summary($period, $scope),
        ]);
    }

    public function topTypes(Request $request): View
    {
        $period = $this->period($request);
        $scope  = $this->scope('complaint');

        return $this->render('top-types', $period, $scope, [
            'items' => $this->service->topTypes($period, $scope),
        ]);
    }

    public function workerActivity(Request $request): View
    {
        $period = $this->period($request);
        $scope  = $this->scope('complaint');

        return $this->render('worker-activity', $period, $scope, [
            'rows' => $this->service->workerActivity($period, $scope),
        ]);
    }

    public function byBus(Request $request): View
    {
        $period = $this->period($request);
        $scope  = $this->scope('complaint');

        return $this->render('by-bus', $period, $scope, [
            'items' => $this->service->byBus($period, $scope),
        ]);
    }

    public function avgCloseTime(Request $request): View
    {
        $period = $this->period($request);
        $scope  = $this->scope('complaint');

        return $this->render('avg-close-time', $period, $scope, [
            'data' => $this->service->avgCloseTime($period, $scope),
        ]);
    }

    private function render(string $view, $period, $scope, array $data): View
    {
        return view("reports.complaint.{$view}", array_merge($data, [
            'domain'       => 'complaint',
            'activeReport' => $view,
            'period'       => $period,
            'scope'        => $scope,
        ]));
    }
}
