@extends('reports.layouts.report-shell')

@section('report-content')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.buses.dqn') }}</th>
                        <th>{{ __('messages.buses.route_number') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.total') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.completed') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.open_now') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php $open = $item->total - $item->completed; @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $item->dqn }}</strong></td>
                            <td>{{ $item->route_number ?? '—' }}</td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--warning">{{ $item->total }}</span>
                            </td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--success">{{ $item->completed }}</span>
                            </td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--muted">{{ $open }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-bus fa-2x mb-3 d-block" style="opacity: .3;"></i>
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
