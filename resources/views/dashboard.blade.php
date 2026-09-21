@extends('layouts.app')

@section('title', __('messages.dashboard.title'))

@section('content')
    <div class="fleet-dashboard">
        <section class="fleet-page-heading">
            <div>
                <span class="fleet-eyebrow">{{ __('messages.dashboard.eyebrow') }}</span>
                <h1>{{ __('messages.dashboard.welcome', ['name' => Auth::user()->name]) }}</h1>
                <p>{{ __('messages.dashboard.subtitle') }}</p>
            </div>
            <div class="fleet-page-heading__actions">
                <a href="{{ route('buses.index') }}" class="fleet-button fleet-button--secondary">
                    <i class="fas fa-bus"></i> {{ __('messages.nav.buses') }}
                </a>
                <a href="{{ route('complaints.index') }}" class="fleet-button fleet-button--primary">
                    <i class="fas fa-arrow-right"></i> {{ __('messages.dashboard.btn_view_cards') }}
                </a>
            </div>
        </section>

        <section class="fleet-kpi-grid" aria-label="{{ __('messages.dashboard.kpi_label') }}">
            <article class="fleet-kpi-card">
                <span class="fleet-kpi-card__icon fleet-kpi-card__icon--blue"><i class="fas fa-bus"></i></span>
                <div>
                    <span>{{ __('messages.dashboard.total_buses') }}</span>
                    <strong>{{ $totalBuses }}</strong>
                    <small>{{ __('messages.dashboard.active_vehicles', ['count' => $activeBuses]) }}</small>
                </div>
            </article>
            <article class="fleet-kpi-card">
                <span class="fleet-kpi-card__icon fleet-kpi-card__icon--amber"><i class="fas fa-screwdriver-wrench"></i></span>
                <div>
                    <span>{{ __('messages.dashboard.open_cards') }}</span>
                    <strong>{{ $activeComplaints }}</strong>
                    <small>{{ __('messages.dashboard.open_cards_desc') }}</small>
                </div>
            </article>
            <article class="fleet-kpi-card">
                <span class="fleet-kpi-card__icon fleet-kpi-card__icon--violet"><i class="fas fa-boxes-stacked"></i></span>
                <div>
                    <span>{{ __('messages.dashboard.stock_quantity') }}</span>
                    <strong>{{ $totalWarehouseItems }}</strong>
                    <small>{{ __('messages.dashboard.stock_quantity_desc') }}</small>
                </div>
            </article>
            <article class="fleet-kpi-card">
                <span class="fleet-kpi-card__icon fleet-kpi-card__icon--rose"><i class="fas fa-gauge-high"></i></span>
                <div>
                    <span>{{ __('messages.dashboard.no_km_today') }}</span>
                    <strong>{{ $busesWithoutKmTodayCount }}</strong>
                    <small>{{ __('messages.dashboard.no_km_today_desc') }}</small>
                </div>
            </article>
        </section>
        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- OIL CHANGE ALERTS                                 --}}
        {{-- ═══════════════════════════════════════════════════ --}}
        @if(($oilStats['total_attention'] ?? 0) > 0)
            <section class="fleet-panel mb-4">
                <header class="fleet-panel__header">
                    <div>
                        <span class="fleet-eyebrow">{{ __('messages.dashboard.oil_change_eyebrow') }}</span>
                        <h2>🛢️ {{ __('messages.dashboard.oil_change_title') }}</h2>
                    </div>
                    <a href="{{ route('oil-changes.index') }}" class="fleet-text-link">
                        {{ __('messages.dashboard.oil_view_details') }} <i class="fas fa-arrow-right"></i>
                    </a>
                </header>

                <div class="row g-3 p-3">
                    @if(($oilStats['overdue'] ?? 0) > 0)
                        <div class="col-md-4">
                            <a href="{{ route('oil-changes.index') }}" class="text-decoration-none">
                                <div class="p-3 rounded-3" style="background: #fef2f2; border: 1px solid #fecaca;">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="fas fa-circle-exclamation text-danger"></i>
                                        <strong class="text-danger" style="font-size: 13px;">
                                            {{ __('messages.oil_change.status.overdue') }}
                                        </strong>
                                    </div>
                                    <div class="fw-bold text-danger" style="font-size: 28px; letter-spacing: -0.5px;">
                                        {{ $oilStats['overdue'] }}
                                    </div>
                                    <small class="text-danger">{{ __('messages.dashboard.oil_buses') }}</small>
                                </div>
                            </a>
                        </div>
                    @endif

                    @if(($oilStats['critical'] ?? 0) > 0)
                        <div class="col-md-4">
                            <a href="{{ route('oil-changes.index') }}" class="text-decoration-none">
                                <div class="p-3 rounded-3" style="background: #fff1f2; border: 1px solid #fed7aa;">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="fas fa-triangle-exclamation" style="color: #ea580c;"></i>
                                        <strong style="color: #ea580c; font-size: 13px;">
                                            {{ __('messages.oil_change.status.critical') }}
                                        </strong>
                                    </div>
                                    <div class="fw-bold" style="color: #ea580c; font-size: 28px; letter-spacing: -0.5px;">
                                        {{ $oilStats['critical'] }}
                                    </div>
                                    <small style="color: #ea580c;">{{ __('messages.dashboard.oil_buses') }}</small>
                                </div>
                            </a>
                        </div>
                    @endif

                    @if(($oilStats['due-soon'] ?? 0) > 0)
                        <div class="col-md-4">
                            <a href="{{ route('oil-changes.index') }}" class="text-decoration-none">
                                <div class="p-3 rounded-3" style="background: #fffbeb; border: 1px solid #fde68a;">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="fas fa-clock text-warning"></i>
                                        <strong class="text-warning" style="font-size: 13px;">
                                            {{ __('messages.oil_change.status.due-soon') }}
                                        </strong>
                                    </div>
                                    <div class="fw-bold text-warning" style="font-size: 28px; letter-spacing: -0.5px;">
                                        {{ $oilStats['due-soon'] }}
                                    </div>
                                    <small class="text-warning">{{ __('messages.dashboard.oil_buses') }}</small>
                                </div>
                            </a>
                        </div>
                    @endif
                </div>
            </section>
        @endif
        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- TRANSFER NOTIFICATIONS                             --}}
        {{-- ═══════════════════════════════════════════════════ --}}
        @if($outboundPending + $inboundPending + $disputedCount > 0)
            <section class="fleet-panel mb-4">
                <header class="fleet-panel__header">
                    <div>
                        <span class="fleet-eyebrow">{{ __('messages.transfers.dashboard_eyebrow') }}</span>
                        <h2>🔔 {{ __('messages.transfers.dashboard_title') }}</h2>
                    </div>
                    <a href="{{ route('warehouse-transfers.index') }}" class="fleet-text-link">
                        {{ __('messages.dashboard.view_all') }} <i class="fas fa-arrow-right"></i>
                    </a>
                </header>

                {{-- Counter badges --}}
                <div class="row g-3 p-3">
                    @if($inboundPending > 0)
                        <div class="col-md-4">
                            <a href="{{ route('warehouse-transfers.index', ['direction' => 'inbound', 'status' => 'dispatched']) }}"
                               class="text-decoration-none">
                                <div class="p-3 rounded-3"
                                     style="background: #ecfdf5; border: 1px solid #a7f3d0;">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="fas fa-arrow-down text-success"></i>
                                        <strong class="text-success" style="font-size: 13px;">
                                            {{ __('messages.transfers.inbound_pending_label') }}
                                        </strong>
                                    </div>
                                    <div class="fw-bold text-success" style="font-size: 28px; letter-spacing: -0.5px;">
                                        {{ $inboundPending }}
                                    </div>
                                    <small class="text-success">
                                        {{ __('messages.transfers.inbound_pending_hint') }}
                                    </small>
                                </div>
                            </a>
                        </div>
                    @endif

                    @if($outboundPending > 0)
                        <div class="col-md-4">
                            <a href="{{ route('warehouse-transfers.index', ['direction' => 'outbound', 'status' => 'dispatched']) }}"
                               class="text-decoration-none">
                                <div class="p-3 rounded-3"
                                     style="background: #eff6ff; border: 1px solid #bfdbfe;">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="fas fa-arrow-up text-primary"></i>
                                        <strong class="text-primary" style="font-size: 13px;">
                                            {{ __('messages.transfers.outbound_pending_label') }}
                                        </strong>
                                    </div>
                                    <div class="fw-bold text-primary" style="font-size: 28px; letter-spacing: -0.5px;">
                                        {{ $outboundPending }}
                                    </div>
                                    <small class="text-primary">
                                        {{ __('messages.transfers.outbound_pending_hint') }}
                                    </small>
                                </div>
                            </a>
                        </div>
                    @endif

                    @if($disputedCount > 0)
                        <div class="col-md-4">
                            <a href="{{ route('warehouse-transfers.index', ['status' => 'disputed']) }}"
                               class="text-decoration-none">
                                <div class="p-3 rounded-3"
                                     style="background: #fffbeb; border: 1px solid #fde68a;">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="fas fa-triangle-exclamation text-warning"></i>
                                        <strong class="text-warning" style="font-size: 13px;">
                                            {{ __('messages.transfers.disputed_label') }}
                                        </strong>
                                    </div>
                                    <div class="fw-bold text-warning" style="font-size: 28px; letter-spacing: -0.5px;">
                                        {{ $disputedCount }}
                                    </div>
                                    <small class="text-warning">
                                        {{ __('messages.transfers.disputed_hint') }}
                                    </small>
                                </div>
                            </a>
                        </div>
                    @endif
                </div>

                {{-- Recent pending transfers list --}}
                @if($pendingTransfers->isNotEmpty())
                    <div class="fleet-list" style="border-top: 1px solid #eef2f7;">
                        @foreach($pendingTransfers as $transfer)
                            <a href="{{ route('warehouse-transfers.show', $transfer) }}"
                               class="fleet-list__item text-decoration-none">
                                <span class="fleet-list__icon">
                                    <i class="fas fa-arrow-right-arrow-left"></i>
                                </span>
                                <span class="fleet-list__content">
                                    <strong>
                                        #{{ $transfer->id }}
                                        · {{ $transfer->fromGarage?->name ?? '—' }}
                                        →
                                        {{ $transfer->destination_label_full }}
                                    </strong>
                                    <small>
                                        {{ $transfer->type->label() }}
                                        · {{ $transfer->declared_total }}
                                        {{ __('messages.transfers.items_unit') }}
                                        · {{ $transfer->created_at?->format('d.m.Y H:i') }}
                                    </small>
                                </span>
                                <span class="fleet-status fleet-status--warning">
                                    {{ $transfer->status->label() }}
                                </span>
                                <i class="fas fa-chevron-right fleet-list__arrow"></i>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        <section class="fleet-dashboard-grid">
            <article class="fleet-panel fleet-panel--wide">
                <header class="fleet-panel__header">
                    <div>
                        <span class="fleet-eyebrow">{{ __('messages.dashboard.eyebrow_recent') }}</span>
                        <h2>{{ __('messages.dashboard.recent_buses') }}</h2>
                    </div>
                    <a href="{{ route('buses.index') }}" class="fleet-text-link">
                        {{ __('messages.dashboard.view_all') }} <i class="fas fa-arrow-right"></i>
                    </a>
                </header>
                <div class="fleet-list">
                    @forelse($recentBuses as $bus)
                        <a href="{{ route('buses.show', $bus) }}" class="fleet-list__item text-decoration-none">
                            <span class="fleet-list__icon"><i class="fas fa-bus"></i></span>
                            <span class="fleet-list__content">
                                <strong>{{ $bus->bus_project ?? __('messages.dashboard.model_not_specified') }}</strong>
                                <small>
                                    {{ $bus->dqn ?? __('messages.dashboard.dqn_not_specified') }}
                                    · {{ __('messages.dashboard.route_label', ['number' => $bus->route_number ?? '—']) }}
                                </small>
                            </span>
                            <span class="fleet-status {{ $bus->is_active ? 'fleet-status--success' : 'fleet-status--muted' }}">
                                {{ $bus->is_active ? __('messages.dashboard.status_active') : __('messages.dashboard.status_inactive') }}
                            </span>
                            <i class="fas fa-chevron-right fleet-list__arrow"></i>
                        </a>
                    @empty
                        <div class="fleet-empty-state">
                            <i class="fas fa-bus"></i>
                            <p>{{ __('messages.dashboard.no_buses') }}</p>
                        </div>
                    @endforelse
                </div>
            </article>
            <article class="fleet-panel">
                <header class="fleet-panel__header">
                    <div>
                        <span class="fleet-eyebrow">{{ __('messages.dashboard.eyebrow_monitoring') }}</span>
                        <h2>{{ __('messages.dashboard.attention') }}</h2>
                    </div>
                </header>
                <div class="fleet-attention-list">
                    <a href="{{ route('warehouses.index') }}" class="fleet-attention-item text-decoration-none">
                        <span class="fleet-attention-item__icon fleet-attention-item__icon--red"><i class="fas fa-box-open"></i></span>
                        <span>
                            <strong>{{ __('messages.dashboard.low_stock_alert') }}</strong>
                            <small>{{ __('messages.dashboard.low_stock_desc', ['count' => $lowStockItems->count()]) }}</small>
                        </span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <a href="{{ route('daily-km-records.index') }}" class="fleet-attention-item text-decoration-none">
                        <span class="fleet-attention-item__icon fleet-attention-item__icon--amber"><i class="fas fa-gauge-high"></i></span>
                        <span>
                            <strong>{{ __('messages.dashboard.km_records') }}</strong>
                            <small>{{ __('messages.dashboard.km_records_desc', ['count' => $busesWithoutKmToday->count()]) }}</small>
                        </span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <a href="{{ route('complaints.index') }}" class="fleet-attention-item text-decoration-none">
                        <span class="fleet-attention-item__icon fleet-attention-item__icon--blue"><i class="fas fa-repeat"></i></span>
                        <span>
                            <strong>{{ __('messages.dashboard.recurring_issues') }}</strong>
                            <small>{{ __('messages.dashboard.recurring_desc', ['count' => $recurringIssues->count()]) }}</small>
                        </span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </article>
        </section>

        <section class="fleet-dashboard-grid">
            <article class="fleet-panel fleet-panel--wide">
                <header class="fleet-panel__header">
                    <div>
                        <span class="fleet-eyebrow">{{ __('messages.dashboard.eyebrow_tasks') }}</span>
                        <h2>{{ __('messages.dashboard.recent_cards') }}</h2>
                    </div>
                    <a href="{{ route('complaints.index') }}" class="fleet-text-link">
                        {{ __('messages.dashboard.link_cards') }} <i class="fas fa-arrow-right"></i>
                    </a>
                </header>
                <div class="fleet-table-wrap">
                    <table class="fleet-table">
                        <thead>
                            <tr>
                                <th>{{ __('messages.dashboard.table_bus') }}</th>
                                <th>{{ __('messages.dashboard.table_complaint') }}</th>
                                <th>{{ __('messages.dashboard.table_status') }}</th>
                                <th>{{ __('messages.dashboard.table_date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentComplaints as $complaint)
                                <tr>
                                    <td><strong>{{ optional($complaint->bus)->dqn ?? '—' }}</strong></td>
                                    <td>
                                        {{ Str::limit($complaint->items->first()->description ?? __('messages.dashboard.no_complaint'), 54) }}
                                    </td>
                                    <td>
                                        <span class="fleet-status fleet-status--warning">
                                            {{ $complaint->status->label() }}
                                        </span>
                                    </td>
                                    <td>{{ optional($complaint->created_at)->format('d.m.Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="fleet-table__empty">{{ __('messages.dashboard.no_open_cards') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
            <article class="fleet-panel">
                <header class="fleet-panel__header">
                    <div>
                        <span class="fleet-eyebrow">{{ __('messages.dashboard.eyebrow_stock') }}</span>
                        <h2>{{ __('messages.dashboard.critical_stock') }}</h2>
                    </div>
                </header>
                <div class="fleet-stock-list">
                    @forelse($lowStockItems as $item)
                        <a href="{{ route('warehouses.index') }}" class="fleet-stock-item text-decoration-none">
                            <span>
                                <strong>{{ $item->name }}</strong>
                                <small>{{ $item->code ?? __('messages.dashboard.no_code') }}</small>
                            </span>
                            <b>{{ $item->quantity }}</b>
                        </a>
                    @empty
                        <div class="fleet-empty-state fleet-empty-state--compact">
                            <i class="fas fa-circle-check"></i>
                            <p>{{ __('messages.dashboard.no_critical_stock') }}</p>
                        </div>
                    @endforelse
                </div>
            </article>
        </section>
    </div>
@endsection
