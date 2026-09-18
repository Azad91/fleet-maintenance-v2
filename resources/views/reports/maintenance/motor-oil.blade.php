@extends('reports.layouts.report-shell')

@section('report-content')
@forelse($rows as $row)
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>🚌 {{ $row->bus?->dqn ?? '—' }}</strong>
                @if($row->bus?->route_number)
                    <span class="badge bg-secondary ms-2">{{ __('messages.daily_km.route_label', ['route' => $row->bus->route_number]) }}</span>
                @endif
            </div>
            <div>
                <span class="badge bg-info text-dark">
                    {{ $row->complaint->created_at->format('d.m.Y') }}
                </span>
                <span class="badge bg-primary ms-1">
                    {{ number_format($row->complaint->service_km, 0, '', '') }} km
                </span>
            </div>
        </div>
        <div class="card-body p-0">
            @if($row->details->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>{{ __('messages.complaints.part_code') }}</th>
                                <th>{{ __('messages.complaints.part_name') }}</th>
                                <th class="text-center">{{ __('messages.complaints.used_qty') }}</th>
                                <th class="text-end">{{ __('messages.reports.content.price_at_use') }}</th>
                                <th class="text-end">{{ __('messages.reports.content.total_cost') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($row->details as $idx => $detail)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td><code>{{ $detail->code }}</code></td>
                                    <td>{{ $detail->name }}</td>
                                    <td class="text-center">{{ $detail->used_quantity }}</td>
                                    <td class="text-end">
                                        {{ $detail->price_at_use !== null ? number_format($detail->price_at_use, 2) . ' ₼' : '—' }}
                                    </td>
                                    <td class="text-end">
                                        @if($detail->total_cost !== null)
                                            <strong>{{ number_format($detail->total_cost, 2) }} ₼</strong>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="5" class="text-end"><strong>{{ __('messages.reports.content.total_cost') }}:</strong></td>
                                <td class="text-end"><strong>{{ number_format($row->total_cost, 2) }} ₼</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="p-3 text-muted">{{ __('messages.complaints.no_parts_used') }}</div>
            @endif
        </div>
    </div>
@empty
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="fas fa-oil-can fa-3x mb-3 d-block" style="opacity: .3;"></i>
            <p class="mb-0">{{ __('messages.reports.content.no_motor_oil') }}</p>
        </div>
    </div>
@endforelse
@endsection
