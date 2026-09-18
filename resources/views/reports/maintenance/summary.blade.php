@extends('reports.layouts.report-shell')

@section('report-content')
<div class="fleet-kpi-grid mb-4">
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--amber">
            <i class="fas fa-file-circle-plus"></i>
        </span>
        <div>
            <span>{{ __('messages.reports.content.cards_opened') }}</span>
            <strong>{{ $summary['opened'] }}</strong>
            <small>{{ __('messages.reports.content.in_period') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--blue">
            <i class="fas fa-circle-check"></i>
        </span>
        <div>
            <span>{{ __('messages.reports.content.cards_closed') }}</span>
            <strong>{{ $summary['closed'] }}</strong>
            <small>{{ __('messages.reports.content.in_period') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--violet">
            <i class="fas fa-oil-can"></i>
        </span>
        <div>
            <span>{{ __('messages.reports.content.motor_oil_count') }}</span>
            <strong>{{ $summary['motor_oil'] }}</strong>
            <small>{{ __('messages.reports.content.in_period') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--rose">
            <i class="fas fa-stopwatch"></i>
        </span>
        <div>
            <span>{{ __('messages.reports.content.avg_close_hours') }}</span>
            <strong>{{ $summary['avg_close_hours'] ?? '—' }}</strong>
            <small>{{ __('messages.reports.content.hours') }}</small>
        </div>
    </article>
</div>

<div class="fleet-kpi-grid mb-4">
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--blue">
            <i class="fas fa-boxes-stacked"></i>
        </span>
        <div>
            <span>{{ __('messages.reports.content.total_quantity') }}</span>
            <strong>{{ number_format($summary['total_quantity'], 0, ',', '.') }}</strong>
            <small>{{ $summary['distinct_parts'] }} {{ __('messages.reports.content.distinct_parts') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--amber">
            <i class="fas fa-money-bill-wave"></i>
        </span>
        <div>
            <span>{{ __('messages.reports.content.total_cost') }}</span>
            <strong>{{ number_format($summary['total_cost'], 2) }} ₼</strong>
            <small>{{ $summary['parts_lines'] }} {{ __('messages.reports.content.parts_lines') }}</small>
        </div>
    </article>
    @if($summary['top_bus'])
        <article class="fleet-kpi-card">
            <span class="fleet-kpi-card__icon fleet-kpi-card__icon--rose">
                <i class="fas fa-bus"></i>
            </span>
            <div>
                <span>{{ __('messages.reports.content.top_bus') }}</span>
                <strong>{{ $summary['top_bus']->dqn }}</strong>
                <small>{{ $summary['top_bus']->card_count }} {{ __('messages.complaints.total_label') }}</small>
            </div>
        </article>
    @endif
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> {{ __('messages.reports.content.by_type') }}</h5>
            </div>
            <div class="card-body">
                @php $totalTypes = array_sum($summary['by_type']); @endphp
                @forelse($summary['by_type'] as $type => $count)
                    @php $percent = $totalTypes > 0 ? round(($count / $totalTypes) * 100, 1) : 0; @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <strong>{{ __('enums.complaint_type.' . $type) }}</strong>
                            <span>{{ $count }} <small class="text-muted">({{ $percent }}%)</small></span>
                        </div>
                        <div style="height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                            <div style="width: {{ $percent }}%; height: 100%; background: linear-gradient(90deg, #f59e0b, #fbbf24);"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-3 d-block" style="opacity: .3;"></i>
                        {{ __('messages.reports.content.no_data') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-location-dot"></i> {{ __('messages.reports.content.by_location') }}</h5>
            </div>
            <div class="card-body">
                @php $totalLoc = array_sum($summary['by_location']); @endphp
                @forelse($summary['by_location'] as $loc => $count)
                    @php $percent = $totalLoc > 0 ? round(($count / $totalLoc) * 100, 1) : 0; @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <strong>{{ __('enums.location.' . $loc) }}</strong>
                            <span>{{ $count }} <small class="text-muted">({{ $percent }}%)</small></span>
                        </div>
                        <div style="height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                            <div style="width: {{ $percent }}%; height: 100%; background: linear-gradient(90deg, #2563eb, #60a5fa);"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-3 d-block" style="opacity: .3;"></i>
                        {{ __('messages.reports.content.no_data') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
