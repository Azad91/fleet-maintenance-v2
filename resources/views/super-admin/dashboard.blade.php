@extends('layouts.app')

@section('title', __('messages.super_admin.dashboard.title'))

@section('content')
<div class="fleet-dashboard">
    <section class="fleet-page-heading">
        <div>
            <span class="fleet-eyebrow">{{ __('messages.super_admin.eyebrow') }}</span>
            <h1>{{ __('messages.super_admin.dashboard.title') }}</h1>
            <p>{{ __('messages.super_admin.dashboard.subtitle') }}</p>
        </div>
        <div class="fleet-page-heading__actions">
            <a href="{{ route('super-admin.companies.index') }}" class="fleet-button fleet-button--secondary">
                <i class="fas fa-building"></i> {{ __('messages.nav.companies') }}
            </a>
            <a href="{{ route('super-admin.garages.index') }}" class="fleet-button fleet-button--primary">
                <i class="fas fa-warehouse"></i> {{ __('messages.nav.garages') }}
            </a>
        </div>
    </section>

    {{-- KPI GRID --}}
    <section class="fleet-kpi-grid">
        <article class="fleet-kpi-card">
            <span class="fleet-kpi-card__icon fleet-kpi-card__icon--blue"><i class="fas fa-building"></i></span>
            <div>
                <span>{{ __('messages.super_admin.dashboard.companies') }}</span>
                <strong>{{ $stats['companies_total'] }}</strong>
                <small>{{ __('messages.super_admin.dashboard.active_count', ['count' => $stats['companies_active']]) }}</small>
            </div>
        </article>
        <article class="fleet-kpi-card">
            <span class="fleet-kpi-card__icon fleet-kpi-card__icon--violet"><i class="fas fa-warehouse"></i></span>
            <div>
                <span>{{ __('messages.super_admin.dashboard.garages') }}</span>
                <strong>{{ $stats['garages_total'] }}</strong>
                <small>{{ __('messages.super_admin.dashboard.active_count', ['count' => $stats['garages_active']]) }}</small>
            </div>
        </article>
        <article class="fleet-kpi-card">
            <span class="fleet-kpi-card__icon fleet-kpi-card__icon--amber"><i class="fas fa-users"></i></span>
            <div>
                <span>{{ __('messages.super_admin.dashboard.users') }}</span>
                <strong>{{ $stats['users_total'] }}</strong>
                <small>{{ __('messages.super_admin.dashboard.active_count', ['count' => $stats['users_active']]) }}</small>
            </div>
        </article>
        <article class="fleet-kpi-card">
            <span class="fleet-kpi-card__icon fleet-kpi-card__icon--rose"><i class="fas fa-bus"></i></span>
            <div>
                <span>{{ __('messages.super_admin.dashboard.total_buses') }}</span>
                <strong>{{ $stats['buses_total'] }}</strong>
                <small>{{ __('messages.super_admin.dashboard.open_cards_count', ['count' => $stats['complaints_open']]) }}</small>
            </div>
        </article>
    </section>

    {{-- RECENT COMPANIES + RECENT GARAGES --}}
    <section class="fleet-dashboard-grid">
        <article class="fleet-panel">
            <header class="fleet-panel__header">
                <div>
                    <span class="fleet-eyebrow">{{ __('messages.super_admin.dashboard.eyebrow_recent') }}</span>
                    <h2>{{ __('messages.super_admin.dashboard.recent_companies') }}</h2>
                </div>
                <a href="{{ route('super-admin.companies.index') }}" class="fleet-text-link">
                    {{ __('messages.dashboard.view_all') }} <i class="fas fa-arrow-right"></i>
                </a>
            </header>
            <div class="fleet-list">
                @forelse($recentCompanies as $company)
                    <a href="{{ route('super-admin.companies.show', $company) }}" class="fleet-list__item text-decoration-none">
                        <span class="fleet-list__icon"><i class="fas fa-building"></i></span>
                        <span class="fleet-list__content">
                            <strong>{{ $company->name }}</strong>
                            <small>{{ $company->slug }} · {{ $company->email ?? '—' }}</small>
                        </span>
                        <span class="fleet-status {{ $company->is_active ? 'fleet-status--success' : 'fleet-status--muted' }}">
                            {{ $company->is_active ? __('messages.common.active') : __('messages.common.inactive') }}
                        </span>
                        <i class="fas fa-chevron-right fleet-list__arrow"></i>
                    </a>
                @empty
                    <div class="fleet-empty-state">
                        <i class="fas fa-building"></i>
                        <p>{{ __('messages.common.no_data') }}</p>
                    </div>
                @endforelse
            </div>
        </article>

        <article class="fleet-panel">
            <header class="fleet-panel__header">
                <div>
                    <span class="fleet-eyebrow">{{ __('messages.super_admin.dashboard.eyebrow_recent') }}</span>
                    <h2>{{ __('messages.super_admin.dashboard.recent_garages') }}</h2>
                </div>
                <a href="{{ route('super-admin.garages.index') }}" class="fleet-text-link">
                    {{ __('messages.dashboard.view_all') }} <i class="fas fa-arrow-right"></i>
                </a>
            </header>
            <div class="fleet-list">
                @forelse($recentGarages as $garage)
                    <a href="{{ route('super-admin.garages.show', $garage) }}" class="fleet-list__item text-decoration-none">
                        <span class="fleet-list__icon"><i class="fas fa-warehouse"></i></span>
                        <span class="fleet-list__content">
                            <strong>{{ $garage->name }}</strong>
                            <small>{{ $garage->company?->name ?? '—' }} · {{ $garage->buses_count }} {{ __('messages.nav.buses') }}</small>
                        </span>
                        <span class="fleet-status {{ $garage->is_active ? 'fleet-status--success' : 'fleet-status--muted' }}">
                            {{ $garage->is_active ? __('messages.common.active') : __('messages.common.inactive') }}
                        </span>
                        <i class="fas fa-chevron-right fleet-list__arrow"></i>
                    </a>
                @empty
                    <div class="fleet-empty-state">
                        <i class="fas fa-warehouse"></i>
                        <p>{{ __('messages.common.no_data') }}</p>
                    </div>
                @endforelse
            </div>
        </article>
    </section>

    {{-- TOP GARAGES + SYSTEM INFO --}}
    <section class="fleet-dashboard-grid">
        <article class="fleet-panel fleet-panel--wide">
            <header class="fleet-panel__header">
                <div>
                    <span class="fleet-eyebrow">{{ __('messages.super_admin.dashboard.eyebrow_stats') }}</span>
                    <h2>{{ __('messages.super_admin.dashboard.top_garages') }}</h2>
                </div>
            </header>
            <div class="fleet-table-wrap">
                <table class="fleet-table">
                    <thead>
                        <tr>
                            <th>{{ __('messages.super_admin.garages.name') }}</th>
                            <th>{{ __('messages.super_admin.garages.company') }}</th>
                            <th class="text-center">{{ __('messages.nav.buses') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topGarages as $garage)
                            <tr>
                                <td><strong>{{ $garage->name }}</strong></td>
                                <td>{{ $garage->company?->name ?? '—' }}</td>
                                <td class="text-center">
                                    <span class="fleet-status fleet-status--success">{{ $garage->buses_count }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('super-admin.garages.show', $garage) }}" class="fleet-text-link">
                                        {{ __('messages.common.view') }} <i class="fas fa-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="fleet-table__empty">{{ __('messages.common.no_data') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="fleet-panel">
            <header class="fleet-panel__header">
                <div>
                    <span class="fleet-eyebrow">{{ __('messages.super_admin.dashboard.eyebrow_system') }}</span>
                    <h2>{{ __('messages.super_admin.dashboard.system_info') }}</h2>
                </div>
            </header>
            <div class="fleet-stock-list">
                <div class="fleet-stock-item">
                    <span>
                        <strong>PHP</strong>
                        <small>Runtime</small>
                    </span>
                    <b style="background: #eaf1ff; color: #2563eb;">{{ $system['php_version'] }}</b>
                </div>
                <div class="fleet-stock-item">
                    <span>
                        <strong>Laravel</strong>
                        <small>Framework</small>
                    </span>
                    <b style="background: #f3e8ff; color: #7c3aed;">{{ $system['laravel_version'] }}</b>
                </div>
                <div class="fleet-stock-item">
                    <span>
                        <strong>Database</strong>
                        <small>Driver</small>
                    </span>
                    <b style="background: #dff9ed; color: #047857;">{{ strtoupper($system['db_driver']) }}</b>
                </div>
                <div class="fleet-stock-item">
                    <span>
                        <strong>Cache</strong>
                        <small>Store</small>
                    </span>
                    <b style="background: #fff4cf; color: #a16207;">{{ $system['cache_driver'] }}</b>
                </div>
                <div class="fleet-stock-item">
                    <span>
                        <strong>{{ __('messages.super_admin.dashboard.environment') }}</strong>
                        <small>{{ $system['timezone'] }}</small>
                    </span>
                    <b style="background: {{ $system['app_env'] === 'production' ? '#dff9ed' : '#feecec' }}; color: {{ $system['app_env'] === 'production' ? '#047857' : '#dc2626' }};">
                        {{ strtoupper($system['app_env']) }}
                    </b>
                </div>
            </div>
        </article>
    </section>
</div>
@endsection
