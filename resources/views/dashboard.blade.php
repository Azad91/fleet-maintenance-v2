@extends('layouts.app')

@section('title', 'İdarə paneli')

@section('content')
    <div class="fleet-dashboard">
        <section class="fleet-page-heading">
            <div>
                <span class="fleet-eyebrow">ƏMƏLİYYAT İCMALI</span>
                <h1>Salam, {{ Auth::user()->name }}!</h1>
                <p>Qarajınızdakı nəqliyyat, texniki işlər və stok vəziyyətini bir baxışda izləyin.</p>
            </div>
            <div class="fleet-page-heading__actions">
                <a href="{{ route('buses.index') }}" class="fleet-button fleet-button--secondary"><i class="fas fa-bus"></i> Avtobuslar</a>
                <a href="{{ route('complaints.index') }}" class="fleet-button fleet-button--primary"><i class="fas fa-arrow-right"></i> Kartlara bax</a>
            </div>
        </section>

        <section class="fleet-kpi-grid" aria-label="Əsas göstəricilər">
            <article class="fleet-kpi-card"><span class="fleet-kpi-card__icon fleet-kpi-card__icon--blue"><i class="fas fa-bus"></i></span><div><span>Ümumi avtobus</span><strong>{{ $totalBuses }}</strong><small>{{ $activeBuses }} aktiv nəqliyyat vasitəsi</small></div></article>
            <article class="fleet-kpi-card"><span class="fleet-kpi-card__icon fleet-kpi-card__icon--amber"><i class="fas fa-screwdriver-wrench"></i></span><div><span>Açıq kartlar</span><strong>{{ $activeComplaints }}</strong><small>Həll olunma gözləyən işlər</small></div></article>
            <article class="fleet-kpi-card"><span class="fleet-kpi-card__icon fleet-kpi-card__icon--violet"><i class="fas fa-boxes-stacked"></i></span><div><span>Anbar qalığı</span><strong>{{ $totalWarehouseItems }}</strong><small>Qeydiyyatda olan ümumi miqdar</small></div></article>
            <article class="fleet-kpi-card"><span class="fleet-kpi-card__icon fleet-kpi-card__icon--rose"><i class="fas fa-gauge-high"></i></span><div><span>Bugünkü KM qeydi yoxdur</span><strong>{{ $busesWithoutKmToday->count() }}</strong><small>Yoxlanmalı avtobuslar</small></div></article>
        </section>

        <section class="fleet-dashboard-grid">
            <article class="fleet-panel fleet-panel--wide">
                <header class="fleet-panel__header"><div><span class="fleet-eyebrow">SON ƏLAVƏ OLUNANLAR</span><h2>Avtobuslar</h2></div><a href="{{ route('buses.index') }}" class="fleet-text-link">Hamısına bax <i class="fas fa-arrow-right"></i></a></header>
                <div class="fleet-list">
                    @forelse($recentBuses as $bus)
                        <a href="{{ route('buses.show', $bus) }}" class="fleet-list__item text-decoration-none">
                            <span class="fleet-list__icon"><i class="fas fa-bus"></i></span>
                            <span class="fleet-list__content"><strong>{{ $bus->bus_project ?? 'Model qeyd edilməyib' }}</strong><small>{{ $bus->dqn ?? 'DQN qeyd edilməyib' }} · Xətt {{ $bus->xett_no ?? '—' }}</small></span>
                            <span class="fleet-status {{ $bus->aktiv ? 'fleet-status--success' : 'fleet-status--muted' }}">{{ $bus->aktiv ? 'Aktiv' : 'Qeyri-aktiv' }}</span><i class="fas fa-chevron-right fleet-list__arrow"></i>
                        </a>
                    @empty
                        <div class="fleet-empty-state"><i class="fas fa-bus"></i><p>Hələ avtobus əlavə edilməyib.</p></div>
                    @endforelse
                </div>
            </article>
            <article class="fleet-panel">
                <header class="fleet-panel__header"><div><span class="fleet-eyebrow">NƏZARƏT</span><h2>Diqqət tələb edir</h2></div></header>
                <div class="fleet-attention-list">
                    <a href="{{ route('warehouses.index') }}" class="fleet-attention-item text-decoration-none"><span class="fleet-attention-item__icon fleet-attention-item__icon--red"><i class="fas fa-box-open"></i></span><span><strong>Aşağı stok</strong><small>{{ $lowStockItems->count() }} detal kritik həddədir</small></span><i class="fas fa-chevron-right"></i></a>
                    <a href="{{ route('daily-km-records.index') }}" class="fleet-attention-item text-decoration-none"><span class="fleet-attention-item__icon fleet-attention-item__icon--amber"><i class="fas fa-gauge-high"></i></span><span><strong>KM qeydi</strong><small>{{ $busesWithoutKmToday->count() }} avtobusdan məlumat gözlənilir</small></span><i class="fas fa-chevron-right"></i></a>
                    <a href="{{ route('complaints.index') }}" class="fleet-attention-item text-decoration-none"><span class="fleet-attention-item__icon fleet-attention-item__icon--blue"><i class="fas fa-repeat"></i></span><span><strong>Təkrarlanan nasazlıq</strong><small>{{ $recurringIssues->count() }} problem izlənməlidir</small></span><i class="fas fa-chevron-right"></i></a>
                </div>
            </article>
        </section>

        <section class="fleet-dashboard-grid">
            <article class="fleet-panel fleet-panel--wide">
                <header class="fleet-panel__header"><div><span class="fleet-eyebrow">TEXNİKİ İŞLƏR</span><h2>Son açıq kartlar</h2></div><a href="{{ route('complaints.index') }}" class="fleet-text-link">Kartlar <i class="fas fa-arrow-right"></i></a></header>
                <div class="fleet-table-wrap"><table class="fleet-table"><thead><tr><th>Avtobus</th><th>Şikayət</th><th>Status</th><th>Tarix</th></tr></thead><tbody>
                    @forelse($recentComplaints as $complaint)
                        <tr><td><strong>{{ optional($complaint->bus)->dqn ?? '—' }}</strong></td><td>{{ Str::limit($complaint->shikayet, 54) }}</td><td><span class="fleet-status fleet-status--warning">{{ $complaint->status }}</span></td><td>{{ optional($complaint->created_at)->format('d.m.Y') }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="fleet-table__empty">Açıq kart yoxdur.</td></tr>
                    @endforelse
                </tbody></table></div>
            </article>
            <article class="fleet-panel">
                <header class="fleet-panel__header"><div><span class="fleet-eyebrow">STOK XƏBƏRDARLIĞI</span><h2>Kritik qalıqlar</h2></div></header>
                <div class="fleet-stock-list">
                    @forelse($lowStockItems as $item)
                        <a href="{{ route('warehouses.index') }}" class="fleet-stock-item text-decoration-none"><span><strong>{{ $item->ad }}</strong><small>{{ $item->kod ?? 'Kod yoxdur' }}</small></span><b>{{ $item->miqdar }}</b></a>
                    @empty
                        <div class="fleet-empty-state fleet-empty-state--compact"><i class="fas fa-circle-check"></i><p>Kritik stok yoxdur.</p></div>
                    @endforelse
                </div>
            </article>
        </section>
    </div>
@endsection
