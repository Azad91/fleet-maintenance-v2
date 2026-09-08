@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="fleet-dashboard">
        <section class="fleet-page-heading">
            <div>
                <span class="fleet-eyebrow">OPERATIONAL OVERVIEW</span>
                <h1>Welcome, {{ Auth::user()->name }}!</h1>
                <p>Monitor your fleet, technical tasks, and warehouse stock at a glance.</p>
            </div>
            <div class="fleet-page-heading__actions">
                <a href="{{ route('buses.index') }}" class="fleet-button fleet-button--secondary"><i class="fas fa-bus"></i> Buses</a>
                <a href="{{ route('complaints.index') }}" class="fleet-button fleet-button--primary"><i class="fas fa-arrow-right"></i> View Cards</a>
            </div>
        </section>

        <section class="fleet-kpi-grid" aria-label="Key metrics">
            <article class="fleet-kpi-card"><span class="fleet-kpi-card__icon fleet-kpi-card__icon--blue"><i class="fas fa-bus"></i></span><div><span>Total Buses</span><strong>{{ $totalBuses }}</strong><small>{{ $activeBuses }} active vehicles</small></div></article>
            <article class="fleet-kpi-card"><span class="fleet-kpi-card__icon fleet-kpi-card__icon--amber"><i class="fas fa-screwdriver-wrench"></i></span><div><span>Open Cards</span><strong>{{ $activeComplaints }}</strong><small>Jobs waiting to be resolved</small></div></article>
            <article class="fleet-kpi-card"><span class="fleet-kpi-card__icon fleet-kpi-card__icon--violet"><i class="fas fa-boxes-stacked"></i></span><div><span>Stock Quantity</span><strong>{{ $totalWarehouseItems }}</strong><small>Total items in stock</small></div></article>
            <article class="fleet-kpi-card"><span class="fleet-kpi-card__icon fleet-kpi-card__icon--rose"><i class="fas fa-gauge-high"></i></span><div><span>No KM Today</span><strong>{{ $busesWithoutKmTodayCount }}</strong><small>Buses to check</small></div></article>
        </section>

        <section class="fleet-dashboard-grid">
            <article class="fleet-panel fleet-panel--wide">
                <header class="fleet-panel__header"><div><span class="fleet-eyebrow">RECENTLY ADDED</span><h2>Buses</h2></div><a href="{{ route('buses.index') }}" class="fleet-text-link">View All <i class="fas fa-arrow-right"></i></a></header>
                <div class="fleet-list">
                    @forelse($recentBuses as $bus)
                        <a href="{{ route('buses.show', $bus) }}" class="fleet-list__item text-decoration-none">
                            <span class="fleet-list__icon"><i class="fas fa-bus"></i></span>
                            <span class="fleet-list__content"><strong>{{ $bus->bus_project ?? 'Model not specified' }}</strong><small>{{ $bus->dqn ?? 'DQN not specified' }} · Route {{ $bus->route_number ?? '—' }}</small></span>
                            <span class="fleet-status {{ $bus->is_active ? 'fleet-status--success' : 'fleet-status--muted' }}">{{ $bus->is_active ? 'Active' : 'Inactive' }}</span><i class="fas fa-chevron-right fleet-list__arrow"></i>
                        </a>
                    @empty
                        <div class="fleet-empty-state"><i class="fas fa-bus"></i><p>No buses added yet.</p></div>
                    @endforelse
                </div>
            </article>
            <article class="fleet-panel">
                <header class="fleet-panel__header"><div><span class="fleet-eyebrow">MONITORING</span><h2>Requires Attention</h2></div></header>
                <div class="fleet-attention-list">
                    <a href="{{ route('warehouses.index') }}" class="fleet-attention-item text-decoration-none"><span class="fleet-attention-item__icon fleet-attention-item__icon--red"><i class="fas fa-box-open"></i></span><span><strong>Low Stock</strong><small>{{ $lowStockItems->count() }} items at critical level</small></span><i class="fas fa-chevron-right"></i></a>
                    <a href="{{ route('daily-km-records.index') }}" class="fleet-attention-item text-decoration-none"><span class="fleet-attention-item__icon fleet-attention-item__icon--amber"><i class="fas fa-gauge-high"></i></span><span><strong>KM Records</strong><small>{{ $busesWithoutKmToday->count() }} buses awaiting data</small></span><i class="fas fa-chevron-right"></i></a>
                    <a href="{{ route('complaints.index') }}" class="fleet-attention-item text-decoration-none"><span class="fleet-attention-item__icon fleet-attention-item__icon--blue"><i class="fas fa-repeat"></i></span><span><strong>Recurring Issues</strong><small>{{ $recurringIssues->count() }} issues to monitor</small></span><i class="fas fa-chevron-right"></i></a>
                </div>
            </article>
        </section>

        <section class="fleet-dashboard-grid">
            <article class="fleet-panel fleet-panel--wide">
                <header class="fleet-panel__header"><div><span class="fleet-eyebrow">TECHNICAL TASKS</span><h2>Recent Open Cards</h2></div><a href="{{ route('complaints.index') }}" class="fleet-text-link">Cards <i class="fas fa-arrow-right"></i></a></header>
                <div class="fleet-table-wrap"><table class="fleet-table"><thead><tr><th>Bus</th><th>Complaint</th><th>Status</th><th>Date</th></tr></thead><tbody>
                    @forelse($recentComplaints as $complaint)
                        <tr>
                            <td><strong>{{ optional($complaint->bus)->dqn ?? '—' }}</strong></td>
                            <td>
                                {{ Str::limit($complaint->items->first()->description ?? 'No complaint', 54) }}
                            </td>
                            <td><span class="fleet-status fleet-status--warning">{{ $complaint->status }}</span></td>
                            <td>{{ optional($complaint->created_at)->format('d.m.Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="fleet-table__empty">No open cards.</td></tr>
                    @endforelse
                </tbody></table></div>
            </article>
            <article class="fleet-panel">
                <header class="fleet-panel__header"><div><span class="fleet-eyebrow">STOCK ALERT</span><h2>Critical Stock</h2></div></header>
                <div class="fleet-stock-list">
                    @forelse($lowStockItems as $item)
                        <a href="{{ route('warehouses.index') }}" class="fleet-stock-item text-decoration-none"><span><strong>{{ $item->name }}</strong><small>{{ $item->code ?? 'No code' }}</small></span><b>{{ $item->quantity }}</b></a>
                    @empty
                        <div class="fleet-empty-state fleet-empty-state--compact"><i class="fas fa-circle-check"></i><p>No critical stock items.</p></div>
                    @endforelse
                </div>
            </article>
        </section>
    </div>
@endsection