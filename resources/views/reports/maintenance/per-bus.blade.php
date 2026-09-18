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
                        <th class="text-center">{{ __('messages.reports.content.cards_opened') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.cards_closed') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.motor_oil_count') }}</th>
                        <th class="text-end">{{ __('messages.reports.content.total_quantity') }}</th>
                        <th class="text-end">{{ __('messages.reports.content.total_cost') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $row->bus?->dqn ?? '—' }}</strong></td>
                            <td>{{ $row->bus?->route_number ?? '—' }}</td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--warning">{{ $row->cards_opened }}</span>
                            </td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--success">{{ $row->cards_closed }}</span>
                            </td>
                            <td class="text-center">
                                @if($row->motor_oil_count > 0)
                                    <span class="fleet-status fleet-status--warning">🛢️ {{ $row->motor_oil_count }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($row->total_qty, 0, ',', '.') }}</td>
                            <td class="text-end">
                                @if($row->total_cost > 0)
                                    <strong>{{ number_format($row->total_cost, 2) }} ₼</strong>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-bus fa-2x mb-3 d-block" style="opacity: .3;"></i>
                                {{ __('messages.reports.content.no_activity') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
