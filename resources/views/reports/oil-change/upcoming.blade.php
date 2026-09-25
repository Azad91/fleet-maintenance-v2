@extends('reports.layouts.report-shell')

@section('report-content')
<div class="alert alert-info mb-3">
    <i class="fas fa-clock"></i>
    {{ __('messages.reports.content.upcoming_hint') }}
</div>

@php
    $grouped = $rows->groupBy('days_remaining');
@endphp

<div class="fleet-kpi-grid mb-4">
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--amber">
            <i class="fas fa-triangle-exclamation"></i>
        </span>
        <div>
            <span>{{ __('messages.reports.content.total') }}</span>
            <strong>{{ $rows->count() }}</strong>
            <small>{{ __('messages.dashboard.oil_buses') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--rose">
            <i class="fas fa-hourglass-half"></i>
        </span>
        <div>
            <span>{{ __('messages.reports.content.days_remaining') }} ≤ 7</span>
            <strong>{{ $rows->where('days_remaining', '<=', 7)->count() }}</strong>
            <small>{{ __('messages.reports.content.critical') }}</small>
        </div>
    </article>
</div>

@if($rows->isEmpty())
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="fas fa-circle-check fa-2x mb-3 d-block" style="color:#10b981; opacity:.5;"></i>
            {{ __('messages.oil_change.no_priority_items') }}
        </div>
    </div>
@else
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('messages.buses.dqn') }}</th>
                            <th>{{ __('messages.buses.route_number') }}</th>
                            <th>{{ __('messages.oil_change.type') }}</th>
                            <th class="text-end">{{ __('messages.oil_change.current_km') }}</th>
                            <th class="text-end">{{ __('messages.oil_change.next_due_km') }}</th>
                            <th class="text-end">{{ __('messages.oil_change.remaining_km') }}</th>
                            <th class="text-center">{{ __('messages.reports.content.days_remaining') }}</th>
                            <th>{{ __('messages.oil_change.daily_status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            @php $status = $row['status']; @endphp
                            <tr>
                                <td><strong>{{ $row['bus']->dqn }}</strong></td>
                                <td>{{ $row['bus']->route_number ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $row['type']->bootstrapColor() }}">
                                        {{ $row['type']->icon() }} {{ $row['type']->label() }}
                                    </span>
                                </td>
                                <td class="text-end">{{ number_format($status->currentKm, 0, '', '.') }} km</td>
                                <td class="text-end">
                                    {{ $status->nextDueKm ? number_format($status->nextDueKm, 0, '', '.') . ' km' : '—' }}
                                </td>
                                <td class="text-end">
                                    <strong>{{ number_format($status->remainingKm, 0, '', '.') }} km</strong>
                                </td>
                                <td class="text-center">
                                    @php
                                        $days = $row['days_remaining'];
                                        $cls = $days <= 7 ? 'fleet-status--muted' : ($days <= 14 ? 'fleet-status--warning' : 'fleet-status--success');
                                    @endphp
                                    <span class="fleet-status {{ $cls }}">{{ $days }} gün</span>
                                </td>
                                <td>
                                    @if($row['bus']->latestDailyStatus)
                                        <small>{{ $row['bus']->latestDailyStatus->status }}</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
@endsection
