@extends('reports.layouts.report-shell')

@section('report-content')
<div class="fleet-kpi-grid mb-4">
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--blue"><i class="fas fa-list-ol"></i></span>
        <div>
            <span>{{ __('messages.reports.content.total') }}</span>
            <strong>{{ $data['total'] }}</strong>
            <small>{{ __('messages.reports.content.in_period') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--blue"><i class="fas fa-percent"></i></span>
        <div>
            <span>{{ __('messages.reports.content.on_time_rate') }}</span>
            <strong>{{ $data['on_time_rate'] }}%</strong>
            <small>{{ __('messages.reports.content.early') }} + {{ __('messages.reports.content.on_time') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--rose"><i class="fas fa-hourglass-end"></i></span>
        <div>
            <span>{{ __('messages.reports.content.late') }}</span>
            <strong>{{ $data['late'] }}</strong>
            <small>{{ __('messages.reports.content.in_period') }}</small>
        </div>
    </article>
</div>

<div class="card">
    <div class="card-body">
        @php $total = max(1, $data['total']); @endphp
        @foreach([
            ['label' => __('messages.reports.content.early'),    'value' => $data['early'],   'color' => 'linear-gradient(90deg,#2563eb,#60a5fa)'],
            ['label' => __('messages.reports.content.on_time'),  'value' => $data['on_time'], 'color' => 'linear-gradient(90deg,#10b981,#34d399)'],
            ['label' => __('messages.reports.content.late'),     'value' => $data['late'],    'color' => 'linear-gradient(90deg,#dc2626,#f87171)'],
        ] as $row)
            @php $percent = round(($row['value'] / $total) * 100, 1); @endphp
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <strong>{{ $row['label'] }}</strong>
                    <span>{{ $row['value'] }} <small class="text-muted">({{ $percent }}%)</small></span>
                </div>
                <div style="height:8px; background:#f1f5f9; border-radius:4px; overflow:hidden;">
                    <div style="width:{{ $percent }}%; height:100%; background:{{ $row['color'] }};"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
