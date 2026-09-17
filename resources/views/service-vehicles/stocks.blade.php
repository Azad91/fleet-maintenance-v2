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

{{-- Filters --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('service-vehicles.stocks') }}" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="search" class="form-label fw-bold">
                    <i class="bi bi-search"></i> {{ __('messages.common.search') }}
                </label>
                <input type="text" name="search" id="search" class="form-control"
                       value="{{ $search }}"
                       placeholder="{{ __('messages.service_vehicles.stocks_search_placeholder') }}">
            </div>
            <div class="col-md-4">
                <label for="vehicle_id" class="form-label fw-bold">
                    <i class="bi bi-truck"></i> {{ __('messages.service_vehicles.title') }}
                </label>
                <select name="vehicle_id" id="vehicle_id" class="form-select">
                    <option value="">{{ __('messages.service_vehicles.stocks_all_vehicles') }}</option>
                    @foreach($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}" @selected($vehicleId === $vehicle->id)>
                            {{ $vehicle->name }}
                            @if($vehicle->plate_number) · {{ $vehicle->plate_number }} @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter"></i> {{ __('messages.common.filter') }}
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.service_vehicles.title') }}</th>
                        <th>{{ __('messages.warehouse.code') }}</th>
                        <th>{{ __('messages.warehouse.name') }}</th>
                        <th>{{ __('messages.warehouse.unit') }}</th>
                        <th class="text-end">{{ __('messages.warehouse.quantity') }}</th>
                        <th class="text-end">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stocks as $stock)
                        <tr>
                            <td>{{ $stocks->firstItem() + $loop->index }}</td>
                            <td>
                                @if($stock->serviceVehicle)
                                    <a href="{{ route('service-vehicles.show', $stock->serviceVehicle) }}" class="text-decoration-none">
                                        <strong>{{ $stock->serviceVehicle->name }}</strong>
                                    </a>
                                    @if($stock->serviceVehicle->plate_number)
                                        <br><code>{{ $stock->serviceVehicle->plate_number }}</code>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td><code>{{ $stock->code }}</code></td>
                            <td><strong>{{ $stock->name }}</strong></td>
                            <td>{{ $stock->unit ?? '—' }}</td>
                            <td class="text-end">
                                @if($stock->quantity > 0)
                                    <strong>{{ number_format($stock->quantity, 0, ',', '.') }}</strong>
                                @else
                                    <span class="badge bg-secondary">{{ __('messages.warehouse.out_of_stock') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($stock->serviceVehicle)
                                    <a href="{{ route('service-vehicles.show', $stock->serviceVehicle) }}"
                                       class="btn btn-sm btn-outline-primary" title="{{ __('messages.common.view') }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fa-2x mb-3 d-block" style="opacity: .3;"></i>
                                {{ $search || $vehicleId ? __('messages.warehouse.no_results', ['search' => $search]) : __('messages.service_vehicles.no_stock') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="d-flex justify-content-center mt-4">
    {{ $stocks->withQueryString()->links() }}
</div>
@endsection
