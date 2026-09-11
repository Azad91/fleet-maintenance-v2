@extends('layouts.app')

@section('title', __('messages.warehouse.details'))

@php
    use App\Enums\RoleEnum;

    $canEditWarehouse = auth()->user()?->isSuperAdmin()
        || auth()->user()?->hasGarageRole(array_merge([RoleEnum::ADMIN->value], RoleEnum::warehouseRoles()));
@endphp

@section('content')
<div class="container">
    <h1>📦 {{ __('messages.warehouse.details') }}</h1>

    <div class="section-title">📋 {{ __('messages.warehouse.basic_info') }}</div>
    <div class="field">
        <span class="label">ID:</span>
        <span class="value">{{ $warehouse->id }}</span>
    </div>
    <div class="field">
        <span class="label">{{ __('messages.warehouse.code') }}:</span>
        <span class="value"><strong>{{ $warehouse->code }}</strong></span>
    </div>
    <div class="field">
        <span class="label">{{ __('messages.warehouse.name') }}:</span>
        <span class="value">{{ $warehouse->name }}</span>
    </div>
    <div class="field">
        <span class="label">{{ __('messages.warehouse.category') }}:</span>
        <span class="value">{{ $warehouse->category ?? '-' }}</span>
    </div>
    <div class="field">
        <span class="label">{{ __('messages.warehouse.unit') }}:</span>
        <span class="value">{{ $warehouse->unit ?? '-' }}</span>
    </div>

    <div class="section-title">📊 {{ __('messages.warehouse.stock_info') }}</div>
    <div class="field">
        <span class="label">{{ __('messages.warehouse.quantity') }}:</span>
        <span class="value">
            <strong>{{ $warehouse->quantity }}</strong>
            @if($warehouse->quantity <= 0)
                <span class="status-empty">🔴 {{ __('messages.warehouse.out_of_stock') }}</span>
            @elseif($warehouse->quantity <= $warehouse->minimum_quantity)
                <span class="status-low">🟡 {{ __('messages.warehouse.low_stock') }}</span>
            @else
                <span class="status-good">🟢 {{ __('messages.warehouse.normal') }}</span>
            @endif
        </span>
    </div>
    <div class="field">
        <span class="label">{{ __('messages.warehouse.minimum_quantity') }}:</span>
        <span class="value">{{ $warehouse->minimum_quantity }}</span>
    </div>
    <div class="field">
        <span class="label">{{ __('messages.warehouse.price') }}:</span>
        <span class="value">{{ $warehouse->price ? number_format($warehouse->price, 2) . ' ₼' : '-' }}</span>
    </div>
    <div class="field">
        <span class="label">{{ __('messages.warehouse.total_price') }}:</span>
        <span class="value">
            <strong>{{ $warehouse->price ? number_format($warehouse->quantity * $warehouse->price, 2) . ' ₼' : '-' }}</strong>
        </span>
    </div>

    <div class="section-title">🏢 {{ __('messages.warehouse.supplier_section') }}</div>
    <div class="field">
        <span class="label">{{ __('messages.warehouse.supplier') }}:</span>
        <span class="value">{{ $warehouse->supplier ?? '-' }}</span>
    </div>

    <div class="section-title">📝 {{ __('messages.warehouse.notes_section') }}</div>
    <div class="field">
        <span class="label">{{ __('messages.common.notes') }}:</span>
        <span class="value">{{ $warehouse->notes ?? '-' }}</span>
    </div>

    <div class="field">
        <span class="label">{{ __('messages.complaints.created') }}:</span>
        <span class="value">{{ $warehouse->created_at }}</span>
    </div>
    <div class="field">
        <span class="label">{{ __('messages.complaints.last_updated') }}:</span>
        <span class="value">{{ $warehouse->updated_at }}</span>
    </div>

    <br>
    @if($canEditWarehouse)
        <a href="{{ route('warehouses.edit', $warehouse) }}" class="btn btn-warning">
            ✏️ {{ __('messages.common.edit') }}
        </a>
    @endif
    <a href="{{ route('warehouses.index') }}" class="btn btn-secondary">
        ⬅ {{ __('messages.common.back') }}
    </a>
</div>
@endsection
