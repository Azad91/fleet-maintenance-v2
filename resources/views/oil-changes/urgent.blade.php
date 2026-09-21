@extends('layouts.app')

@section('title', __('messages.oil_change.urgent_title'))

@php
    use App\Enums\OilType;
@endphp

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.nav.operations') }}</span>
        <h1 class="mb-1">🚨 {{ __('messages.oil_change.urgent_title') }}</h1>
        <p class="text-muted mb-0">{{ __('messages.oil_change.urgent_subtitle') }}</p>
    </div>
    <a href="{{ route('oil-changes.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> {{ __('messages.common.back') }}
    </a>
</div>

{{-- KPI cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card" style="border-left: 4px solid #dc2626;">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center rounded-3"
                     style="width: 52px; height: 52px; background: #fef2f2; color: #dc2626;">
                    <i class="fas fa-circle-exclamation" style="font-size: 22px;"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size: 12px; font-weight: 700; letter-spacing: .5px;">
                        {{ __('messages.oil_change.status.overdue') }}
                    </div>
                    <div class="fw-bold" style="font-size: 28px; color: #dc2626; letter-spacing: -0.5px;">
                        {{ $overdueCount }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card" style="border-left: 4px solid #ea580c;">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center rounded-3"
                     style="width: 52px; height: 52px; background: #fff1f2; color: #ea580c;">
                    <i class="fas fa-triangle-exclamation" style="font-size: 22px;"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size: 12px; font-weight: 700; letter-spacing: .5px;">
                        {{ __('messages.oil_change.status.critical') }}
                    </div>
                    <div class="fw-bold" style="font-size: 28px; color: #ea580c; letter-spacing: -0.5px;">
                        {{ $criticalCount }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($rows->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-circle-check fa-3x mb-3 d-block" style="color: #10b981; opacity: .5;"></i>
            <h5 class="text-muted mb-0">{{ __('messages.oil_change.urgent_empty') }}</h5>
        </div>
    </div>
@else
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('messages.oil_change.bus') }}</th>
                            <th>{{ __('messages.oil_change.type') }}</th>
                            <th class="text-end">{{ __('messages.oil_change.current_km') }}</th>
                            <th class="text-end">{{ __('messages.oil_change.next_due_km') }}</th>
                            <th class="text-end">{{ __('messages.oil_change.remaining_km') }}</th>
                            <th class="text-center">{{ __('messages.oil_change.column_status') }}</th>
                            <th class="text-end">{{ __('messages.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            @foreach($row['urgent_statuses'] as $status)
                                <tr>
                                    <td>
                                        <a href="{{ route('oil-changes.show', $row['bus']) }}"
                                           class="text-decoration-none">
                                            <strong>{{ $row['bus']->dqn }}</strong>
                                        </a>
                                        @if($row['bus']->route_number)
                                            <br>
                                            <small class="text-muted">
                                                {{ __('messages.daily_km.route_label', ['route' => $row['bus']->route_number]) }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        <span style="font-size: 14px;">
                                            {{ $status->type->icon() }} {{ $status->type->label() }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <strong>{{ number_format($status->currentKm, 0, '', '.') }}</strong> km
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($status->nextDueKm, 0, '', '.') }} km
                                    </td>
                                    <td class="text-end">
                                        @if($status->isOverdue())
                                            <span class="text-danger fw-bold" style="font-size: 15px;">
                                                −{{ number_format($status->overdueByKm(), 0, '', '.') }} km
                                            </span>
                                        @else
                                            <span style="color: #ea580c; font-weight: 700; font-size: 15px;">
                                                +{{ number_format($status->remainingKm, 0, '', '.') }} km
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $status->bootstrapColor() }}">
                                            {{ $status->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('oil-changes.create', ['bus_id' => $row['bus']->id, 'type' => $status->type->value]) }}"
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-plus"></i> {{ __('messages.oil_change.add_change') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
@endsection
