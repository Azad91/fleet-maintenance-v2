@extends('reports.layouts.report-shell')

@section('report-content')
<div class="fleet-kpi-grid mb-4">
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--blue"><i class="fas fa-clipboard-list"></i></span>
        <div>
            <span>{{ __('messages.reports.content.cards_opened') }}</span>
            <strong>{{ $data['total_complaints'] }}</strong>
            <small>{{ __('messages.reports.content.in_period') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--violet"><i class="fas fa-boxes-stacked"></i></span>
        <div>
            <span>{{ __('messages.reports.content.total_quantity') }}</span>
            <strong>{{ $data['total_parts'] }}</strong>
            <small>{{ __('messages.reports.content.in_period') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--amber"><i class="fas fa-chart-bar"></i></span>
        <div>
            <span>{{ __('messages.reports.content.avg') }}</span>
            <strong>{{ $data['avg_parts_per_complaint'] }}</strong>
            <small>{{ __('messages.reports.content.per_complaint') }}</small>
        </div>
    </article>
</div>

<div class="card">
    <div class="card-body">
        <p class="text-muted mb-0">
            <i class="fas fa-info-circle"></i>
            {{ __('messages.reports.content.per_complaint_hint') }}
        </p>
    </div>
</div>
@endsection
