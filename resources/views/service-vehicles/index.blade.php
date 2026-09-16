@extends('layouts.app')

@section('title', __('messages.service_vehicles.title'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.nav.data') }}</span>
        <h1 class="mb-1">🚐 {{ __('messages.service_vehicles.title') }}</h1>
        <p class="text-muted mb-0">{{ __('messages.service_vehicles.subtitle') }}</p>
    </div>
    <a href="{{ route('service-vehicles.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> {{ __('messages.service_vehicles.new') }}
    </a>
</div>

@if(session('success'))
    <div class="fleet-alert fleet-alert--success">
        <i class="fas fa-circle-check"></i>{{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="fleet-alert fleet-alert--error">
        <i class="fas fa-circle-exclamation"></i>{{ session('error') }}
    </div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.service_vehicles.name') }}</th>
                        <th>{{ __('messages.service_vehicles.plate_number') }}</th>
                        <th>{{ __('messages.service_vehicles.driver_name') }}</th>
                        <th>{{ __('messages.service_vehicles.phone') }}</th>
                        <th>{{ __('messages.common.status') }}</th>
                        <th class="text-end">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicles as $vehicle)
                        <tr>
                            <td>{{ $vehicles->firstItem() + $loop->index }}</td>
                            <td>
                                <a href="{{ route('service-vehicles.show', $vehicle) }}" class="text-decoration-none">
                                    <strong>{{ $vehicle->name }}</strong>
                                </a>
                            </td>
                            <td>
                                @if($vehicle->plate_number)
                                    <code>{{ $vehicle->plate_number }}</code>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $vehicle->driver_name ?? '—' }}</td>
                            <td>{{ $vehicle->phone ?? '—' }}</td>
                            <td>
                                @if($vehicle->is_active)
                                    <span class="badge text-bg-success">{{ __('messages.common.active') }}</span>
                                @else
                                    <span class="badge text-bg-secondary">{{ __('messages.common.inactive') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('service-vehicles.show', $vehicle) }}" class="btn btn-sm btn-outline-primary" title="{{ __('messages.common.view') }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('service-vehicles.edit', $vehicle) }}" class="btn btn-sm btn-outline-warning" title="{{ __('messages.common.edit') }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('service-vehicles.destroy', $vehicle) }}" method="POST" style="display:inline" onsubmit="return confirm('{{ __('messages.service_vehicles.delete_confirm') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('messages.common.delete') }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-truck fa-2x mb-3 d-block" style="opacity: .3;"></i>
                                {{ __('messages.service_vehicles.no_vehicles') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="d-flex justify-content-center mt-4">
    {{ $vehicles->withQueryString()->links() }}
</div>
@endsection
