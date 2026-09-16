<?php

namespace App\Http\Controllers\Reports;

use App\Services\Reports\TransferReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransferReportController extends ReportController
{
    public function __construct(
        protected TransferReportService $service
    ) {}

    public function summary(Request $request): View
    {
        $period = $this->period($request);
        $scope  = $this->scope('transfer');

        return $this->render('summary', $period, $scope, [
            'summary' => $this->service->summary($period, $scope),
        ]);
    }

    public function byRoute(Request $request): View
    {
        $period = $this->period($request);
        $scope  = $this->scope('transfer');

        return $this->render('by-route', $period, $scope, [
            'items' => $this->service->byRoute($period, $scope),
        ]);
    }

    public function topItems(Request $request): View
    {
        $period = $this->period($request);
        $scope  = $this->scope('transfer');

        return $this->render('top-items', $period, $scope, [
            'items' => $this->service->topItems($period, $scope),
        ]);
    }

    public function workerActivity(Request $request): View
    {
        $period = $this->period($request);
        $scope  = $this->scope('transfer');

        return $this->render('worker-activity', $period, $scope, [
            'rows' => $this->service->workerActivity($period, $scope),
        ]);
    }

    public function disputed(Request $request): View
    {
        $period = $this->period($request);
        $scope  = $this->scope('transfer');

        return $this->render('disputed', $period, $scope, [
            'transfers' => $this->service->disputed($period, $scope),
        ]);
    }

    private function render(string $view, $period, $scope, array $data): View
    {
        return view("reports.transfer.{$view}", array_merge($data, [
            'domain'       => 'transfer',
            'activeReport' => $view,
            'period'       => $period,
            'scope'        => $scope,
        ]));
    }
}
