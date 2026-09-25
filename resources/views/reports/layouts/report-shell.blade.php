@extends('layouts.app')

@php
    use App\Enums\RoleEnum;

    $shellUser = auth()->user();
    $shellIsDirector = $shellUser?->isDirector() ?? false;

    $shellRoutePrefix = $shellIsDirector ? 'director.' : '';

    $domainConfig = [
        'warehouse' => [
            'title'   => __('messages.reports.warehouse.title'),
            'eyebrow' => __('messages.nav.warehouses'),
            'icon'    => 'fa-boxes-stacked',
            'reports' => [
                'receipt'               => ['label' => __('messages.reports.warehouse.receipt'),               'route' => 'reports.warehouse.receipt',               'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
                'usage'                 => ['label' => __('messages.reports.warehouse.usage'),                 'route' => 'reports.warehouse.usage',                 'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
                'worker-activity'       => ['label' => __('messages.reports.warehouse.worker_activity'),       'route' => 'reports.warehouse.worker-activity',       'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
                'low-stock'             => ['label' => __('messages.reports.warehouse.low_stock'),             'route' => 'reports.warehouse.low-stock',             'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value, RoleEnum::WAREHOUSE_WORKER->value]],
                'movement'              => ['label' => __('messages.reports.warehouse.movement'),              'route' => 'reports.warehouse.movement',              'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
                'service-vehicle-usage' => ['label' => __('messages.reports.warehouse.service_vehicle_usage'), 'route' => 'reports.warehouse.service-vehicle-usage', 'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
            ],
        ],
        'complaint' => [
            'title'   => __('messages.reports.complaint.title'),
            'eyebrow' => __('messages.nav.complaints'),
            'icon'    => 'fa-screwdriver-wrench',
            'reports' => [
                'summary'         => ['label' => __('messages.reports.complaint.summary'),         'route' => 'reports.complaint.summary',         'roles' => [RoleEnum::ADMIN->value, RoleEnum::COMPLAINT_MANAGER->value]],
                'top-types'       => ['label' => __('messages.reports.complaint.top_types'),       'route' => 'reports.complaint.top-types',       'roles' => [RoleEnum::ADMIN->value, RoleEnum::COMPLAINT_MANAGER->value]],
                'worker-activity' => ['label' => __('messages.reports.complaint.worker_activity'), 'route' => 'reports.complaint.worker-activity', 'roles' => [RoleEnum::ADMIN->value, RoleEnum::COMPLAINT_MANAGER->value, RoleEnum::COMPLAINT_WORKER->value]],
                'by-bus'          => ['label' => __('messages.reports.complaint.by_bus'),          'route' => 'reports.complaint.by-bus',          'roles' => [RoleEnum::ADMIN->value, RoleEnum::COMPLAINT_MANAGER->value]],
                'avg-close-time'  => ['label' => __('messages.reports.complaint.avg_close_time'),  'route' => 'reports.complaint.avg-close-time',  'roles' => [RoleEnum::ADMIN->value, RoleEnum::COMPLAINT_MANAGER->value]],
            ],
        ],
        'daily_km' => [
            'title'   => __('messages.reports.daily_km.title'),
            'eyebrow' => __('messages.nav.daily_km'),
            'icon'    => 'fa-gauge-high',
            'reports' => [
                'missing'         => ['label' => __('messages.reports.daily_km.missing'),         'route' => 'reports.daily-km.missing',         'roles' => [RoleEnum::ADMIN->value, RoleEnum::DAILY_KM_MANAGER->value]],
                'top-buses'       => ['label' => __('messages.reports.daily_km.top_buses'),       'route' => 'reports.daily-km.top-buses',       'roles' => [RoleEnum::ADMIN->value, RoleEnum::DAILY_KM_MANAGER->value]],
                'worker-activity' => ['label' => __('messages.reports.daily_km.worker_activity'), 'route' => 'reports.daily-km.worker-activity', 'roles' => [RoleEnum::ADMIN->value, RoleEnum::DAILY_KM_MANAGER->value, RoleEnum::DAILY_KM_WORKER->value]],
            ],
        ],
        'daily_status' => [
            'title'   => __('messages.reports.daily_status.title'),
            'eyebrow' => __('messages.nav.daily_statuses'),
            'icon'    => 'fa-clipboard-check',
            'reports' => [
                'distribution'    => ['label' => __('messages.reports.daily_status.distribution'),    'route' => 'reports.daily-status.distribution',    'roles' => [RoleEnum::ADMIN->value, RoleEnum::DAILY_STATUS_MANAGER->value]],
                'changes'         => ['label' => __('messages.reports.daily_status.changes'),         'route' => 'reports.daily-status.changes',         'roles' => [RoleEnum::ADMIN->value, RoleEnum::DAILY_STATUS_MANAGER->value]],
                'worker-activity' => ['label' => __('messages.reports.daily_status.worker_activity'), 'route' => 'reports.daily-status.worker-activity', 'roles' => [RoleEnum::ADMIN->value, RoleEnum::DAILY_STATUS_MANAGER->value, RoleEnum::DAILY_STATUS_WORKER->value]],
            ],
        ],
        'transfer' => [
            'title'   => __('messages.transfers.report.title'),
            'eyebrow' => __('messages.nav.warehouses'),
            'icon'    => 'fa-arrow-right-arrow-left',
            'reports' => [
                'summary'         => ['label' => __('messages.transfers.report.summary'),         'route' => 'reports.transfer.summary',         'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
                'detailed'        => ['label' => __('messages.transfers.report.detailed'),        'route' => 'reports.transfer.detailed',        'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
                'by-route'        => ['label' => __('messages.transfers.report.by_route'),        'route' => 'reports.transfer.by-route',        'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
                'top-items'       => ['label' => __('messages.transfers.report.top_items'),       'route' => 'reports.transfer.top-items',       'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
                'worker-activity' => ['label' => __('messages.transfers.report.worker_activity'), 'route' => 'reports.transfer.worker-activity', 'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value, RoleEnum::WAREHOUSE_WORKER->value]],
                'disputed'        => ['label' => __('messages.transfers.report.disputed_tab'),    'route' => 'reports.transfer.disputed',        'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
            ],
        ],
        'maintenance' => [
            'title'   => __('messages.reports.maintenance.title'),
            'eyebrow' => __('messages.nav.complaints'),
            'icon'    => 'fa-wrench',
            'reports' => [
                'summary'       => ['label' => __('messages.reports.maintenance.summary'),       'route' => 'reports.maintenance.summary',       'roles' => [RoleEnum::ADMIN->value, RoleEnum::COMPLAINT_MANAGER->value, RoleEnum::COMPLAINT_WORKER->value]],
                'per-bus'       => ['label' => __('messages.reports.maintenance.per_bus'),       'route' => 'reports.maintenance.per-bus',       'roles' => [RoleEnum::ADMIN->value, RoleEnum::COMPLAINT_MANAGER->value, RoleEnum::COMPLAINT_WORKER->value]],
                'per-part'      => ['label' => __('messages.reports.maintenance.per_part'),      'route' => 'reports.maintenance.per-part',      'roles' => [RoleEnum::ADMIN->value, RoleEnum::COMPLAINT_MANAGER->value, RoleEnum::COMPLAINT_WORKER->value]],
                'motor-oil'     => ['label' => __('messages.reports.maintenance.motor_oil'),     'route' => 'reports.maintenance.motor-oil',     'roles' => [RoleEnum::ADMIN->value, RoleEnum::COMPLAINT_MANAGER->value, RoleEnum::COMPLAINT_WORKER->value]],
                'most-repaired' => ['label' => __('messages.reports.maintenance.most_repaired'), 'route' => 'reports.maintenance.most-repaired', 'roles' => [RoleEnum::ADMIN->value, RoleEnum::COMPLAINT_MANAGER->value, RoleEnum::COMPLAINT_WORKER->value]],
            ],
        ],
        'oil_change' => [
            'title'   => __('messages.reports.oil_change.title'),
            'eyebrow' => __('messages.nav.operations'),
            'icon'    => 'fa-oil-can-drip',
            'reports' => [
                'history'         => ['label' => __('messages.reports.oil_change.history'),        'route' => 'reports.oil-change.history',        'roles' => [RoleEnum::ADMIN->value]],
                'upcoming'        => ['label' => __('messages.reports.oil_change.upcoming'),       'route' => 'reports.oil-change.upcoming',       'roles' => [RoleEnum::ADMIN->value]],
                'counts'          => ['label' => __('messages.reports.oil_change.counts'),         'route' => 'reports.oil-change.counts',         'roles' => [RoleEnum::ADMIN->value]],
                'adherence'       => ['label' => __('messages.reports.oil_change.adherence'),      'route' => 'reports.oil-change.adherence',      'roles' => [RoleEnum::ADMIN->value]],
                'catalog-usage'   => ['label' => __('messages.reports.oil_change.catalog_usage'),  'route' => 'reports.oil-change.catalog-usage',  'roles' => [RoleEnum::ADMIN->value]],
            ],
        ],

        'parts_usage' => [
            'title'   => __('messages.reports.parts_usage.title'),
            'eyebrow' => __('messages.nav.warehouses'),
            'icon'    => 'fa-puzzle-piece',
            'reports' => [
                'per-bus'        => ['label' => __('messages.reports.parts_usage.per_bus'),        'route' => 'reports.parts-usage.per-bus',        'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value, RoleEnum::COMPLAINT_MANAGER->value]],
                'top-consumed'   => ['label' => __('messages.reports.parts_usage.top_consumed'),   'route' => 'reports.parts-usage.top-consumed',   'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value, RoleEnum::COMPLAINT_MANAGER->value]],
                'bus-cost'       => ['label' => __('messages.reports.parts_usage.bus_cost'),       'route' => 'reports.parts-usage.bus-cost',       'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
                'per-complaint'  => ['label' => __('messages.reports.parts_usage.per_complaint'),  'route' => 'reports.parts-usage.per-complaint',  'roles' => [RoleEnum::ADMIN->value, RoleEnum::COMPLAINT_MANAGER->value]],
                'dead-stock'     => ['label' => __('messages.reports.parts_usage.dead_stock'),     'route' => 'reports.parts-usage.dead-stock',     'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
            ],
        ],

        // ==================================================================
        // FLEET HEALTH REPORTS  (#20–#25)
        // ==================================================================
        'fleet_health' => [
            'title'   => __('messages.reports.fleet_health.title'),
            'eyebrow' => __('messages.nav.operations'),
            'icon'    => 'fa-heart-pulse',
            'reports' => [
                'cost-per-km'          => ['label' => __('messages.reports.fleet_health.cost_per_km'),          'route' => 'reports.fleet-health.cost-per-km',          'roles' => [RoleEnum::ADMIN->value]],
                'downtime'             => ['label' => __('messages.reports.fleet_health.downtime'),             'route' => 'reports.fleet-health.downtime',             'roles' => [RoleEnum::ADMIN->value, RoleEnum::COMPLAINT_MANAGER->value]],
                'recurring-issues'     => ['label' => __('messages.reports.fleet_health.recurring_issues'),     'route' => 'reports.fleet-health.recurring-issues',     'roles' => [RoleEnum::ADMIN->value, RoleEnum::COMPLAINT_MANAGER->value]],
                'recurring-complaints' => ['label' => __('messages.reports.fleet_health.recurring_complaints'), 'route' => 'reports.fleet-health.recurring-complaints', 'roles' => [RoleEnum::ADMIN->value, RoleEnum::COMPLAINT_MANAGER->value]],
                'accidents'            => ['label' => __('messages.reports.fleet_health.accidents'),            'route' => 'reports.fleet-health.accidents',            'roles' => [RoleEnum::ADMIN->value, RoleEnum::COMPLAINT_MANAGER->value]],
                'utilization'          => ['label' => __('messages.reports.fleet_health.utilization'),          'route' => 'reports.fleet-health.utilization',          'roles' => [RoleEnum::ADMIN->value, RoleEnum::DAILY_KM_MANAGER->value]],
            ],
        ],
        // ==================================================================
        // WAREHOUSE ANALYTICS REPORTS  (#26, #28, #29, #30, #31)
        // ==================================================================
        'warehouse_analytics' => [
            'title'   => __('messages.reports.warehouse_analytics.title'),
            'eyebrow' => __('messages.nav.warehouses'),
            'icon'    => 'fa-chart-pie',
            'reports' => [
                'slow-moving'   => ['label' => __('messages.reports.warehouse_analytics.slow_moving'),  'route' => 'reports.warehouse-analytics.slow-moving',  'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
                'valuation'     => ['label' => __('messages.reports.warehouse_analytics.valuation'),    'route' => 'reports.warehouse-analytics.valuation',    'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
                'reorder'       => ['label' => __('messages.reports.warehouse_analytics.reorder'),      'route' => 'reports.warehouse-analytics.reorder',      'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
                'supplier'      => ['label' => __('messages.reports.warehouse_analytics.supplier'),     'route' => 'reports.warehouse-analytics.supplier',     'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
                'part-history'  => ['label' => __('messages.reports.warehouse_analytics.part_history'), 'route' => 'reports.warehouse-analytics.part-history', 'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
            ],
        ],
        // ==================================================================
        // OIL CHANGE REPORTS  (#11–#15)
        // ==================================================================
        'oil_change' => [
            'title'   => __('messages.reports.oil_change.title'),
            'eyebrow' => __('messages.nav.operations'),
            'icon'    => 'fa-oil-can-drip',
            'reports' => [
                'history'        => ['label' => __('messages.reports.oil_change.history'),       'route' => 'reports.oil-change.history',       'roles' => [RoleEnum::ADMIN->value]],
                'upcoming'       => ['label' => __('messages.reports.oil_change.upcoming'),      'route' => 'reports.oil-change.upcoming',      'roles' => [RoleEnum::ADMIN->value]],
                'counts'         => ['label' => __('messages.reports.oil_change.counts'),        'route' => 'reports.oil-change.counts',        'roles' => [RoleEnum::ADMIN->value]],
                'adherence'      => ['label' => __('messages.reports.oil_change.adherence'),     'route' => 'reports.oil-change.adherence',     'roles' => [RoleEnum::ADMIN->value]],
                'catalog-usage'  => ['label' => __('messages.reports.oil_change.catalog_usage'), 'route' => 'reports.oil-change.catalog-usage', 'roles' => [RoleEnum::ADMIN->value]],
            ],
        ],

        // ==================================================================
        // PARTS USAGE REPORTS  (#16–#19, #27)
        // ==================================================================
        'parts_usage' => [
            'title'   => __('messages.reports.parts_usage.title'),
            'eyebrow' => __('messages.nav.warehouses'),
            'icon'    => 'fa-puzzle-piece',
            'reports' => [
                'per-bus'        => ['label' => __('messages.reports.parts_usage.per_bus'),       'route' => 'reports.parts-usage.per-bus',       'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value, RoleEnum::COMPLAINT_MANAGER->value]],
                'top-consumed'   => ['label' => __('messages.reports.parts_usage.top_consumed'),  'route' => 'reports.parts-usage.top-consumed',  'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value, RoleEnum::COMPLAINT_MANAGER->value]],
                'bus-cost'       => ['label' => __('messages.reports.parts_usage.bus_cost'),      'route' => 'reports.parts-usage.bus-cost',      'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
                'per-complaint'  => ['label' => __('messages.reports.parts_usage.per_complaint'), 'route' => 'reports.parts-usage.per-complaint', 'roles' => [RoleEnum::ADMIN->value, RoleEnum::COMPLAINT_MANAGER->value]],
                'dead-stock'     => ['label' => __('messages.reports.parts_usage.dead_stock'),    'route' => 'reports.parts-usage.dead-stock',    'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
            ],
        ],
    ];

    $config = $domainConfig[$domain] ?? null;
    abort_unless($config, 404);

    if ($shellIsDirector) {
        $visibleReports = $config['reports'];
    } else {
        $visibleReports = array_filter($config['reports'], function ($r) use ($shellUser) {
            if ($shellUser->isSuperAdmin()) {
                return true;
            }
            foreach ($r['roles'] as $role) {
                if ($shellUser->hasGarageRole($role)) {
                    return true;
                }
            }
            return false;
        });
    }

    $resolveRoute = function (string $routeName) use ($shellRoutePrefix) {
        return $shellRoutePrefix.$routeName;
    };

    // Preserve period, custom range AND the brand filter across tab switches.
    $queryString = request()->only(['period', 'from', 'to', 'brand_id']);

    // ─── Brand filter support ───
    // Only the three bus-related domains accept a brand filter.
    $brandSupportedDomains = ['complaint', 'daily_km', 'daily_status', 'maintenance', 'oil_change', 'parts_usage', 'fleet_health', 'warehouse_analytics'];
    $brandFilterSupported = in_array($domain, $brandSupportedDomains, true);

    $reportBrands = collect();
    $selectedBrandId = request('brand_id');

    if ($brandFilterSupported) {
        if ($shellIsDirector) {
            $directorCompany = $shellUser->activeDirectorCompany();

            $reportBrands = $directorCompany
                ? \App\Models\BusBrand::withoutGlobalScope('garage')
                    ->where('company_id', $directorCompany->id)
                    ->whereNull('deleted_at')
                    ->orderBy('name')
                    ->get()
                : collect();
        } else {
            $reportBrands = \App\Models\BusBrand::active()
                ->orderBy('name')
                ->get();
        }
    }
@endphp

@section('title', $config['title'])

@section('content')
<div class="fleet-dashboard">
    <section class="fleet-page-heading">
        <div>
            <span class="fleet-eyebrow">
                {{ __('messages.reports.eyebrow') }}
                · {{ $config['eyebrow'] }}
                @if($shellIsDirector)
                    · {{ __('messages.director.reports.company_scope') }}
                @endif
                @if($selectedBrandId && $reportBrands->isNotEmpty())
                    @php $activeBrand = $reportBrands->firstWhere('id', (int) $selectedBrandId); @endphp
                    @if($activeBrand)
                        · <span class="text-primary">{{ $activeBrand->name }}</span>
                    @endif
                @endif
            </span>
            <h1>{{ $config['title'] }}</h1>
            <p>
                @if($shellIsDirector)
                    {{ __('messages.director.reports.subtitle') }}
                @else
                    {{ __('messages.reports.subtitle') }}
                @endif
            </p>
        </div>
        @if($shellIsDirector)
            <div class="fleet-page-heading__actions">
                <a href="{{ route('director.dashboard') }}" class="fleet-button fleet-button--secondary">
                    <i class="fas fa-arrow-left"></i> {{ __('messages.common.back') }}
                </a>
            </div>
        @endif
    </section>

    {{-- Tabs --}}
    <div class="report-tabs mb-4">
        @foreach($visibleReports as $key => $r)
            <a href="{{ route($resolveRoute($r['route']), $queryString) }}"
               class="report-tab {{ $activeReport === $key ? 'report-tab--active' : '' }}">
                {{ $r['label'] }}
            </a>
        @endforeach
    </div>

    {{-- Period & Brand filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route($resolveRoute($config['reports'][$activeReport]['route'] ?? array_key_first($config['reports']))) }}">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">{{ __('messages.reports.period.label') }}</label>
                        <select name="period" class="form-select" onchange="this.form.submit()">
                            <option value="daily"   @selected(request('period', 'monthly') === 'daily')>{{ __('messages.reports.period.daily') }}</option>
                            <option value="weekly"  @selected(request('period', 'monthly') === 'weekly')>{{ __('messages.reports.period.weekly') }}</option>
                            <option value="monthly" @selected(request('period', 'monthly') === 'monthly')>{{ __('messages.reports.period.monthly') }}</option>
                            <option value="custom"  @selected(request('period') === 'custom')>{{ __('messages.reports.period.custom') }}</option>
                        </select>
                    </div>

                    @if($brandFilterSupported)
                        <div class="col-md-3">
                            <label class="form-label fw-bold">{{ __('messages.reports.brand') }}</label>
                            <select name="brand_id" class="form-select" onchange="this.form.submit()">
                                <option value="">{{ __('messages.reports.all_brands') }}</option>
                                @foreach($reportBrands as $brand)
                                    <option value="{{ $brand->id }}" @selected($selectedBrandId == $brand->id)>
                                        {{ $brand->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if(request('period') === 'custom')
                        <div class="col-md-3">
                            <label class="form-label fw-bold">{{ __('messages.reports.period.from') }}</label>
                            <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">{{ __('messages.reports.period.to') }}</label>
                            <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-check"></i> {{ __('messages.reports.period.apply') }}
                            </button>
                        </div>
                    @endif

                    <div class="col-md-3 ms-auto d-flex align-items-center justify-content-end gap-2 flex-wrap">
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i>
                            {{ request()->filled('from') && request()->filled('to') && request('period') === 'custom'
                                ? \Carbon\Carbon::parse(request('from'))->format('d.m.Y') . ' — ' . \Carbon\Carbon::parse(request('to'))->format('d.m.Y')
                                : match(request('period', 'monthly')) {
                                    'daily'   => now()->format('d.m.Y'),
                                    'weekly'  => now()->startOfWeek()->format('d.m.Y') . ' — ' . now()->endOfWeek()->format('d.m.Y'),
                                    default   => now()->startOfMonth()->format('d.m.Y') . ' — ' . now()->endOfMonth()->format('d.m.Y'),
                                }
                            }}
                        </small>

                        {{-- ✅ Export button — only rendered for reports
                             that implement export support (they set the
                             $exportUrl variable in their render() helper).
                             The URL is the current filter URL with
                             ?export=xlsx appended, so the export respects
                             the same period, brand and custom date range. --}}
                        @isset($exportUrl)
                            <a href="{{ $exportUrl }}"
                               class="btn btn-sm btn-outline-success"
                               title="{{ __('messages.reports.export_excel') }}">
                                <i class="fas fa-file-excel"></i>
                                {{ __('messages.reports.export_excel') }}
                            </a>
                        @endisset
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Report content --}}
    @yield('report-content')
</div>
@endsection

@push('styles')
<style>
    .report-tabs {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
        border-bottom: 2px solid #e5eaf1;
        padding-bottom: 0;
    }
    .report-tab {
        padding: 10px 18px;
        font-size: 13px;
        font-weight: 700;
        color: #64748b;
        text-decoration: none;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        transition: all 0.15s;
    }
    .report-tab:hover {
        color: #2563eb;
    }
    .report-tab--active {
        color: #2563eb;
        border-bottom-color: #2563eb;
    }
    html[data-fleet-theme="dark"] .report-tabs { border-color: #2c3c51; }
    html[data-fleet-theme="dark"] .report-tab { color: #a8b9ce; }
    html[data-fleet-theme="dark"] .report-tab--active { color: #60a5fa; border-bottom-color: #60a5fa; }
</style>
@endpush
