@extends('layouts.app')

@php
    use App\Enums\RoleEnum;

    // Domain metadata — tabs and titles per domain
    $domainConfig = [
        'warehouse' => [
            'title'   => __('messages.reports.warehouse.title'),
            'eyebrow' => __('messages.nav.warehouses'),
            'icon'    => 'fa-boxes-stacked',
            'reports' => [
                'receipt'         => ['label' => __('messages.reports.warehouse.receipt'),         'route' => 'reports.warehouse.receipt',         'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
                'usage'           => ['label' => __('messages.reports.warehouse.usage'),           'route' => 'reports.warehouse.usage',           'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
                'worker-activity' => ['label' => __('messages.reports.warehouse.worker_activity'), 'route' => 'reports.warehouse.worker-activity', 'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
                'low-stock'       => ['label' => __('messages.reports.warehouse.low_stock'),       'route' => 'reports.warehouse.low-stock',       'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value, RoleEnum::WAREHOUSE_WORKER->value]],
                'movement'        => ['label' => __('messages.reports.warehouse.movement'),        'route' => 'reports.warehouse.movement',        'roles' => [RoleEnum::ADMIN->value, RoleEnum::WAREHOUSE_MANAGER->value]],
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
    ];

    $config = $domainConfig[$domain] ?? null;
    abort_unless($config, 404);

    $currentUser = auth()->user();

    // Filter which report tabs the current user can see
    $visibleReports = array_filter($config['reports'], function ($r) use ($currentUser) {
        if ($currentUser->isSuperAdmin()) {
            return true; // Super Admin sees all
        }
        foreach ($r['roles'] as $role) {
            if ($currentUser->hasGarageRole($role)) {
                return true;
            }
        }
        return false;
    });

    // Preserve current period parameters when switching between tabs
    $queryString = request()->only(['period', 'from', 'to']);
@endphp

@section('title', $config['title'])

@section('content')
<div class="fleet-dashboard">
    <section class="fleet-page-heading">
        <div>
            <span class="fleet-eyebrow">{{ __('messages.reports.eyebrow') }} · {{ $config['eyebrow'] }}</span>
            <h1>{{ $config['title'] }}</h1>
            <p>{{ __('messages.reports.subtitle') }}</p>
        </div>
    </section>

    {{-- Tabs --}}
    <div class="report-tabs mb-4">
        @foreach($visibleReports as $key => $r)
            <a href="{{ route($r['route'], $queryString) }}"
               class="report-tab {{ $activeReport === $key ? 'report-tab--active' : '' }}">
                {{ $r['label'] }}
            </a>
        @endforeach
    </div>

    {{-- Period filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route($config['reports'][$activeReport]['route'] ?? array_key_first($config['reports'])) }}">
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

                    <div class="col-md-3 ms-auto text-end">
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
