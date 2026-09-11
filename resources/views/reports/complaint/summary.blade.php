@extends('reports.layouts.report-shell')

@section('report-content')
<div class="fleet-kpi-grid mb-4">
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--amber">
            <i class="fas fa-file-circle-plus"></i>
        </span>
        <div>
            <span>{{ __('messages.reports.content.opened') }}</span>
            <strong>{{ $summary['opened'] }}</strong>
            <small>{{ __('messages.reports.content.in_period') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--blue">
            <i class="fas fa-circle-check"></i>
        </span>
        <div>
            <span>{{ __('messages.reports.content.closed') }}</span>
            <strong>{{ $summary['closed'] }}</strong>
            <small>{{ __('messages.reports.content.in_period') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--rose">
            <i class="fas fa-hourglass-half"></i>
        </span>
        <div>
            <span>{{ __('messages.reports.content.open_now') }}</span>
            <strong>{{ $summary['open_now'] }}</strong>
            <small>{{ __('messages.reports.content.current_state') }}</small>
        </div>
    </article>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-chart-pie"></i> {{ __('messages.reports.content.by_status') }}
        </h5>
    </div>
    <div class="card-body">
        @php
            $statuses = $summary['by_status'] ?? [];
            $total = array_sum($statuses);
        @endphp

        @if($total > 0)
            @foreach($statuses as $status => $count)
                @php $percent = $total > 0 ? round(($count / $total) * 100, 1) : 0; @endphp
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <strong>{{ __('enums.complaint_status.' . $status) }}</strong>
                        <span>
                            {{ $count }} <small class="text-muted">({{ $percent }}%)</small>
                        </span>
                    </div>
                    <div style="height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                        <div style="width: {{ $percent }}%; height: 100%; background: linear-gradient(90deg, #2563eb, #60a5fa);"></div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="text-center text-muted py-4">
                <i class="fas fa-inbox fa-2x mb-3 d-block" style="opacity: .3;"></i>
                {{ __('messages.reports.content.no_data') }}
            </div>
        @endif
    </div>
</div>
@endsection
