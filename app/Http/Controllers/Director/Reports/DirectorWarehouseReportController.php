<?php

namespace App\Http\Controllers\Director\Reports;

use App\Http\Controllers\Reports\ReportController;
use App\Services\Reports\WarehouseReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Read-only warehouse reports for Company Directors.
 *
 * Reuses the exact same report services and Blade views as the
 * garage-level WarehouseReportController. The only differences are
 * the route name prefix (director.reports.*) and the fact that
 * DirectorReportScope already aggregates across every garage in
 * the director's company.
 */
class DirectorWarehouseReportController extends ReportController
{
    public function __construct(
        protected WarehouseReportService $service
    ) {}

    public function receipt(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope  = $this->scope('warehouse');

        $items = $this->service->receipt($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'director-warehouse-receipt',
            headings: [
                __('messages.reports.content.date'),
                __('messages.warehouse.code'),
                __('messages.warehouse.name'),
                __('messages.warehouse.quantity'),
                __('messages.warehouse.unit'),
                __('messages.warehouse.price'),
                __('messages.reports.content.user'),
            ],
            rows: $items->map(fn ($item) => [
                $item->created_at?->format('d.m.Y H:i') ?? '',
                $item->code ?? '',
                $item->name ?? '',
                (int) ($item->quantity ?? 0),
                $item->unit ?? '',
                $item->price !== null ? (float) $item->price : '',
                $item->creator?->name ?? '',
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('receipt', $period, $scope, [
            'items' => $items,
        ]);
    }

    public function usage(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope  = $this->scope('warehouse');

        $items = $this->service->usage($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'director-warehouse-usage',
            headings: [
                __('messages.complaints.part_code'),
                __('messages.complaints.part_name'),
                __('messages.reports.content.times_used'),
                __('messages.reports.content.total_used'),
            ],
            rows: $items->map(fn ($item) => [
                $item->code ?? '',
                $item->name ?? '',
                (int) ($item->times_used ?? 0),
                (int) ($item->total_used ?? 0),
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('usage', $period, $scope, [
            'items' => $items,
        ]);
    }

    public function workerActivity(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope  = $this->scope('warehouse');

        $rows = $this->service->workerActivity($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'director-warehouse-worker-activity',
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

    public function lowStock(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope  = $this->scope('warehouse');

        $items = $this->service->lowStock($scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'director-warehouse-low-stock',
            headings: [
                __('messages.warehouse.code'),
                __('messages.warehouse.name'),
                __('messages.warehouse.quantity'),
                __('messages.reports.content.min_quantity'),
                __('messages.reports.content.deficit'),
                __('messages.warehouse.unit'),
            ],
            rows: $items->map(fn ($item) => [
                $item->code ?? '',
                $item->name ?? '',
                (int) ($item->quantity ?? 0),
                (int) ($item->minimum_quantity ?? 0),
                max(0, (int) ($item->minimum_quantity ?? 0) - (int) ($item->quantity ?? 0)),
                $item->unit ?? '',
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('low-stock', $period, $scope, [
            'items' => $items,
        ]);
    }

    public function movement(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope  = $this->scope('warehouse');

        $logs = $this->service->movement($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'director-warehouse-movement',
            headings: [
                __('messages.reports.content.date'),
                __('messages.reports.content.user'),
                __('messages.reports.content.event'),
                __('messages.warehouse.name'),
                __('messages.reports.content.changes'),
            ],
            rows: $logs->map(fn ($log) => [
                $log->created_at?->format('d.m.Y H:i') ?? '',
                $log->user?->name ?? __('messages.reports.content.unknown_user'),
                __('messages.reports.content.event_'.$log->event),
                $log->new_values['name'] ?? $log->old_values['name'] ?? '',
                $log->new_values
                    ? implode(', ', array_keys($log->new_values))
                    : '',
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('movement', $period, $scope, [
            'logs' => $logs,
        ]);
    }

    public function serviceVehicleUsage(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope  = $this->scope('warehouse');

        $rows = $this->service->serviceVehicleUsage($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'director-warehouse-service-vehicle-usage',
            headings: [
                __('messages.service_vehicles.name'),
                __('messages.service_vehicles.plate_number'),
                __('messages.complaints.part_code'),
                __('messages.complaints.part_name'),
                __('messages.reports.content.times_used'),
                __('messages.reports.content.total_used'),
            ],
            rows: $rows->map(fn ($row) => [
                $row->service_vehicle_name ?? '',
                $row->service_vehicle_plate ?? '',
                $row->code ?? '',
                $row->part_name ?? '',
                (int) ($row->times_used ?? 0),
                (int) ($row->total_used ?? 0),
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('service-vehicle-usage', $period, $scope, [
            'rows' => $rows,
        ]);
    }

    private function render(string $view, $period, $scope, array $data): View
    {
        return view("reports.warehouse.{$view}", array_merge($data, [
            'domain' => 'warehouse',
            'activeReport' => $view,
            'period' => $period,
            'scope' => $scope,
            'exportUrl' => request()->fullUrlWithQuery(['export' => 'xlsx']),
        ]));
    }
}
