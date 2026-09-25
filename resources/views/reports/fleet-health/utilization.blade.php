@extends('reports.layouts.report-shell')

@section('report-content')
@php
    $avgUtil = $rows->avg('utilization_percent') ?? 0;
    $activeBuses = $rows->where('utilization_percent', '>=', 70)->count();
@endphp

<div class="fleet-kpi-grid mb-4">
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--blue"><i class="fas fa-bus"></i></span>
        <div>
            <span>{{ __('messages.dashboard.total_buses') }}</span>
            <strong>{{ $rows->count() }}</strong>
            <small>{{ __('messages.reports.content.in_period') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--violet"><i class="fas fa-chart-line"></i></span>
        <div>
            <span>{{ __('messages.reports.content.avg') }}</span>
            <strong>{{ number_format($avgUtil, 1) }}%</strong>
            <small>{{ __('messages.reports.content.utilization') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--amber"><i class="fas fa-circle-check"></i></span>
        <div>
            <span>≥ 70%</span>
            <strong>{{ $activeBuses }}</strong>
            <small>{{ __('messages.dashboard.oil_buses') }}</small>
        </div>
    </article>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.buses.dqn') }}</th>
                        <th>{{ __('messages.buses.route_number') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.days_in_service') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.total_days') }}</th>
                        <th class="text-end">{{ __('messages.reports.content.distance') }}</th>
                        <th style="width: 25%;">{{ __('messages.reports.content.utilization') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $percent = $row->utilization_percent;
                            $color = $percent >= 70 ? 'linear-gradient(90deg,#10b981,#34d399)'
                                : ($percent >= 40 ? 'linear-gradient(90deg,#f59e0b,#fbbf24)'
                                : 'linear-gradient(90deg,#dc2626,#f87171)');
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $row->dqn }}</strong></td>
                            <td>{{ $row->route_number ?? '—' }}</td>
                            <td class="text-center">{{ $row->days_in_service }}</td>
                            <td class="text-center text-muted">{{ $row->total_days_in_period }}</td>
                            <td class="text-end">{{ number_format($row->total_distance, 0, ',', '.') }} km</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="flex:1; height:8px; background:#f1f5f9; border-radius:4px; overflow:hidden;">
                                        <div style="width:{{ $percent }}%; height:100%; background:{{ $color }};"></div>
                                    </div>
                                    <strong style="white-space:nowrap;">{{ $percent }}%</strong>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-bus fa-2x mb-3 d-block" style="opacity:.3;"></i>
                                {{ __('messages.reports.content.no_data') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
