<?php

namespace App\Http\Controllers\Reports;

use App\Services\Reports\MaintenanceReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MaintenanceReportController extends ReportController
{
    public function __construct(
        protected MaintenanceReportService $service
    ) {}

    // ==================================================================
    // SUMMARY
    // ==================================================================

    public function summary(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('maintenance', $request);

        $summary = $this->service->summary($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'maintenance-summary',
            headings: [
                __('messages.reports.content.cards_opened'),
                __('messages.reports.content.cards_closed'),
                __('messages.reports.content.parts_lines'),
                __('messages.reports.content.distinct_parts'),
                __('messages.reports.content.total_quantity'),
                __('messages.reports.content.total_cost'),
                __('messages.reports.content.motor_oil_count'),
                __('messages.reports.content.avg_close_hours'),
            ],
            rows: [[
                (int) $summary['opened'],
                (int) $summary['closed'],
                (int) $summary['parts_lines'],
                (int) $summary['distinct_parts'],
                (int) $summary['total_quantity'],
                (float) $summary['total_cost'],
                (int) $summary['motor_oil'],
                $summary['avg_close_hours'] ?? '',
            ]],
        )) {
            return $export;
        }

        return $this->render('summary', $period, $scope, [
            'summary' => $summary,
        ]);
    }

    // ==================================================================
    // PER BUS
    // ==================================================================

    public function perBus(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('maintenance', $request);

        $rows = $this->service->perBus($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'maintenance-per-bus',
            headings: [
                __('messages.buses.dqn'),
                __('messages.buses.route_number'),
                __('messages.reports.content.cards_opened'),
                __('messages.reports.content.cards_closed'),
                __('messages.reports.content.motor_oil_count'),
                __('messages.reports.content.total_quantity'),
                __('messages.reports.content.total_cost'),
            ],
            rows: $rows->map(fn ($r) => [
                $r->bus?->dqn ?? '',
                $r->bus?->route_number ?? '',
                (int) $r->cards_opened,
                (int) $r->cards_closed,
                (int) $r->motor_oil_count,
                (int) $r->total_qty,
                (float) $r->total_cost,
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('per-bus', $period, $scope, [
            'rows' => $rows,
        ]);
    }

    // ==================================================================
    // PER PART
    // ==================================================================

    public function perPart(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('maintenance', $request);

        $items = $this->service->perPart($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'maintenance-per-part',
            headings: [
                __('messages.complaints.part_code'),
                __('messages.complaints.part_name'),
                __('messages.reports.content.times_used'),
                __('messages.reports.content.total_quantity'),
                __('messages.reports.content.total_cost'),
                __('messages.reports.content.qty_warehouse'),
                __('messages.reports.content.qty_service_vehicle'),
                __('messages.reports.content.qty_historical'),
            ],
            rows: $items->map(fn ($r) => [
                $r->code,
                $r->name,
                (int) $r->times_used,
                (int) $r->total_qty,
                (float) $r->total_cost,
                (int) $r->qty_warehouse,
                (int) $r->qty_service_vehicle,
                (int) $r->qty_historical,
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('per-part', $period, $scope, [
            'items' => $items,
        ]);
    }

    // ==================================================================
    // MOTOR OIL
    // ==================================================================

    public function motorOil(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('maintenance', $request);

        $rows = $this->service->motorOil($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'maintenance-motor-oil',
            headings: [
                __('messages.reports.content.date'),
                __('messages.buses.dqn'),
                __('messages.buses.route_number'),
                __('messages.reports.content.service_km'),
                __('messages.reports.content.total_parts_count'),
                __('messages.reports.content.total_quantity'),
                __('messages.reports.content.total_cost'),
            ],
            rows: $rows->map(fn ($r) => [
                $r->complaint->created_at
                    ? \Carbon\Carbon::parse($r->complaint->created_at)->format('d.m.Y H:i')
                    : '',
                $r->bus?->dqn ?? '',
                $r->bus?->route_number ?? '',
                $r->complaint->service_km !== null
                    ? (int) $r->complaint->service_km
                    : '',
                (int) $r->parts_count,
                (int) $r->total_qty,
                (float) $r->total_cost,
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('motor-oil', $period, $scope, [
            'rows' => $rows,
        ]);
    }

    // ==================================================================
    // MOST REPAIRED
    // ==================================================================

    public function mostRepaired(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('maintenance', $request);

        $rows = $this->service->mostRepaired($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'maintenance-most-repaired',
            headings: [
                __('messages.buses.dqn'),
                __('messages.buses.route_number'),
                __('messages.reports.content.cards_opened'),
                __('messages.reports.content.completed'),
                __('messages.reports.content.total_hours'),
                __('messages.reports.content.total_cost'),
            ],
            rows: $rows->map(fn ($r) => [
                $r->bus?->dqn ?? '',
                $r->bus?->route_number ?? '',
                (int) $r->cards_count,
                (int) $r->completed_count,
                (float) $r->total_hours,
                (float) $r->total_cost,
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('most-repaired', $period, $scope, [
            'rows' => $rows,
        ]);
    }

    // ==================================================================
    // SHARED RENDER HELPER
    // ==================================================================

    /**
     * Render a maintenance-report view with the standard shared data.
     *
     * The `exportUrl` variable is what makes the "Export to Excel"
     * button in the report shell work — it captures the current
     * period + brand filter (if any) plus `?export=xlsx`.
     */
    private function render(string $view, $period, $scope, array $data): View
    {
        return view("reports.maintenance.{$view}", array_merge($data, [
            'domain' => 'maintenance',
            'activeReport' => $view,
            'period' => $period,
            'scope' => $scope,
            'exportUrl' => request()->fullUrlWithQuery(['export' => 'xlsx']),
        ]));
    }
}
