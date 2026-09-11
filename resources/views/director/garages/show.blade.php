@extends('layouts.app')

@section('title', $garage->name)

@section('content')
<div class="fleet-dashboard">
    <section class="fleet-page-heading">
        <div>
            <span class="fleet-eyebrow">{{ $company->name }}</span>
            <h1>{{ $garage->name }}</h1>
            <p>
                <code>{{ $garage->code }}</code>
                @if($garage->address) · {{ $garage->address }} @endif
            </p>
        </div>
        <div class="fleet-page-heading__actions">
            <a href="{{ route('director.garages') }}" class="fleet-button fleet-button--secondary">
                <i class="fas fa-arrow-left"></i> {{ __('messages.common.back') }}
            </a>
        </div>
    </section>

    <section class="fleet-kpi-grid">
        <article class="fleet-kpi-card">
            <span class="fleet-kpi-card__icon fleet-kpi-card__icon--blue"><i class="fas fa-bus"></i></span>
            <div>
                <span>{{ __('messages.dashboard.total_buses') }}</span>
                <strong>{{ $stats['total_buses'] }}</strong>
                <small>{{ __('messages.dashboard.active_vehicles', ['count' => $stats['active_buses']]) }}</small>
            </div>
        </article>
        <article class="fleet-kpi-card">
            <span class="fleet-kpi-card__icon fleet-kpi-card__icon--amber"><i class="fas fa-screwdriver-wrench"></i></span>
            <div>
                <span>{{ __('messages.dashboard.open_cards') }}</span>
                <strong>{{ $stats['open_complaints'] }}</strong>
                <small>{{ __('messages.dashboard.open_cards_desc') }}</small>
            </div>
        </article>
        <article class="fleet-kpi-card">
            <span class="fleet-kpi-card__icon fleet-kpi-card__icon--violet"><i class="fas fa-users"></i></span>
            <div>
                <span>{{ __('messages.nav.employees') }}</span>
                <strong>{{ $stats['total_employees'] }}</strong>
                <small>{{ __('messages.director.employees_label') }}</small>
            </div>
        </article>
        <article class="fleet-kpi-card">
            <span class="fleet-kpi-card__icon fleet-kpi-card__icon--rose"><i class="fas fa-id-card"></i></span>
            <div>
                <span>{{ __('messages.nav.drivers') }}</span>
                <strong>{{ $stats['total_drivers'] }}</strong>
                <small>{{ __('messages.director.drivers_label') }}</small>
            </div>
        </article>
    </section>

    <section class="fleet-panel">
        <header class="fleet-panel__header">
            <div>
                <span class="fleet-eyebrow">{{ __('messages.director.garage_info') }}</span>
                <h2>{{ __('messages.super_admin.garages.show_title') }}</h2>
            </div>
        </header>
        <div class="fleet-list">
            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-phone"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.super_admin.garages.phone') }}</strong>
                    <small>{{ $garage->phone ?? '—' }}</small>
                </span>
            </div>
            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-location-dot"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.super_admin.garages.address') }}</strong>
                    <small>{{ $garage->address ?? '—' }}</small>
                </span>
            </div>
            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-circle-check"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.common.status') }}</strong>
                    <small>
                        @if($garage->is_active)
                            <span class="fleet-status fleet-status--success">{{ __('messages.common.active') }}</span>
                        @else
                            <span class="fleet-status fleet-status--muted">{{ __('messages.common.inactive') }}</span>
                        @endif
                    </small>
                </span>
            </div>
            <div class="fleet-list__item">
                <span class="fleet-list__icon"><i class="fas fa-calendar"></i></span>
                <span class="fleet-list__content">
                    <strong>{{ __('messages.complaints.created') }}</strong>
                    <small>{{ $garage->created_at?->format('d.m.Y H:i') ?? '—' }}</small>
                </span>
            </div>
        </div>
    </section>
</div>
@endsection
