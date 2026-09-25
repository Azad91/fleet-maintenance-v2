@extends('reports.layouts.report-shell')

@section('report-content')
@php
    $maxHours = $rows->max('total_hours') ?: 1;
    $totalHours = $rows->sum('total_hours');
@endphp

<div class="fleet-kpi-grid mb-4">
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--rose"><i class="fas fa-hourglass-half"></i></span>
        <div>
            <span>{{ __('messages.reports.content.total_hours') }}</span>
            <strong>{{ number_format($totalHours, 0, ',', '.') }} h</strong>
            <small>{{ __('messages.reports.content.in_period') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--violet"><i class="fas fa-bus"></i></span>
        <div>
            <span>{{ __('messages.dashboard.total_buses') }}</span>
            <strong>{{ $rows->count() }}</strong>
            <small>{{ __('messages.reports.content.in_period') }}</small>
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
                        <th class="text-end">{{ __('messages.reports.content.total_hours') }}</th>
                        <th class="text-end">{{ __('messages.reports.content.avg_hours') }}</th>
                        <th style="width: 25%;">{{ __('messages.reports.content.total_hours') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php $percent = round(($row->total_hours / $maxHours) * 100, 1); @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $row->dqn }}</strong></td>
                            <td>{{ $row->route_number ?? '—' }}</td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--warning">{{ (int) $row->complaint_count }}</span>
                            </td>
                            <td class="text-end">
                                <strong>{{ number_format((float) $row->total_hours, 1) }} h</strong>
                            </td>
                            <td class="text-end">{{ number_format((float) $row->avg_hours, 1) }} h</td>
                            <td>
                                <div style="height:8px; background:#f1f5f9; border-radius:4px; overflow:hidden;">
                                    <div style="width:{{ $percent }}%; height:100%; background:linear-gradient(90deg,#dc2626,#f87171);"></div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-hourglass-half fa-2x mb-3 d-block" style="opacity:.3;"></i>
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
