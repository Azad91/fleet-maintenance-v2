@extends('reports.layouts.report-shell')

@section('report-content')
<div class="fleet-kpi-grid mb-4">
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--rose"><i class="fas fa-car-burst"></i></span>
        <div>
            <span>{{ __('messages.reports.fleet_health.accidents') }}</span>
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
                        <th>{{ __('messages.reports.content.date') }}</th>
                        <th>{{ __('messages.buses.dqn') }}</th>
                        <th>{{ __('messages.buses.route_number') }}</th>
                        <th>{{ __('messages.complaints.driver') }}</th>
                        <th>{{ __('messages.complaints.location') }}</th>
                        <th>{{ __('messages.common.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->created_at?->format('d.m.Y') ?? '—' }}</td>
                            <td><strong>{{ $row->bus?->dqn ?? '—' }}</strong></td>
                            <td>{{ $row->bus?->route_number ?? '—' }}</td>
                            <td>{{ $row->driver?->full_name ?? $row->driver_name ?? '—' }}</td>
                            <td>{{ $row->yer?->label() ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $row->status?->bootstrapColor() ?? 'secondary' }}">
                                    {{ $row->status?->label() ?? '—' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fas fa-shield-halved fa-2x mb-3 d-block" style="color:#10b981; opacity:.5;"></i>
                                {{ __('messages.reports.content.no_accidents') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
