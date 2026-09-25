@extends('layouts.app')

@section('title', __('messages.transfers.details') . ' #' . $transfer->id)

@php
    use App\Enums\TransferStatus;
    use App\Services\GarageContext;

    $currentGarageId = GarageContext::getGarageId();
    $user = auth()->user();

    $isSource      = $transfer->isSource($currentGarageId);
    $isDestination = $transfer->isDestination($currentGarageId);

    $canDispatch = $user->can('dispatch', $transfer);
    $canReceive  = $user->can('receive', $transfer);
    $canReject   = $user->can('reject', $transfer);
    $canResolve  = $user->can('resolve', $transfer);
    $canCancel   = $user->can('cancel', $transfer);
@endphp

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.nav.warehouses') }}</span>
        <h1 class="mb-1">
            🔁 {{ __('messages.transfers.details') }} #{{ $transfer->id }}
            <span class="badge bg-{{ $transfer->status->bootstrapColor() }} ms-2">
                {{ $transfer->status->label() }}
            </span>
        </h1>
        <p class="text-muted mb-0">
            {{ $transfer->type->label() }}
            · {{ $transfer->created_at?->format('d.m.Y H:i') }}
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('warehouse-transfers.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
        </a>

        @if($canCancel)
            <form action="{{ route('warehouse-transfers.cancel', $transfer) }}" method="POST"
                  onsubmit="return confirm('{{ __('messages.transfers.delete_confirm') }}')">
                @csrf
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-x-circle"></i> {{ __('messages.transfers.action_cancel') }}
                </button>
            </form>
        @endif

        @if($canDispatch)
            <form action="{{ route('warehouse-transfers.dispatch', $transfer) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send"></i> {{ __('messages.transfers.action_dispatch') }}
                </button>
            </form>
        @endif

        @if($canReject)
            <button type="button" class="btn btn-outline-danger"
                    data-bs-toggle="modal" data-bs-target="#rejectModal">
                <i class="bi bi-x-lg"></i> {{ __('messages.transfers.action_reject') }}
            </button>
        @endif

        @if($canReceive)
            <button type="button" class="btn btn-success"
                    data-bs-toggle="modal" data-bs-target="#receiveModal">
                <i class="bi bi-check-lg"></i> {{ __('messages.transfers.action_receive') }}
            </button>
        @endif

        @if($canResolve)
            <button type="button" class="btn btn-warning"
                    data-bs-toggle="modal" data-bs-target="#resolveModal">
                <i class="bi bi-tools"></i> {{ __('messages.transfers.action_resolve') }}
            </button>
        @endif
    </div>
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

{{-- ─── Transfer Lifecycle Timeline ─── --}}
@php
    $status = $transfer->status;
    $steps = [
        'draft' => [
            'label' => __('messages.transfers.action_dispatch'),
            'icon'  => 'fa-file-pen',
            'done'  => ! $status->isDraft(),
            'active' => $status->isDraft(),
        ],
        'dispatched' => [
            'label' => __('messages.transfers.action_receive'),
            'icon'  => 'fa-truck-fast',
            'done'  => in_array($status->value, ['received', 'disputed', 'resolved'], true),
            'active' => $status->isDispatched(),
        ],
        'received' => [
            'label' => __('messages.transfers.received'),
            'icon'  => 'fa-circle-check',
            'done'  => $status->isReceived(),
            'active' => in_array($status->value, ['received', 'disputed', 'resolved'], true),
        ],
    ];
@endphp

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
            @foreach($steps as $key => $step)
                <div class="text-center flex-fill">
                    <div class="timeline-circle {{ $step['done'] ? 'is-done' : ($step['active'] ? 'is-active' : '') }}">
                        <i class="fas {{ $step['icon'] }}"></i>
                    </div>
                    <div class="mt-2 small fw-bold {{ $step['active'] ? 'text-primary' : 'text-muted' }}">
                        {{ $step['label'] }}
                    </div>
                </div>

                @if(! $loop->last)
                    <div class="timeline-bar {{ $step['done'] ? 'is-done' : '' }}"></div>
                @endif
            @endforeach
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- ─── Source / Destination card ─── --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">📍 {{ __('messages.transfers.from_garage') }}</h5>
            </div>
            <div class="card-body">
                <strong>{{ $transfer->fromGarage?->name ?? '—' }}</strong>
                @if($transfer->fromGarage?->code)
                    <code class="ms-2">{{ $transfer->fromGarage->code }}</code>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    @if($transfer->type->isReturnToQuarantine())
                        ⚠️ {{ __('messages.transfers.destination_quarantine') }}
                    @else
                        🎯 {{ __('messages.transfers.to_garage') }}
                    @endif
                </h5>
            </div>
            <div class="card-body">
                @if($transfer->type->isReturnToQuarantine())
                    <span class="badge bg-warning text-dark">
                        <i class="bi bi-shield-exclamation"></i>
                        {{ __('messages.warehouse.quarantine') }}
                    </span>
                    <p class="mb-0 mt-2 text-muted">
                        {{ __('messages.transfers.quarantine_hint') }}
                    </p>
                @elseif($transfer->toGarage)
                    <strong>{{ $transfer->toGarage->name }}</strong>
                    @if($transfer->toGarage->code)
                        <code class="ms-2">{{ $transfer->toGarage->code }}</code>
                    @endif
                @elseif($transfer->toServiceVehicle)
                    🚐 <strong>{{ $transfer->toServiceVehicle->name }}</strong>
                    @if($transfer->toServiceVehicle->plate_number)
                        <code class="ms-2">{{ $transfer->toServiceVehicle->plate_number }}</code>
                    @endif
                @else
                    —
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ─── Lifecycle card ─── --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">📅 {{ __('messages.complaints.info') }}</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @if($transfer->dispatched_at)
                <div class="col-md-4">
                    <small class="text-muted d-block">{{ __('messages.transfers.dispatched') }}</small>
                    <strong>{{ $transfer->dispatched_at->format('d.m.Y H:i') }}</strong>
                    @if($transfer->dispatchedBy)
                        <small class="d-block text-muted">by {{ $transfer->dispatchedBy->name }}</small>
                    @endif
                </div>
            @endif

            @if($transfer->received_at)
                <div class="col-md-4">
                    <small class="text-muted d-block">{{ __('messages.transfers.received') }}</small>
                    <strong>{{ $transfer->received_at->format('d.m.Y H:i') }}</strong>
                    @if($transfer->receivedBy)
                        <small class="d-block text-muted">by {{ $transfer->receivedBy->name }}</small>
                    @endif
                </div>
            @endif

            @if($transfer->resolved_at)
                <div class="col-md-4">
                    <small class="text-muted d-block">{{ __('messages.transfers.resolved') }}</small>
                    <strong>{{ $transfer->resolved_at->format('d.m.Y H:i') }}</strong>
                    @if($transfer->resolvedBy)
                        <small class="d-block text-muted">by {{ $transfer->resolvedBy->name }}</small>
                    @endif
                    @if($transfer->resolution)
                        <small class="d-block">
                            <span class="badge bg-secondary">{{ $transfer->resolution }}</span>
                        </small>
                    @endif
                </div>
            @endif
        </div>

        @if($transfer->notes)
            <div class="mt-3">
                <small class="text-muted d-block">{{ __('messages.transfers.notes') }}</small>
                <strong>{{ $transfer->notes }}</strong>
            </div>
        @endif

        @if($transfer->discrepancy_notes)
            <div class="mt-3">
                <small class="text-danger d-block">{{ __('messages.transfers.discrepancy') }}</small>
                <strong>{{ $transfer->discrepancy_notes }}</strong>
            </div>
        @endif
    </div>
</div>

{{-- ─── Items ─── --}}
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">📦 {{ __('messages.transfers.items') }}</h5>
    </div>
    <div class="card-body p-0">
        @include('warehouse-transfers.partials.items-table')
    </div>
</div>

{{-- ═══════════════════════════════════════════════════ --}}
{{-- RECEIVE MODAL                                       --}}
{{-- ═══════════════════════════════════════════════════ --}}
@if($canReceive)
    <div class="modal fade" id="receiveModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('warehouse-transfers.receive', $transfer) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-check-circle"></i> {{ __('messages.transfers.receive_modal_title') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            {{ __('messages.transfers.received_qty') }} — {{ __('messages.transfers.items') }}
                        </div>
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('messages.transfers.item_code') }}</th>
                                    <th class="text-end">{{ __('messages.transfers.declared_qty') }}</th>
                                    <th style="width: 150px;">{{ __('messages.transfers.received_qty') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transfer->items as $item)
                                    <tr>
                                        <td>
                                            <code>{{ $item->warehouse?->code ?? '—' }}</code>
                                            — {{ $item->warehouse?->name ?? '—' }}
                                        </td>
                                        <td class="text-end">{{ $item->declared_quantity }}</td>
                                        <td>
                                            <input type="number"
                                                   name="received[{{ $item->id }}]"
                                                   class="form-control form-control-sm"
                                                   min="0"
                                                   value="{{ $item->declared_quantity }}"
                                                   required>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            {{ __('messages.common.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg"></i> {{ __('messages.transfers.action_receive') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════ --}}
{{-- REJECT MODAL                                        --}}
{{-- ═══════════════════════════════════════════════════ --}}
@if($canReject)
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('warehouse-transfers.reject', $transfer) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-x-circle"></i> {{ __('messages.transfers.reject_modal_title') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            {{ __('messages.transfers.action_reject') }} — {{ __('messages.transfers.items') }}
                        </div>
                        <label for="reason" class="form-label fw-bold">
                            {{ __('messages.transfers.reject_reason') }}
                        </label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" maxlength="2000"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            {{ __('messages.common.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-lg"></i> {{ __('messages.transfers.action_reject') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════ --}}
{{-- RESOLVE MODAL                                       --}}
{{-- ═══════════════════════════════════════════════════ --}}
@if($canResolve)
    <div class="modal fade" id="resolveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('warehouse-transfers.resolve', $transfer) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-tools"></i> {{ __('messages.transfers.resolve_modal_title') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="resolution"
                                   id="resolution_retransfer" value="retransfer" checked>
                            <label class="form-check-label" for="resolution_retransfer">
                                {{ __('messages.transfers.resolve_retransfer') }}
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="resolution"
                                   id="resolution_loss" value="loss_accepted">
                            <label class="form-check-label" for="resolution_loss">
                                {{ __('messages.transfers.resolve_loss') }}
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            {{ __('messages.common.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check-lg"></i> {{ __('messages.transfers.action_resolve') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection
@push('styles')
<style>
    .timeline-circle {
        width: 52px;
        height: 52px;
        margin: 0 auto;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #e5e7eb;
        color: #9ca3af;
        font-size: 20px;
        transition: all 0.3s;
    }
    .timeline-circle.is-active {
        background: #2563eb;
        color: #fff;
        box-shadow: 0 0 0 6px rgba(37, 99, 235, 0.15);
    }
    .timeline-circle.is-done {
        background: #10b981;
        color: #fff;
    }
    .timeline-bar {
        flex: 1;
        height: 3px;
        margin-top: 25px;
        background: #e5e7eb;
        border-radius: 3px;
    }
    .timeline-bar.is-done {
        background: #10b981;
    }
    html[data-fleet-theme="dark"] .timeline-circle {
        background: #2c3c51;
        color: #a8b9ce;
    }
    html[data-fleet-theme="dark"] .timeline-bar {
        background: #2c3c51;
    }
</style>
@endpush
