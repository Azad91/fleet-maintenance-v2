@extends('reports.layouts.report-shell')

@section('report-content')
<div class="alert alert-warning mb-3">
    <i class="fas fa-triangle-exclamation"></i>
    {{ __('messages.reports.content.missing_hint', ['date' => $period->to->format('d.m.Y')]) }}
</div>

<div class="fleet-kpi-grid mb-4">
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--rose">
            <i class="fas fa-gauge-high"></i>
        </span>
        <div>
            <span>{{ __('messages.reports.content.missing_count') }}</span>
            <strong>{{ $buses->count() }}</strong>
            <small>{{ __('messages.reports.content.buses_awaiting') }}</small>
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
                        <th>{{ __('messages.buses.bus_project') }}</th>
                        <th class="text-end">{{ __('messages.buses.col_latest_km') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($buses as $bus)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $bus->dqn }}</strong></td>
                            <td>{{ $bus->route_number ?? '—' }}</td>
                            <td>{{ $bus->bus_project ?? '—' }}</td>
                            <td class="text-end">
                                @if($bus->km)
                                    {{ number_format($bus->km, 0, ',', '.') }} km
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-circle-check fa-2x mb-3 d-block" style="opacity: .3;"></i>
                                {{ __('messages.reports.content.all_buses_recorded') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($buses->isNotEmpty())
        <div class="card-footer text-muted small text-end">
            {{ __('messages.reports.content.records_count', ['count' => $buses->count()]) }}
        </div>
    @endif
</div>
@endsection
