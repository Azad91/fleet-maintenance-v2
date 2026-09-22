<?php

namespace App\Http\Controllers\Reports;

use App\Services\Reports\ComplaintReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ComplaintReportController extends ReportController
{
    public function __construct(
        protected ComplaintReportService $service
    ) {}

    public function summary(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope  = $this->scope('complaint', $request);

        $summary = $this->service->summary($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'complaint-summary',
            headings: [
                __('messages.reports.content.opened'),
                __('messages.reports.content.closed'),
                __('messages.reports.content.open_now'),
            ],
            rows: [[
                (int) $summary['opened'],
                (int) $summary['closed'],
                (int) $summary['open_now'],
            ]],
        )) {
            return $export;
        }

        return $this->render('summary', $period, $scope, [
            'summary' => $summary,
        ]);
    }

    public function topTypes(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope  = $this->scope('complaint', $request);

        $items = $this->service->topTypes($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'complaint-top-types',
            headings: [
                __('messages.complaints.complaint_type'),
                __('messages.reports.content.total'),
            ],
            rows: $items->map(function ($item) {
                $typeValue = $item->complaint_type instanceof \App\Enums\ComplaintType
                    ? $item->complaint_type->value
                    : $item->complaint_type;

                return [
                    $typeValue
                        ? __('enums.complaint_type.'.$typeValue)
                        : __('messages.reports.content.unknown_type'),
                    (int) $item->total,
                ];
            })->all(),
        )) {
            return $export;
        }

        return $this->render('top-types', $period, $scope, [
            'items' => $items,
        ]);
    }

    public function workerActivity(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope  = $this->scope('complaint', $request);

        $rows = $this->service->workerActivity($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'complaint-worker-activity',
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

    public function byBus(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope  = $this->scope('complaint', $request);

        $items = $this->service->byBus($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'complaint-by-bus',
            headings: [
                __('messages.buses.dqn'),
                __('messages.buses.route_number'),
                __('messages.reports.content.total'),
                __('messages.reports.content.completed'),
                __('messages.reports.content.open_now'),
            ],
            rows: $items->map(fn ($item) => [
                $item->dqn ?? '',
                $item->route_number ?? '',
                (int) $item->total,
                (int) $item->completed,
                (int) $item->total - (int) $item->completed,
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('by-bus', $period, $scope, [
            'items' => $items,
        ]);
    }

    public function avgCloseTime(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope  = $this->scope('complaint', $request);

        $data = $this->service->avgCloseTime($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'complaint-avg-close-time',
            headings: [
                __('messages.complaints.complaint_type'),
                __('messages.reports.content.count'),
                __('messages.reports.content.avg'),
                __('messages.reports.content.min'),
                __('messages.reports.content.max'),
            ],
            rows: $data['by_type']->map(fn ($row) => [
                $row->type
                    ? __('enums.complaint_type.'.$row->type)
                    : __('messages.reports.content.unknown_type'),
                (int) $row->count,
                (float) $row->avg_hours,
                (float) $row->min_hours,
                (float) $row->max_hours,
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('avg-close-time', $period, $scope, [
            'data' => $data,
        ]);
    }

    private function render(string $view, $period, $scope, array $data): View
    {
        return view("reports.complaint.{$view}", array_merge($data, [
            'domain' => 'complaint',
            'activeReport' => $view,
            'period' => $period,
            'scope' => $scope,
            'exportUrl' => request()->fullUrlWithQuery(['export' => 'xlsx']),
        ]));
    }
}
