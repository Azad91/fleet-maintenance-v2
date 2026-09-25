<?php

namespace App\Http\Controllers\Reports;

use App\Services\Reports\FleetHealthReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FleetHealthReportController extends ReportController
{
    public function __construct(
        protected FleetHealthReportService $service
    ) {}

    // #20 — Cost per KM
    public function costPerKm(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('daily_km', $request);

        $rows = $this->service->costPerKm($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'fleet-health-cost-per-km',
            headings: [
                __('messages.buses.dqn'),
                __('messages.buses.route_number'),
                __('messages.reports.content.cards_opened'),
                __('messages.reports.content.distance'),
                __('messages.reports.content.total_cost'),
                __('messages.reports.content.cost_per_km'),
            ],
            rows: $rows->map(fn ($r) => [
                $r->dqn,
                $r->route_number ?? '',
                (int) $r->cards_count,
                (int) $r->distance,
                (float) $r->total_cost,
                (float) $r->cost_per_km,
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('cost-per-km', $period, $scope, ['rows' => $rows]);
    }

    // #21 — Downtime
    public function downtime(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('complaint', $request);

        $rows = $this->service->downtime($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'fleet-health-downtime',
            headings: [
                __('messages.buses.dqn'),
                __('messages.buses.route_number'),
                __('messages.reports.content.cards_opened'),
                __('messages.reports.content.total_hours'),
                __('messages.reports.content.avg_hours'),
                __('messages.reports.content.max_hours'),
            ],
            rows: $rows->map(fn ($r) => [
                $r->dqn,
                $r->route_number ?? '',
                (int) $r->complaint_count,
                round((float) $r->total_hours, 1),
                round((float) $r->avg_hours, 1),
                round((float) $r->max_hours, 1),
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('downtime', $period, $scope, ['rows' => $rows]);
    }

    // #22 — Recurring Issues
    public function recurringIssues(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('complaint', $request);

        $rows = $this->service->recurringIssues($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'fleet-health-recurring-issues',
            headings: [
                __('messages.complaints.complaint'),
                __('messages.buses.dqn'),
                __('messages.reports.content.total'),
                __('messages.reports.content.last_occurrence'),
            ],
            rows: $rows->map(fn ($r) => [
                $r->description,
                $r->bus_id,
                (int) $r->total,
                \Carbon\Carbon::parse($r->last_occurrence)->format('d.m.Y'),
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('recurring-issues', $period, $scope, ['rows' => $rows]);
    }

    // #23 — Recurring Complaints on Same Bus
    public function recurringComplaints(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('complaint', $request);

        $rows = $this->service->recurringComplaintsOnSameBus($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'fleet-health-recurring-complaints',
            headings: [
                __('messages.buses.dqn'),
                __('messages.buses.route_number'),
                __('messages.complaints.complaint'),
                __('messages.reports.content.total'),
                __('messages.reports.content.last_occurrence'),
            ],
            rows: $rows->map(fn ($r) => [
                $r->dqn,
                $r->route_number ?? '',
                $r->description,
                (int) $r->occurrences,
                \Carbon\Carbon::parse($r->last_occurrence)->format('d.m.Y'),
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('recurring-complaints', $period, $scope, ['rows' => $rows]);
    }

    // #24 — Accidents
    public function accidents(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('complaint', $request);

        $rows = $this->service->accidents($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'fleet-health-accidents',
            headings: [
                __('messages.reports.content.date'),
                __('messages.buses.dqn'),
                __('messages.buses.route_number'),
                __('messages.complaints.driver'),
                __('messages.complaints.location'),
                __('messages.common.status'),
            ],
            rows: $rows->map(fn ($r) => [
                $r->created_at?->format('d.m.Y') ?? '',
                $r->bus?->dqn ?? '',
                $r->bus?->route_number ?? '',
                $r->driver?->full_name ?? $r->driver_name ?? '',
                $r->yer?->label() ?? '',
                $r->status?->label() ?? '',
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('accidents', $period, $scope, ['rows' => $rows]);
    }

    // #25 — Utilization
    public function utilization(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('daily_km', $request);

        $rows = $this->service->utilization($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'fleet-health-utilization',
            headings: [
                __('messages.buses.dqn'),
                __('messages.buses.route_number'),
                __('messages.reports.content.days_in_service'),
                __('messages.reports.content.total_days'),
                __('messages.reports.content.utilization'),
                __('messages.reports.content.distance'),
            ],
            rows: $rows->map(fn ($r) => [
                $r->dqn,
                $r->route_number ?? '',
                (int) $r->days_in_service,
                (int) $r->total_days_in_period,
                $r->utilization_percent.'%',
                (int) $r->total_distance,
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('utilization', $period, $scope, ['rows' => $rows]);
    }

    private function render(string $view, $period, $scope, array $data): View
    {
        return view("reports.fleet-health.{$view}", array_merge($data, [
            'domain' => 'fleet_health',
            'activeReport' => $view,
            'period' => $period,
            'scope' => $scope,
            'exportUrl' => request()->fullUrlWithQuery(['export' => 'xlsx']),
        ]));
    }
}
