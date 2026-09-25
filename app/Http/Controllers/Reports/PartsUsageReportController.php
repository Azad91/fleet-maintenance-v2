<?php

namespace App\Http\Controllers\Reports;

use App\Services\Reports\OilChangeReportService;
use App\Services\Reports\PartsUsageReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PartsUsageReportController extends ReportController
{
    public function __construct(
        protected PartsUsageReportService $service,
        protected OilChangeReportService $oilService, // for dead stock
    ) {}

    // #16 — Per Bus
    public function perBus(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('warehouse', $request);

        $rows = $this->service->perBus($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'parts-usage-per-bus',
            headings: [
                __('messages.buses.dqn'),
                __('messages.buses.route_number'),
                __('messages.reports.content.cards_opened'),
                __('messages.reports.content.distinct_parts'),
                __('messages.reports.content.total_quantity'),
                __('messages.reports.content.total_cost'),
            ],
            rows: $rows->map(fn ($r) => [
                $r->dqn,
                $r->route_number ?? '',
                (int) $r->cards_count,
                (int) $r->distinct_parts,
                (int) $r->total_quantity,
                (float) $r->total_cost,
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('per-bus', $period, $scope, ['rows' => $rows]);
    }

    // #17 — Top Consumed
    public function topConsumed(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('warehouse', $request);

        $rows = $this->service->topConsumed($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'parts-usage-top-consumed',
            headings: [
                __('messages.complaints.part_code'),
                __('messages.complaints.part_name'),
                __('messages.reports.content.times_used'),
                __('messages.reports.content.total_quantity'),
                __('messages.reports.content.total_cost'),
            ],
            rows: $rows->map(fn ($r) => [
                $r->code,
                $r->name,
                (int) $r->times_used,
                (int) $r->total_quantity,
                (float) $r->total_cost,
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('top-consumed', $period, $scope, ['rows' => $rows]);
    }

    // #18 — Bus Cost
    public function busCost(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('warehouse', $request);

        $rows = $this->service->busCost($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'parts-usage-bus-cost',
            headings: [
                __('messages.buses.dqn'),
                __('messages.buses.route_number'),
                __('messages.reports.content.cards_opened'),
                __('messages.reports.content.total_cost'),
            ],
            rows: $rows->map(fn ($r) => [
                $r->dqn,
                $r->route_number ?? '',
                (int) $r->cards_count,
                (float) $r->total_cost,
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('bus-cost', $period, $scope, ['rows' => $rows]);
    }

    // #19 — Per Complaint
    public function perComplaint(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('warehouse', $request);

        $data = $this->service->perComplaint($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'parts-usage-per-complaint',
            headings: [
                __('messages.reports.content.total'),
            ],
            rows: [
                [$data['total_complaints']],
                [$data['total_parts']],
                [$data['avg_parts_per_complaint']],
            ],
        )) {
            return $export;
        }

        return $this->render('per-complaint', $period, $scope, ['data' => $data]);
    }

    // #27 — Dead Stock
    public function deadStock(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('warehouse', $request);

        $items = $this->oilService->deadStock($scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'warehouse-dead-stock',
            headings: [
                __('messages.warehouse.code'),
                __('messages.warehouse.name'),
                __('messages.warehouse.quantity'),
                __('messages.warehouse.unit'),
                __('messages.warehouse.price'),
                __('messages.reports.content.total_cost'),
            ],
            rows: $items->map(fn ($r) => [
                $r->code,
                $r->name,
                (int) $r->quantity,
                $r->unit ?? '',
                (float) ($r->price ?? 0),
                (float) $r->tied_capital,
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('dead-stock', $period, $scope, ['items' => $items]);
    }

    private function render(string $view, $period, $scope, array $data): View
    {
        return view("reports.parts-usage.{$view}", array_merge($data, [
            'domain' => 'parts_usage',
            'activeReport' => $view,
            'period' => $period,
            'scope' => $scope,
            'exportUrl' => request()->fullUrlWithQuery(['export' => 'xlsx']),
        ]));
    }
}
