@extends('layouts.app')

@section('title', $vehicle->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.nav.data') }}</span>
        <h1 class="mb-0">🚐 {{ $vehicle->name }}</h1>
        @if($vehicle->plate_number)
            <p class="text-muted mb-0"><code>{{ $vehicle->plate_number }}</code></p>
        @endif
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('service-vehicles.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
        </a>
        <a href="{{ route('service-vehicles.edit', $vehicle) }}" class="btn btn-warning">
            <i class="bi bi-pencil"></i> {{ __('messages.common.edit') }}
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">{{ __('messages.service_vehicles.details') }}</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <small class="text-muted d-block">{{ __('messages.service_vehicles.name') }}</small>
                <strong>{{ $vehicle->name }}</strong>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">{{ __('messages.service_vehicles.plate_number') }}</small>
                <strong>{{ $vehicle->plate_number ?? '—' }}</strong>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">{{ __('messages.common.status') }}</small>
                @if($vehicle->is_active)
                    <span class="badge text-bg-success">{{ __('messages.common.active') }}</span>
                @else
                    <span class="badge text-bg-secondary">{{ __('messages.common.inactive') }}</span>
                @endif
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">{{ __('messages.service_vehicles.driver_name') }}</small>
                <strong>{{ $vehicle->driver_name ?? '—' }}</strong>
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">{{ __('messages.service_vehicles.phone') }}</small>
                <strong>{{ $vehicle->phone ?? '—' }}</strong>
            </div>
            @if($vehicle->notes)
                <div class="col-12">
                    <small class="text-muted d-block">{{ __('messages.common.notes') }}</small>
                    <strong>{{ $vehicle->notes }}</strong>
                </div>
            @endif
            <div class="col-md-6">
                <small class="text-muted d-block">{{ __('messages.complaints.created') }}</small>
                <strong>{{ $vehicle->created_at?->format('d.m.Y H:i') ?? '—' }}</strong>
            </div>
        </div>
    </div>
</div>
{{-- ─── Current stock on this service vehicle ─── --}}
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            📦 {{ __('messages.service_vehicles.current_stock') }}
            <span class="badge bg-secondary ms-2">
                {{ $vehicle->stocks->count() }} {{ __('messages.motor_oil.parts') }}
            </span>
        </h5>
        @if($vehicle->total_stock_quantity > 0)
            <span class="badge bg-primary">
                {{ __('messages.common.total') }}: {{ $vehicle->total_stock_quantity }}
            </span>
        @endif
    </div>
    <div class="card-body p-0">
        @if($vehicle->stocks->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('messages.warehouse.code') }}</th>
                            <th>{{ __('messages.warehouse.name') }}</th>
                            <th>{{ __('messages.warehouse.unit') }}</th>
                            <th class="text-end">{{ __('messages.warehouse.quantity') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vehicle->stocks as $index => $stock)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><code>{{ $stock->code }}</code></td>
                                <td><strong>{{ $stock->name }}</strong></td>
                                <td>{{ $stock->unit ?? '—' }}</td>
                                <td class="text-end">
                                    <strong>{{ number_format($stock->quantity, 0, ',', '.') }}</strong>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox" style="font-size: 40px; display: block; margin-bottom: 10px; opacity: .3;"></i>
                {{ __('messages.service_vehicles.no_stock') }}
            </div>
        @endif
    </div>
</div>
@endsection
