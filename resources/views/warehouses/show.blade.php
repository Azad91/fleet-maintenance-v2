@extends('layouts.app')

@section('title', __('messages.warehouse.details'))

@php
    use App\Enums\RoleEnum;

    $canEditWarehouse = auth()->user()?->isSuperAdmin()
        || auth()->user()?->hasGarageRole(array_merge(
            [RoleEnum::ADMIN->value],
            RoleEnum::warehouseRoles()
        ));

    // Stock health classification — drives the badge color and the
    // "out of stock" / "low stock" / "normal" label.
    if ($warehouse->quantity <= 0) {
        $stockState = 'out';
        $stockLabel = __('messages.warehouse.out_of_stock');
        $stockColor = 'fleet-status--muted';
    } elseif ($warehouse->quantity <= $warehouse->minimum_quantity) {
        $stockState = 'low';
        $stockLabel = __('messages.warehouse.low_stock');
        $stockColor = 'fleet-status--warning';
    } else {
        $stockState = 'normal';
        $stockLabel = __('messages.warehouse.normal');
        $stockColor = 'fleet-status--success';
    }

    $totalPrice = $warehouse->price
        ? $warehouse->quantity * $warehouse->price
        : null;
@endphp

@section('content')
<div class="fleet-dashboard">
    {{-- ─── Page Heading ─── --}}
    <section class="fleet-page-heading">
        <div>
            <span class="fleet-eyebrow">{{ __('messages.nav.warehouses') }}</span>
            <h1>{{ $warehouse->name }}</h1>
            <p>
                <code>{{ $warehouse->code }}</code>
                @if($warehouse->is_quarantine)
                    · <span class="fleet-status fleet-status--warning">
                        <i class="fas fa-shield-exclamation"></i>
                        {{ __('messages.warehouse.quarantine') }}
                    </span>
                @endif
            </p>
        </div>
        <div class="fleet-page-heading__actions">
            <a href="{{ route('warehouses.index') }}" class="fleet-button fleet-button--secondary">
                <i class="fas fa-arrow-left"></i> {{ __('messages.common.back') }}
            </a>
            @if($canEditWarehouse)
                <a href="{{ route('warehouses.edit', $warehouse) }}" class="fleet-button fleet-button--primary">
                    <i class="fas fa-pencil"></i> {{ __('messages.common.edit') }}
                </a>
            @endif
        </div>
    </section>

    {{-- ─── KPI Cards ─── --}}
    <section class="fleet-kpi-grid mb-4">
        <article class="fleet-kpi-card">
            <span class="fleet-kpi-card__icon fleet-kpi-card__icon--blue">
                <i class="fas fa-boxes-stacked"></i>
            </span>
            <div>
                <span>{{ __('messages.warehouse.quantity') }}</span>
                <strong>{{ number_format($warehouse->quantity, 0, ',', '.') }}</strong>
                <small>
                    <span class="fleet-status {{ $stockColor }}">
                        {{ $stockLabel }}
                    </span>
                </small>
            </div>
        </article>

        <article class="fleet-kpi-card">
            <span class="fleet-kpi-card__icon fleet-kpi-card__icon--amber">
                <i class="fas fa-triangle-exclamation"></i>
            </span>
            <div>
                <span>{{ __('messages.warehouse.minimum_quantity') }}</span>
                <strong>{{ number_format($warehouse->minimum_quantity, 0, ',', '.') }}</strong>
                <small>{{ $warehouse->unit ?? '—' }}</small>
            </div>
        </article>

        <article class="fleet-kpi-card">
            <span class="fleet-kpi-card__icon fleet-kpi-card__icon--violet">
                <i class="fas fa-money-bill-wave"></i>
            </span>
            <div>
                <span>{{ __('messages.warehouse.price') }}</span>
                <strong>{{ $warehouse->price ? number_format($warehouse->price, 2) . ' ₼' : '—' }}</strong>
                <small>{{ __('messages.warehouse.price_hint') }}</small>
            </div>
        </article>

        <article class="fleet-kpi-card">
            <span class="fleet-kpi-card__icon fleet-kpi-card__icon--rose">
                <i class="fas fa-coins"></i>
            </span>
            <div>
                <span>{{ __('messages.warehouse.total_price') }}</span>
                <strong>{{ $totalPrice !== null ? number_format($totalPrice, 2) . ' ₼' : '—' }}</strong>
                <small>{{ __('messages.common.total') }}</small>
            </div>
        </article>
    </section>

    {{-- ─── Item Info Panel ─── --}}
    <section class="fleet-panel">
        <header class="fleet-panel__header">
            <div>
                <span class="fleet-eyebrow">{{ __('messages.warehouse.details') }}</span>
                <h2>{{ __('messages.warehouse.basic_info') }}</h2>
            </div>
        </header>

        <div class="fleet-list">
            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-hashtag"></i></span>
                <span class="fleet-list__content">
                    <strong>ID</strong>
                    <small>{{ $warehouse->id }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-barcode"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.warehouse.code') }}</strong>
                    <small>{{ $warehouse->code }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-tag"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.warehouse.name') }}</strong>
                    <small>{{ $warehouse->name }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-folder"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.warehouse.category') }}</strong>
                    <small>{{ $warehouse->category ?? '—' }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-ruler"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.warehouse.unit') }}</strong>
                    <small>{{ $warehouse->unit ?? '—' }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-truck"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.warehouse.supplier') }}</strong>
                    <small>{{ $warehouse->supplier ?? '—' }}</small>
                </span>
            </div>

            @if($warehouse->notes)
                <div class="fleet-list__item">
                    <span class="fleet-list__icon"><i class="fas fa-comment"></i></span>
                    <span class="fleet-list__content">
                        <strong>{{ __('messages.common.notes') }}</strong>
                        <small>{{ $warehouse->notes }}</small>
                    </span>
                </div>
            @endif

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-calendar-plus"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.complaints.created') }}</strong>
                    <small>{{ $warehouse->created_at?->format('d.m.Y H:i') ?? '—' }}</small>
                </span>
            </div>

            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-calendar-check"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.complaints.last_updated') }}</strong>
                    <small>{{ $warehouse->updated_at?->format('d.m.Y H:i') ?? '—' }}</small>
                </span>
            </div>
        </div>
    </section>
</div>
@endsection