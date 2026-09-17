@extends('reports.layouts.report-shell')

@section('report-content')
@if($transfers->isEmpty())
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="fas fa-check-circle fa-3x mb-3 d-block" style="color: #10b981; opacity: .5;"></i>
            <p class="mb-0">{{ __('messages.transfers.report.no_disputes') }}</p>
        </div>
    </div>
@else
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('messages.transfers.from_garage') }}</th>
                            <th>{{ __('messages.transfers.to_garage') }}</th>
                            <th class="text-end">{{ __('messages.transfers.declared_total') }}</th>
                            <th class="text-end">{{ __('messages.transfers.received_total') }}</th>
                            <th class="text-end">{{ __('messages.transfers.discrepancy') }}</th>
                            <th>{{ __('messages.transfers.status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transfers as $transfer)
                            @php
                                $diff = ($transfer->received_total ?? 0) - $transfer->declared_total;
                            @endphp
                            <tr>
                                <td><strong>#{{ $transfer->id }}</strong></td>
                                <td>{{ $transfer->fromGarage?->name ?? '—' }}</td>
                                <td>
                                    @if($transfer->toGarage)
                                        {{ $transfer->toGarage->name }}
                                    @elseif($transfer->toServiceVehicle)
                                        🚐 {{ $transfer->toServiceVehicle->name }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end">{{ $transfer->declared_total }}</td>
                                <td class="text-end">{{ $transfer->received_total ?? '—' }}</td>
                                <td class="text-end">
                                    <strong class="text-danger">
                                        {{ $diff > 0 ? '+' : '' }}{{ $diff }}
                                    </strong>
                                </td>
                                <td>
                                    <span class="badge bg-warning">{{ $transfer->status->label() }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('warehouse-transfers.show', $transfer) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
@endsection
