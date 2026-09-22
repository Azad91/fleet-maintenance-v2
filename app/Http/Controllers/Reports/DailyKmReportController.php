<?php

namespace App\Http\Controllers\Reports;

use App\Services\Reports\DailyKmReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DailyKmReportController extends ReportController
{
    public function __construct(
        protected DailyKmReportService $service
    ) {}

    public function missing(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope  = $this->scope('daily_km', $request);

        $buses = $this->service->missing($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'daily-km-missing',
            headings: [
                __('messages.buses.dqn'),
                __('messages.buses.route_number'),
                __('messages.buses.bus_project'),
                __('messages.buses.col_latest_km'),
            ],
            rows: $buses->map(fn ($bus) => [
                $bus->dqn ?? '',
                $bus->route_number ?? '',
                $bus->bus_project ?? '',
                $bus->km !== null ? (int) $bus->km : '',
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('missing', $period, $scope, [
            'buses' => $buses,
        ]);
    }

    public function topBuses(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope  = $this->scope('daily_km', $request);

        $buses = $this->service->topBuses($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'daily-km-top-buses',
            headings: [
                __('messages.buses.dqn'),
                __('messages.buses.route_number'),
                __('messages.reports.content.start_km'),
                __('messages.reports.content.end_km'),
                __('messages.reports.content.distance'),
                __('messages.reports.content.entries'),
            ],
            rows: $buses->map(fn ($bus) => [
                $bus->dqn ?? '',
                $bus->route_number ?? '',
                (int) ($bus->start_km ?? 0),
                (int) ($bus->end_km ?? 0),
                (int) ($bus->distance ?? 0),
                (int) ($bus->entries_count ?? 0),
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('top-buses', $period, $scope, [
            'buses' => $buses,
        ]);
    }

    public function workerActivity(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope  = $this->scope('daily_km', $request);

        $rows = $this->service->workerActivity($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'daily-km-worker-activity',
            headings: [
                __('messages.reports.content.user'),
                __('messages.reports.content.created'),
                __('messages.reports.content.updated'),
                __('messages.reports.content.deleted'),
                __('messages.reports.content.total'),
            ],
            rows: $rows->map(fn ($row) => [
                $row->user?->name ?? __('messages.reports.content.unknown_user'),
                (int) $row->created_count,
                (int) $row->updated_count,
                (int) $row->deleted_count,
                (int) $row->total_actions,
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('worker-activity', $period, $scope, [
            'rows' => $rows,
        ]);
    }

    private function render(string $view, $period, $scope, array $data): View
    {
        return view("reports.daily-km.{$view}", array_merge($data, [
            'domain'       => 'daily_km',
            'activeReport' => $view,
            'period'       => $period,
            'scope'        => $scope,
            'exportUrl'    => request()->fullUrlWithQuery(['export' => 'xlsx']),
        ]));
    }
}
