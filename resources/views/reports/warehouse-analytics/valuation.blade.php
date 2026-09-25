@extends('reports.layouts.report-shell')

@section('report-content')
<div class="fleet-kpi-grid mb-4">
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--amber"><i class="fas fa-coins"></i></span>
        <div>
            <span>{{ __('messages.reports.content.total_value') }}</span>
            <strong>{{ number_format($data['total_value'], 2) }} ₼</strong>
            <small>{{ __('messages.reports.content.total_value') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--blue"><i class="fas fa-boxes-stacked"></i></span>
        <div>
            <span>{{ __('messages.reports.content.total_items') }}</span>
            <strong>{{ $data['total_items'] }}</strong>
            <small>{{ __('messages.reports.content.items') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--violet"><i class="fas fa-layer-group"></i></span>
        <div>
            <span>{{ __('messages.reports.content.total_quantity') }}</span>
            <strong>{{ number_format($data['total_quantity'], 0, ',', '.') }}</strong>
            <small>{{ __('messages.warehouse.quantity') }}</small>
        </div>
    </article>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-chart-pie"></i> {{ __('messages.reports.content.category_breakdown') }}</h5>
    </div>
    <div class="card-body">
        @php $totalValue = max(1, $data['total_value']); @endphp
        @forelse($data['by_category'] as $row)
            @php $percent = round(($row->total_value / $totalValue) * 100, 1); @endphp
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <strong>
                        {{ $row->category_key === 'uncategorized'
                            ? __('messages.reports.content.uncategorized')
                            : $row->category_key }}
                    </strong>
                    <span>
                        {{ number_format($row->total_value, 2) }} ₼
                        <small class="text-muted">({{ $percent }}%)</small>
                    </span>
                </div>
                <div style="height:8px; background:#f1f5f9; border-radius:4px; overflow:hidden;">
                    <div style="width:{{ $percent }}%; height:100%; background:linear-gradient(90deg,#f59e0b,#fbbf24);"></div>
                </div>
                <small class="text-muted">
                    {{ (int) $row->item_count }} {{ __('messages.reports.content.items') }}
                    · {{ (int) $row->total_quantity }} {{ __('messages.warehouse.quantity') }}
                </small>
            </div>
        @empty
            <div class="text-center text-muted py-5">
                <i class="fas fa-inbox fa-2x mb-3 d-block" style="opacity:.3;"></i>
                {{ __('messages.reports.content.no_data') }}
            </div>
        @endforelse
    </div>
</div>
@endsection
