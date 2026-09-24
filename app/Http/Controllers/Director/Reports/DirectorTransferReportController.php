<?php

namespace App\Http\Controllers\Director\Reports;

use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Http\Controllers\Reports\ReportController;
use App\Services\Reports\TransferReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Read-only transfer reports for Company Directors.
 *
 * Reuses the exact same report service and Blade views as the
 * garage-level TransferReportController. The only differences are:
 *
 *   - Route prefix: `director.reports.transfer.*`
 *   - Scope: ReportScope::for($director, 'transfer') returns every
 *     garage in the director's company, so the same service methods
 *     produce company-wide aggregates without any code change here.
 *   - Shell detection: the layout reads the route prefix and adjusts
 *     the tab route names, so the same Blade views render correctly
 *     for both audiences.
 *
 * EXPORT SUPPORT
 * --------------
 * Every method accepts `?export=xlsx` on the query string. When set,
 * the same rows that would be rendered in the HTML table are written
 * to an .xlsx file via GenericReportExport. This matches the pattern
 * already used by DirectorWarehouseReportController and
 * DirectorComplaintReportController.
 */
class DirectorTransferReportController extends ReportController
{
    public function __construct(
        protected TransferReportService $service
    ) {}

    // ==================================================================
    // 1. SUMMARY — headline counters + status / type breakdown
    // ==================================================================

    public function summary(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('transfer');

        $summary = $this->service->summary($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'director-transfer-summary',
            headings: [
                __('messages.reports.content.total'),
                __('messages.transfers.report.outbound'),
                __('messages.transfers.report.inbound'),
                __('messages.transfers.report.items_moved'),
                __('messages.transfers.report.received'),
                __('messages.transfers.report.disputed'),
                __('messages.transfers.report.dispute_rate'),
            ],
            rows: [[
                (int) $summary['total'],
                (int) $summary['outbound'],
                (int) $summary['inbound'],
                (int) $summary['items_moved'],
                (int) $summary['received'],
                (int) $summary['disputed'],
                $summary['dispute_rate'].'%',
            ]],
        )) {
            return $export;
        }

