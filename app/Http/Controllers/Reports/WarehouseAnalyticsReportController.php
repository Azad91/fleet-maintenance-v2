<?php

namespace App\Http\Controllers\Reports;

use App\Services\Reports\WarehouseAnalyticsReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WarehouseAnalyticsReportController extends ReportController
{
    public function __construct(
        protected WarehouseAnalyticsReportService $service
    ) {}

    // #26 — Slow-moving Stock
    public function slowMoving(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('warehouse', $request);

        $days = (int) $request->input('days', 90);
        if ($days < 7 || $days > 365) {
            $days = 90;
        }

        $rows = $this->service->slowMoving($scope, $days);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'warehouse-slow-moving',
            headings: [
                __('messages.warehouse.code'),
                __('messages.warehouse.name'),
                __('messages.warehouse.quantity'),
                __('messages.warehouse.unit'),
                __('messages.reports.content.days_since_use'),
                __('messages.reports.content.tied_capital'),
            ],
            rows: $rows->map(fn ($r) => [
                $r->code,
                $r->name,
                (int) $r->quantity,
                $r->unit ?? '',
                $r->days_since_use ?? '',
                number_format($r->tied_capital, 2, '.', ''),
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('slow-moving', $period, $scope, [
            'rows' => $rows,
            'days' => $days,
        ]);
    }

    // #28 — Inventory Valuation
    public function valuation(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('warehouse', $request);

        $data = $this->service->valuation($scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'warehouse-valuation',
            headings: [
                __('messages.warehouse.category'),
                __('messages.reports.content.items'),
                __('messages.reports.content.total_quantity'),
                __('messages.reports.content.total_value'),
            ],
            rows: $data['by_category']->map(fn ($r) => [
                $r->category_key,
                (int) $r->item_count,
                (int) $r->total_quantity,
                number_format((float) $r->total_value, 2, '.', ''),
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('valuation', $period, $scope, [
            'data' => $data,
        ]);
    }

    // #29 — Reorder Suggestions
    public function reorder(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('warehouse', $request);

        $rows = $this->service->reorderSuggestions($scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'warehouse-reorder',
            headings: [
                __('messages.warehouse.code'),
                __('messages.warehouse.name'),
                __('messages.warehouse.quantity'),
                __('messages.reports.content.min_quantity'),
                __('messages.reports.content.suggested_order'),
                __('messages.reports.content.total_cost'),
                __('messages.warehouse.supplier'),
            ],
            rows: $rows->map(fn ($r) => [
                $r->code,
                $r->name,
                (int) $r->quantity,
                (int) $r->minimum_quantity,
                (int) $r->suggested_order,
                number_format($r->estimated_cost, 2, '.', ''),
                $r->supplier ?? '',
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('reorder', $period, $scope, ['rows' => $rows]);
    }

    // #30 — Supplier Performance
    public function supplier(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('warehouse', $request);

        $rows = $this->service->supplierPerformance($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'warehouse-supplier-performance',
            headings: [
                __('messages.warehouse.supplier'),
                __('messages.reports.content.distinct_parts'),
                __('messages.reports.content.cards_opened'),
                __('messages.reports.content.total_used'),
                __('messages.reports.content.total_cost'),
            ],
            rows: $rows->map(fn ($r) => [
                $r->supplier,
                (int) $r->distinct_parts,
                (int) $r->cards_count,
                (int) $r->total_used,
                number_format((float) $r->total_cost, 2, '.', ''),
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('supplier', $period, $scope, ['rows' => $rows]);
    }

    // #31 — Part Movement History
    public function partHistory(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('warehouse', $request);

        $code = $request->filled('code') ? trim((string) $request->input('code')) : null;
        $dqn = $request->filled('dqn') ? trim((string) $request->input('dqn')) : null;

        $rows = $this->service->partMovementHistory($period, $scope, $code, $dqn);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'warehouse-part-history',
            headings: [
                __('messages.reports.content.date'),
                __('messages.buses.dqn'),
                __('messages.buses.route_number'),
                __('messages.complaints.part_code'),
                __('messages.complaints.part_name'),
                __('messages.complaints.used_qty'),
                __('messages.reports.content.price_at_use'),
                __('messages.reports.content.total_cost'),
                __('messages.reports.content.source'),
            ],
            rows: $rows->map(fn ($r) => [
                \Carbon\Carbon::parse($r->created_at)->format('d.m.Y H:i'),
                $r->dqn,
                $r->route_number ?? '',
                $r->code,
                $r->part_name,
                (int) $r->used_quantity,
                (float) ($r->price_at_use ?? 0),
                number_format($r->line_cost, 2, '.', ''),
                __('messages.stock_sources.'.$r->source_type),
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('part-history', $period, $scope, [
            'rows' => $rows,
            'codeFilter' => $code,
            'dqnFilter' => $dqn,
        ]);
    }

    private function render(string $view, $period, $scope, array $data): View
    {
        return view("reports.warehouse-analytics.{$view}", array_merge($data, [
            'domain' => 'warehouse_analytics',
            'activeReport' => $view,
            'period' => $period,
            'scope' => $scope,
            'exportUrl' => request()->fullUrlWithQuery(['export' => 'xlsx']),
        ]));
    }
}
