@extends('reports.layouts.report-shell')

@section('report-content')
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-chart-line" style="font-size: 48px; color: #94a3b8;"></i>
        <h4 class="mt-3 mb-2">{{ __('messages.reports.coming_soon') }}</h4>
        <p class="text-muted">{{ __('messages.reports.coming_soon_hint') }}</p>
    </div>
</div>
@endsection
