@extends('reports.layouts.report-shell')

@section('report-content')
<div class="fleet-kpi-grid mb-4">
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--blue">
            <i class="fas fa-stopwatch"></i>
        </span>
        <div>
            <span>{{ __('messages.reports.content.avg_close_hours') }}</span>
            <strong>{{ $data['overall_avg_hours'] ?? '—' }}</strong>
            <small>{{ __('messages.reports.content.hours') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--violet">
            <i class="fas fa-list-ol"></i>
        </span>
        <div>
            <span>{{ __('messages.reports.content.sample_count') }}</span>
            <strong>{{ $data['sample_count'] }}</strong>
            <small>{{ __('messages.reports.content.closed_cards') }}</small>
        </div>
    </article>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-chart-bar"></i> {{ __('messages.reports.content.by_type') }}
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('messages.complaints.complaint_type') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.count') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.avg') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.min') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.max') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data['by_type'] as $row)
                        <tr>
                            <td>
                                <strong>
                                    {{ $row->type
                                        ? __('enums.complaint_type.' . $row->type)
                                        : __('messages.reports.content.unknown_type') }}
                                </strong>
                            </td>
                            <td class="text-center">{{ $row->count }}</td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--warning">{{ $row->avg_hours }} h</span>
                            </td>
                            <td class="text-center">{{ $row->min_hours }} h</td>
                            <td class="text-center">{{ $row->max_hours }} h</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-stopwatch fa-2x mb-3 d-block" style="opacity: .3;"></i>
                                {{ __('messages.reports.content.no_data') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