        return $this->render('summary', $period, $scope, [
            'summary' => $summary,
        ]);
    }

    // ==================================================================
    // 2. DETAILED — one row per transfer line item
    // ==================================================================

    public function detailed(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('transfer');

        $rows = $this->service->detailed($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'director-transfer-detailed',
            headings: [
                __('messages.transfers.report.summary'),
                __('messages.reports.content.date'),
                __('messages.transfers.from_garage'),
                __('messages.transfers.to_garage'),
                __('messages.transfers.type'),
                __('messages.complaints.part_code'),
                __('messages.complaints.part_name'),
                __('messages.transfers.declared_qty'),
                __('messages.transfers.received_qty'),
                __('messages.transfers.status'),
            ],
            rows: $rows->map(function ($row) {
                // Destination label — garage name OR service vehicle.
                $to = $row->to_garage_name
                    ? $row->to_garage_name.($row->to_garage_code ? ' ('.$row->to_garage_code.')' : '')
                    : ($row->to_vehicle_name ? '🚐 '.$row->to_vehicle_name : '—');

                // Human-readable type / status labels (falls back to
                // the raw enum value if the enum case was removed).
                try {
                    $typeLabel = TransferType::from($row->type)->label();
                } catch (\Throwable $e) {
                    $typeLabel = $row->type;
                }

                try {
                    $statusLabel = TransferStatus::from($row->status)->label();
                } catch (\Throwable $e) {
                    $statusLabel = $row->status;
                }

                return [
                    '#'.$row->transfer_id,
                    \Carbon\Carbon::parse($row->created_at)->format('d.m.Y H:i'),
                    $row->from_garage_name.($row->from_garage_code ? ' ('.$row->from_garage_code.')' : ''),
                    $to,
                    $typeLabel,
                    $row->code,
                    $row->part_name,
                    (int) $row->declared_quantity,
                    $row->received_quantity !== null ? (int) $row->received_quantity : '',
                    $statusLabel,
                ];
            })->all(),
        )) {
            return $export;
        }

        return $this->render('detailed', $period, $scope, [
            'rows' => $rows,
        ]);
    }

    // ==================================================================
    // 3. BY ROUTE — aggregated counts per (from → to) garage pair
    // ==================================================================

    public function byRoute(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('transfer');

        $items = $this->service->byRoute($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'director-transfer-by-route',
            headings: [
                __('messages.transfers.from_garage'),
                __('messages.transfers.to_garage'),
                __('messages.transfers.report.total_transfers'),
                __('messages.transfers.report.received'),
                __('messages.transfers.report.disputed'),
            ],
            rows: $items->map(fn ($item) => [
                $item->from_garage_name.($item->from_garage_code ? ' ('.$item->from_garage_code.')' : ''),
                $item->to_garage_name
                    ? $item->to_garage_name.($item->to_garage_code ? ' ('.$item->to_garage_code.')' : '')
                    : __('messages.transfers.to_service_vehicle'),
                (int) $item->total_transfers,
                (int) $item->received_count,
                (int) $item->disputed_count,
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('by-route', $period, $scope, [
            'items' => $items,
        ]);
    }

    // ==================================================================
    // 4. TOP ITEMS — most frequently transferred parts
    // ==================================================================

    public function topItems(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('transfer');

        $items = $this->service->topItems($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'director-transfer-top-items',
            headings: [
                __('messages.warehouse.code'),
                __('messages.warehouse.name'),
                __('messages.warehouse.unit'),
                __('messages.transfers.report.times_transferred'),
                __('messages.transfers.report.total_declared'),
                __('messages.transfers.report.total_received'),
            ],
            rows: $items->map(fn ($item) => [
                $item->code,
                $item->name,
                $item->unit ?? '—',
                (int) $item->times_transferred,
                (int) $item->total_declared,
                (int) $item->total_received,
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('top-items', $period, $scope, [
            'items' => $items,
        ]);
    }

    // ==================================================================
    // 5. WORKER ACTIVITY — per-user action counts (audit-log based)
    // ==================================================================

    public function workerActivity(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('transfer');

        $rows = $this->service->workerActivity($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'director-transfer-worker-activity',
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

    // ==================================================================
    // 6. DISPUTED — every transfer that ended in a discrepancy
    // ==================================================================

    public function disputed(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('transfer');

        $transfers = $this->service->disputed($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'director-transfer-disputed',
            headings: [
                __('messages.transfers.details'),
                __('messages.transfers.from_garage'),
                __('messages.transfers.to_garage'),
                __('messages.transfers.declared_total'),
                __('messages.transfers.received_total'),
                __('messages.transfers.discrepancy'),
                __('messages.transfers.status'),
            ],
            rows: $transfers->map(function ($transfer) {
                $diff = ($transfer->received_total ?? 0) - $transfer->declared_total;

                return [
                    '#'.$transfer->id,
                    $transfer->fromGarage?->name ?? '—',
                    $transfer->toGarage?->name
                        ?? ($transfer->toServiceVehicle ? '🚐 '.$transfer->toServiceVehicle->name : '—'),
                    (int) $transfer->declared_total,
                    $transfer->received_total !== null ? (int) $transfer->received_total : '',
                    ($diff > 0 ? '+' : '').$diff,
                    $transfer->status->label(),
                ];
            })->all(),
        )) {
            return $export;
        }

        return $this->render('disputed', $period, $scope, [
            'transfers' => $transfers,
        ]);
    }

    // ==================================================================
    // HELPERS
    // ==================================================================

    /**
     * Render a transfer-report view with the standard shared data.
     *
     * The `exportUrl` variable is what makes the "Export to Excel"
     * button in the report shell work — it captures the current
     * period + brand filter (if any) plus `?export=xlsx`.
     */
    private function render(string $view, $period, $scope, array $data): View
    {
        return view("reports.transfer.{$view}", array_merge($data, [
            'domain' => 'transfer',
            'activeReport' => $view,
            'period' => $period,
            'scope' => $scope,
            'exportUrl' => request()->fullUrlWithQuery(['export' => 'xlsx']),
        ]));
    }
}
