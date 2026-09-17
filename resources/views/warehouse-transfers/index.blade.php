@extends('layouts.app')

@section('title', __('messages.transfers.title'))

@php
    use App\Enums\TransferStatus;
    use App\Enums\TransferType;
@endphp

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.nav.warehouses') }}</span>
        <h1 class="mb-1">🔁 {{ __('messages.transfers.title') }}</h1>
        <p class="text-muted mb-0">{{ __('messages.transfers.subtitle') }}</p>
    </div>
    <a href="{{ route('warehouse-transfers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> {{ __('messages.transfers.new') }}
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

{{-- Filters --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('warehouse-transfers.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="direction" class="form-label fw-bold">
                    {{ __('messages.transfers.direction') }}
                </label>
                <select name="direction" id="direction" class="form-select" onchange="this.form.submit()">
                    <option value="all"      @selected(($direction ?? 'all') === 'all')>{{ __('messages.transfers.direction_all') }}</option>
                    <option value="outbound" @selected(($direction ?? 'all') === 'outbound')>{{ __('messages.transfers.direction_outbound') }}</option>
                    <option value="inbound"  @selected(($direction ?? 'all') === 'inbound')>{{ __('messages.transfers.direction_inbound') }}</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label fw-bold">
                    {{ __('messages.transfers.status') }}
                </label>
                <select name="status" id="status" class="form-select" onchange="this.form.submit()">
                    <option value="">{{ __('messages.common.select') }}</option>
                    @foreach(TransferStatus::cases() as $s)
                        <option value="{{ $s->value }}" @selected(($status ?? '') === $s->value)>
                            {{ $s->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <a href="{{ route('warehouse-transfers.index') }}" class="btn btn-secondary w-100">
                    <i class="bi bi-x-circle"></i> {{ __('messages.common.reset') }}
                </a>
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
                        <th>{{ __('messages.transfers.type') }}</th>
                        <th>{{ __('messages.transfers.from_garage') }}</th>
                        <th>{{ __('messages.transfers.to_garage') }}</th>
                        <th class="text-end">{{ __('messages.transfers.declared_total') }}</th>
                        <th>{{ __('messages.transfers.status') }}</th>
                        <th>{{ __('messages.complaints.created') }}</th>
                        <th class="text-end">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $transfer)
                        <tr>
                            <td>
                                <a href="{{ route('warehouse-transfers.show', $transfer) }}" class="text-decoration-none">
                                    <strong>#{{ $transfer->id }}</strong>
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark">
                                    {{ $transfer->type->label() }}
                                </span>
                            </td>
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
                            <td class="text-end">
                                <strong>{{ $transfer->declared_total }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-{{ $transfer->status->bootstrapColor() }}">
                                    {{ $transfer->status->label() }}
                                </span>
                            </td>
                            <td>{{ $transfer->created_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('warehouse-transfers.show', $transfer) }}"
                                   class="btn btn-sm btn-outline-primary"
                                   title="{{ __('messages.common.view') }}">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-arrow-left-right fa-2x mb-3 d-block" style="opacity: .3;"></i>
                                {{ __('messages.transfers.no_transfers') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="d-flex justify-content-center mt-4">
    {{ $transfers->withQueryString()->links() }}
</div>
@endsection
