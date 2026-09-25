@extends('reports.layouts.report-shell')

@section('report-content')
@php
    $totalCost = $rows->sum('total_cost');
    $avgCost = $rows->avg('total_cost') ?? 0;
    $maxCost = $rows->max('total_cost') ?: 1;
@endphp

<div class="fleet-kpi-grid mb-4">
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--violet"><i class="fas fa-bus"></i></span>
        <div>
            <span>{{ __('messages.dashboard.total_buses') }}</span>
            <strong>{{ $rows->count() }}</strong>
            <small>{{ __('messages.reports.content.in_period') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--amber"><i class="fas fa-coins"></i></span>
        <div>
            <span>{{ __('messages.reports.content.total_cost') }}</span>
            <strong>{{ number_format($totalCost, 2) }} ₼</strong>
            <small>{{ __('messages.reports.content.in_period') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--blue"><i class="fas fa-chart-line"></i></span>
        <div>
            <span>{{ __('messages.reports.content.avg') }}</span>
            <strong>{{ number_format($avgCost, 2) }} ₼</strong>
            <small>{{ __('messages.reports.content.per_bus') }}</small>
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
                        <th class="text-center">{{ __('messages.reports.content.cards_opened') }}</th>
                        <th class="text-end">{{ __('messages.reports.content.avg') }}</th>
                        <th style="width: 25%;">{{ __('messages.reports.content.total_cost') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php $percent = round(($row->total_cost / $maxCost) * 100, 1); @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $row->dqn }}</strong></td>
                            <td>{{ $row->route_number ?? '—' }}</td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--warning">{{ (int) $row->cards_count }}</span>
                            </td>
                            <td class="text-end">
                                {{ number_format($row->avg_part_price, 2) }} ₼
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="flex:1; height:8px; background:#f1f5f9; border-radius:4px; overflow:hidden;">
                                        <div style="width:{{ $percent }}%; height:100%; background:linear-gradient(90deg,#dc2626,#f87171);"></div>
                                    </div>
                                    <strong style="white-space:nowrap;">{{ number_format($row->total_cost, 2) }} ₼</strong>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fas fa-coins fa-2x mb-3 d-block" style="opacity:.3;"></i>
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
