@extends('layouts.app')

@section('title', __('messages.service_vehicles.stocks_title'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.nav.data') }}</span>
        <h1 class="mb-1">📦 {{ __('messages.service_vehicles.stocks_title') }}</h1>
        <p class="text-muted mb-0">{{ __('messages.service_vehicles.stocks_subtitle') }}</p>
    </div>
    <a href="{{ route('service-vehicles.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
    </a>
</div>

<div class="row g-3">
    @forelse($vehicles as $vehicle)
        <div class="col-md-6 col-lg-4">
            <a href="{{ route('service-vehicles.show', $vehicle) }}" class="text-decoration-none">
                <div class="card h-100 vehicle-stock-card">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="vehicle-stock-icon">
                                <i class="bi bi-truck"></i>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <h5 class="mb-1 text-dark text-truncate" title="{{ $vehicle->name }}">
                                    {{ $vehicle->name }}
                                </h5>
                                @if($vehicle->plate_number)
                                    <code>{{ $vehicle->plate_number }}</code>
                                @endif
                            </div>
                            @if($vehicle->is_active)
                                <span class="badge bg-success">{{ __('messages.common.active') }}</span>
                            @else
                                <span class="badge bg-secondary">{{ __('messages.common.inactive') }}</span>
                            @endif
                        </div>

                        <div class="row g-2 mt-2">
                            <div class="col-6">
                                <small class="text-muted d-block">
                                    {{ __('messages.service_vehicles.stocks_item_count') }}
                                </small>
                                <strong style="font-size: 20px;">{{ $vehicle->stocks_count }}</strong>
                            </div>
                            <div class="col-6 text-end">
                                <small class="text-muted d-block">
                                    {{ __('messages.service_vehicles.stocks_total_qty') }}
                                </small>
                                <strong style="font-size: 20px;">
                                    {{ number_format((float) ($vehicle->stocks_sum_quantity ?? 0), 0, ',', '.') }}
                                </strong>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent text-end">
                        <span class="text-primary fw-bold" style="font-size: 13px;">
                            {{ __('messages.service_vehicles.view_stock') }}
                            <i class="bi bi-arrow-right ms-1"></i>
                        </span>
                    </div>
                </div>
            </a>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-truck fa-2x mb-3 d-block" style="opacity: .3;"></i>
                    {{ __('messages.service_vehicles.no_vehicles') }}
                </div>
            </div>
        </div>
    @endforelse
</div>
@endsection

@push('styles')
<style>
    .vehicle-stock-card {
        transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
        border: 1px solid #e5eaf1;
    }
    .vehicle-stock-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(37, 99, 235, 0.08);
        border-color: #bfdbfe;
    }
    .vehicle-stock-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #eaf1ff;
        color: #2563eb;
        font-size: 20px;
        flex-shrink: 0;
    }
    .min-width-0 { min-width: 0; }

    html[data-fleet-theme="dark"] .vehicle-stock-card {
        background: #192638;
        border-color: #2c3c51;
    }
    html[data-fleet-theme="dark"] .vehicle-stock-card:hover {
        border-color: #4f8cff;
    }
    html[data-fleet-theme="dark"] .vehicle-stock-icon {
        background: #1e3a8a;
        color: #bfdbfe;
    }
</style>
@endpush
