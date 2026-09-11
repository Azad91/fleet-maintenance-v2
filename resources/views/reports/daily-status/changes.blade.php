@extends('reports.layouts.report-shell')

@section('report-content')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('messages.reports.content.date') }}</th>
                        <th>{{ __('messages.reports.content.user') }}</th>
                        <th>{{ __('messages.reports.content.event') }}</th>
                        <th>{{ __('messages.reports.content.status') }}</th>
                        <th>{{ __('messages.reports.content.changes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        @php
                            $status = $log->new_values['status'] ?? $log->old_values['status'] ?? '—';
                            $eventClass = match($log->event) {
                                'created'                  => 'fleet-status--success',
                                'updated'                  => 'fleet-status--warning',
                                'deleted', 'force_deleted' => 'fleet-status--muted',
                                default                    => 'fleet-status--muted',
                            };
                        @endphp
                        <tr>
                            <td>{{ $log->created_at->format('d.m.Y H:i') }}</td>
                            <td>{{ $log->user?->name ?? __('messages.reports.content.unknown_user') }}</td>
                            <td>
                                <span class="fleet-status {{ $eventClass }}">
                                    {{ __('messages.reports.content.event_' . $log->event) }}
                                </span>
                            </td>
                            <td><strong>{{ $status }}</strong></td>
                            <td>
                                @if($log->event === 'updated' && $log->new_values)
                                    <small class="text-muted">
                                        {{ implode(', ', array_keys($log->new_values)) }}
                                    </small>
                                @else
                                    <small class="text-muted">—</small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-history fa-2x mb-3 d-block" style="opacity: .3;"></i>
                                {{ __('messages.reports.content.no_data') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->isNotEmpty())
        <div class="card-footer text-muted small text-end">
            {{ __('messages.reports.content.records_count', ['count' => $logs->count()]) }}
        </div>
    @endif
</div>
@endsection
