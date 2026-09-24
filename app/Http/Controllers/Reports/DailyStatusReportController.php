<?php

namespace App\Http\Controllers\Reports;

use App\Services\Reports\DailyStatusReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DailyStatusReportController extends ReportController
{
    public function __construct(
        protected DailyStatusReportService $service
    ) {}

    public function distribution(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('daily_status', $request);

        $data = $this->service->distribution($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'daily-status-distribution',
            headings: [
                __('messages.reports.content.status'),
                __('messages.reports.content.total'),
            ],
            rows: $data['rows']->map(fn ($row) => [
                $row->status ?? '',
                (int) $row->total,
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('distribution', $period, $scope, [
            'data' => $data,
        ]);
    }

    public function changes(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('daily_status', $request);

        $logs = $this->service->changes($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'daily-status-changes',
            headings: [
                __('messages.reports.content.date'),
                __('messages.reports.content.user'),
                __('messages.reports.content.event'),
                __('messages.reports.content.status'),
                __('messages.reports.content.changes'),
            ],
            rows: $logs->map(function ($log) {
                $status = $log->new_values['status']
                    ?? $log->old_values['status']
                    ?? '';

                $changes = ($log->event === 'updated' && $log->new_values)
                    ? implode(', ', array_keys($log->new_values))
                    : '';

                return [
                    $log->created_at?->format('d.m.Y H:i') ?? '',
                    $log->user?->name ?? __('messages.reports.content.unknown_user'),
                    __('messages.reports.content.event_'.$log->event),
                    $status,
                    $changes,
                ];
            })->all(),
        )) {
            return $export;
        }

        return $this->render('changes', $period, $scope, [
            'logs' => $logs,
        ]);
    }

    public function workerActivity(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('daily_status', $request);

        $rows = $this->service->workerActivity($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'daily-status-worker-activity',
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
        return view("reports.daily-status.{$view}", array_merge($data, [
            'domain' => 'daily_status',
            'activeReport' => $view,
            'period' => $period,
            'scope' => $scope,
            'exportUrl' => request()->fullUrlWithQuery(['export' => 'xlsx']),
        ]));
    }
}
