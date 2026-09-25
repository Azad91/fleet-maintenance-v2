<?php

namespace App\Http\Controllers\Reports;

use App\Enums\OilType;
use App\Services\Reports\OilChangeReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OilChangeReportController extends ReportController
{
    public function __construct(
        protected OilChangeReportService $service
    ) {}

    // #11 — History
    public function history(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('daily_km', $request); // daily_km uses bus scope

        $type = OilType::tryFrom((string) $request->input('oil_type', ''));

        $rows = $this->service->history($period, $scope, $type);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'oil-change-history',
            headings: [
                __('messages.buses.dqn'),
                __('messages.oil_change.type'),
                __('messages.oil_change.brand'),
                __('messages.oil_change.scheduled_km'),
                __('messages.oil_change.actual_km'),
                __('messages.oil_change.changed_at'),
            ],
            rows: $rows->map(fn ($r) => [
                $r->bus?->dqn ?? '',
                $r->oil_type->label(),
                $r->oil_brand ?? '',
                $r->scheduled_km ?? '',
                $r->actual_km,
                $r->changed_at?->format('d.m.Y') ?? $r->created_at?->format('d.m.Y') ?? '',
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('history', $period, $scope, [
            'rows' => $rows,
            'oilType' => $type,
        ]);
    }

    // #12 — Upcoming
    public function upcoming(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('daily_km', $request);

        $rows = $this->service->upcoming($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'oil-change-upcoming',
            headings: [
                __('messages.buses.dqn'),
                __('messages.oil_change.type'),
                __('messages.oil_change.current_km'),
                __('messages.oil_change.next_due_km'),
                __('messages.oil_change.remaining_km'),
                __('messages.reports.content.days_remaining'),
            ],
            rows: $rows->map(fn ($r) => [
                $r['bus']->dqn,
                $r['type']->label(),
                $r['status']->currentKm,
                $r['status']->nextDueKm ?? '',
                $r['status']->remainingKm ?? '',
                $r['days_remaining'],
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('upcoming', $period, $scope, [
            'rows' => $rows,
        ]);
    }

    // #13 — Counts
    public function counts(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('daily_km', $request);

        $bucket = $request->input('bucket', 'month');
        if (! in_array($bucket, ['month', 'quarter'], true)) {
            $bucket = 'month';
        }

        $rows = $this->service->countsByPeriod($period, $scope, $bucket);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'oil-change-counts',
            headings: [
                __('messages.reports.period.label'),
                __('messages.oil_change.type'),
                __('messages.reports.content.total'),
            ],
            rows: $rows->map(fn ($r) => [
                \Carbon\Carbon::parse($r->period_start)->format('Y-m'),
                OilType::from($r->oil_type)->label(),
                (int) $r->total,
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('counts', $period, $scope, [
            'rows' => $rows,
            'bucket' => $bucket,
        ]);
    }

    // #14 — Adherence
    public function adherence(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('daily_km', $request);

        $data = $this->service->scheduleAdherence($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'oil-change-adherence',
            headings: [
                __('messages.reports.content.status'),
                __('messages.reports.content.total'),
            ],
            rows: [
                [__('messages.oil_change.status.early'), $data['early']],
                [__('messages.oil_change.status.on_time'), $data['on_time']],
                [__('messages.oil_change.status.late'), $data['late']],
            ],
        )) {
            return $export;
        }

        return $this->render('adherence', $period, $scope, [
            'data' => $data,
        ]);
    }

    // #15 — Catalog Usage
    public function catalogUsage(Request $request): View|BinaryFileResponse
    {
        $period = $this->period($request);
        $scope = $this->scope('daily_km', $request);

        $rows = $this->service->catalogUsage($period, $scope);

        if ($export = $this->maybeExport(
            request: $request,
            basename: 'oil-change-catalog-usage',
            headings: [
                __('messages.complaints.part_code'),
                __('messages.complaints.part_name'),
                __('messages.oil_change.interval_km'),
                __('messages.reports.content.times_used'),
                __('messages.reports.content.total_used'),
                __('messages.reports.content.total_cost'),
            ],
            rows: $rows->map(fn ($r) => [
                $r->code,
                $r->part_name,
                $r->catalog_km,
                (int) $r->times_used,
                (int) $r->total_used,
                (float) $r->total_cost,
            ])->all(),
        )) {
            return $export;
        }

        return $this->render('catalog-usage', $period, $scope, [
            'rows' => $rows,
        ]);
    }

    private function render(string $view, $period, $scope, array $data): View
    {
        return view("reports.oil-change.{$view}", array_merge($data, [
            'domain' => 'oil_change',
            'activeReport' => $view,
            'period' => $period,
            'scope' => $scope,
            'exportUrl' => request()->fullUrlWithQuery(['export' => 'xlsx']),
        ]));
    }
}
