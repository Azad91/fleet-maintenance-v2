@extends('layouts.app')

@section('title', __('messages.director.dashboard_title'))

@section('content')
<div class="fleet-dashboard">
    <section class="fleet-page-heading">
        <div>
            <span class="fleet-eyebrow">{{ __('messages.director.eyebrow') }}</span>
            <h1>{{ $company->name }}</h1>
            <p>{{ __('messages.director.subtitle') }}</p>
        </div>
        <div class="fleet-page-heading__actions">
            <a href="{{ route('director.garages') }}" class="fleet-button fleet-button--primary">
                <i class="fas fa-warehouse"></i> {{ __('messages.director.view_garages') }}
            </a>
        </div>
    </section>

    <section class="fleet-kpi-grid" aria-label="{{ __('messages.dashboard.kpi_label') }}">
        <article class="fleet-kpi-card">
            <span class="fleet-kpi-card__icon fleet-kpi-card__icon--blue"><i class="fas fa-warehouse"></i></span>
            <div>
                <span>{{ __('messages.director.total_garages') }}</span>
                <strong>{{ $stats['total_garages'] }}</strong>
                <small>{{ __('messages.director.active_count', ['count' => $stats['active_garages']]) }}</small>
            </div>
        </article>
        <article class="fleet-kpi-card">
            <span class="fleet-kpi-card__icon fleet-kpi-card__icon--violet"><i class="fas fa-bus"></i></span>
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
            <span class="fleet-kpi-card__icon fleet-kpi-card__icon--rose"><i class="fas fa-boxes-stacked"></i></span>
            <div>
                <span>{{ __('messages.dashboard.stock_quantity') }}</span>
                <strong>{{ $stats['total_warehouse_items'] }}</strong>
                <small>{{ __('messages.dashboard.stock_quantity_desc') }}</small>
            </div>
        </article>
    </section>

    <section class="fleet-dashboard-grid">
        <article class="fleet-panel fleet-panel--wide">
            <header class="fleet-panel__header">
                <div>
                    <span class="fleet-eyebrow">{{ __('messages.director.garages_eyebrow') }}</span>
                    <h2>{{ __('messages.director.garages_title') }}</h2>
                </div>
                <a href="{{ route('director.garages') }}" class="fleet-text-link">
                    {{ __('messages.dashboard.view_all') }} <i class="fas fa-arrow-right"></i>
                </a>
            </header>
            <div class="fleet-table-wrap">
                <table class="fleet-table">
                    <thead>
                        <tr>
                            <th>{{ __('messages.super_admin.garages.name') }}</th>
                            <th>{{ __('messages.super_admin.garages.code') }}</th>
                            <th class="text-center">{{ __('messages.nav.buses') }}</th>
                            <th class="text-center">{{ __('messages.dashboard.open_cards') }}</th>
                            <th>{{ __('messages.common.status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($garages as $garage)
                            <tr>
                                <td><strong>{{ $garage->name }}</strong></td>
                                <td><code>{{ $garage->code }}</code></td>
                                <td class="text-center">{{ $garage->buses_count }}</td>
                                <td class="text-center">{{ $garage->open_complaints_count }}</td>
                                <td>
                                    @if($garage->is_active)
                                        <span class="fleet-status fleet-status--success">{{ __('messages.common.active') }}</span>
                                    @else
                                        <span class="fleet-status fleet-status--muted">{{ __('messages.common.inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('director.garages.show', $garage) }}" class="fleet-text-link">
                                        {{ __('messages.common.view') }} <i class="fas fa-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="fleet-table__empty">{{ __('messages.director.no_garages') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</div>
@endsection
