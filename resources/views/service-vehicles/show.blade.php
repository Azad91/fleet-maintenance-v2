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
@endsection
