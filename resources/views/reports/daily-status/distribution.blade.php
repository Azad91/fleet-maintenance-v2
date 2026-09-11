@extends('reports.layouts.report-shell')

@section('report-content')
<div class="fleet-kpi-grid mb-4">
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--blue">
            <i class="fas fa-clipboard-check"></i>
        </span>
        <div>
            <span>{{ __('messages.reports.content.total_statuses') }}</span>
            <strong>{{ $data['total'] }}</strong>
            <small>{{ __('messages.reports.content.records_in_period') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--violet">
            <i class="fas fa-list"></i>
        </span>
        <div>
            <span>{{ __('messages.reports.content.unique_statuses') }}</span>
            <strong>{{ $data['rows']->count() }}</strong>
            <small>{{ __('messages.reports.content.distinct_types') }}</small>
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
            $rows = $data['rows'];
            $total = $data['total'];
            $max = $rows->max('total') ?: 1;
        @endphp

        @if($total > 0)
            @foreach($rows as $row)
                @php
                    $percent = $total > 0 ? round(($row->total / $total) * 100, 1) : 0;
                    $barWidth = round(($row->total / $max) * 100, 1);
                @endphp
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <strong>{{ $row->status }}</strong>
                        <span>
                            {{ $row->total }}
                            <small class="text-muted">({{ $percent }}%)</small>
                        </span>
                    </div>
                    <div style="height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                        <div style="width: {{ $barWidth }}%; height: 100%; background: linear-gradient(90deg, #7c3aed, #a78bfa);"></div>
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
