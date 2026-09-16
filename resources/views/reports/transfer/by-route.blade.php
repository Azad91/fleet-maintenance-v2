@extends('reports.layouts.report-shell')

@section('report-content')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.transfers.from_garage') }}</th>
                        <th>{{ __('messages.transfers.to_garage') }}</th>
                        <th class="text-center">{{ __('messages.transfers.report.total_transfers') }}</th>
                        <th class="text-center">{{ __('messages.transfers.report.received') }}</th>
                        <th class="text-center">{{ __('messages.transfers.report.disputed') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $item->from_garage_name }}</strong>
                                <br>
                                <small class="text-muted"><code>{{ $item->from_garage_code }}</code></small>
                            </td>
                            <td>
                                @if($item->to_garage_name)
                                    <strong>{{ $item->to_garage_name }}</strong>
                                    <br>
                                    <small class="text-muted"><code>{{ $item->to_garage_code }}</code></small>
                                @else
                                    <span class="text-muted">🚐 {{ __('messages.transfers.to_service_vehicle') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--warning">{{ $item->total_transfers }}</span>
                            </td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--success">{{ $item->received_count }}</span>
                            </td>
                            <td class="text-center">
                                @if($item->disputed_count > 0)
                                    <span class="fleet-status fleet-status--muted">{{ $item->disputed_count }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-3 d-block" style="opacity: .3;"></i>
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
