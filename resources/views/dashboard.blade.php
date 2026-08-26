@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <h1>Dashboard <small>Avtobus parkınızın ümumi vəziyyəti</small></h1>
</div>

<!-- Statistik Kartlar -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="stat-card primary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-count">{{ $totalBuses }}</div>
                    <div class="stat-label">Cəmi Avtobus</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-bus"></i>
                </div>
            </div>
            <div class="stat-change up">+{{ $activeBuses }} aktiv</div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="stat-card warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-count">{{ $activeComplaints }}</div>
                    <div class="stat-label">Açıq Şikayət</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
            </div>
            <div class="stat-change down">Gözləmədə olanlar</div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="stat-card danger">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-count">{{ $lowStockItems->count() }}</div>
                    <div class="stat-label">Tükənən Məhsul</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
            <div class="stat-change down">Kritik səviyyə</div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="stat-card info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-count">{{ $totalWarehouseItems }}</div>
                    <div class="stat-label">Anbar Məhsulları</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-warehouse"></i>
                </div>
            </div>
            <div class="stat-change up">Ümumi miqdar</div>
        </div>
    </div>
</div>

<!-- Son Avtobuslar və Tükənən Məhsullar -->
<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-bus"></i> Son Avtobuslar</span>
                <a href="{{ route('buses.index') }}" class="btn btn-sm btn-primary">Hamısına Bax</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Xətt №</th>
                                <th>DQN</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentBuses as $bus)
                            <tr>
                                <td>{{ $bus->xett_no ?? '-' }}</td>
                                <td><strong>{{ $bus->dqn }}</strong></td>
                                <td>
                                    @if($bus->aktiv)
                                        <span class="badge-status aktiv">Aktiv</span>
                                    @else
                                        <span class="badge-status passiv">Passiv</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">Hələ avtobus yoxdur</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-clipboard-list"></i> Son Şikayətlər</span>
                <a href="{{ route('complaints.index') }}" class="btn btn-sm btn-primary">Hamısına Bax</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Avtobus</th>
                                <th>Şikayət</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentComplaints as $complaint)
                            <tr>
                                <td>{{ $complaint->bus->dqn ?? '-' }}</td>
                                <td>{{ Str::limit($complaint->shikayet, 30) }}</td>
                                <td>
                                    <span class="badge-status {{ $complaint->status }}">
                                        {{ $complaint->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">Hələ şikayət yoxdur</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bildirişlər (Təkrarlanan nasazlıqlar, KM daxil edilməyənlər) -->
<div class="row g-4">
    @if($recurringIssues->count() > 0)
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-repeat text-warning"></i> Təkrarlanan Nasazlıqlar</span>
            </div>
            <div class="card-body">
                @foreach($recurringIssues as $issue)
                <div class="notification-card warning">
                    <div class="notif-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="notif-content">
                        <div class="notif-title">{{ $issue->bus->dqn ?? 'Bilinməyən' }}</div>
                        <div class="notif-desc">{{ $issue->shikayet }} ({{ $issue->total }} dəfə)</div>
                        <div class="notif-time">Son 30 gündə</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @if($busesWithoutKmToday->count() > 0)
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-road text-danger"></i> Bu gün KM daxil edilməyənlər</span>
            </div>
            <div class="card-body">
                @foreach($busesWithoutKmToday as $bus)
                <div class="notification-card critical">
                    <div class="notif-icon"><i class="fas fa-bus"></i></div>
                    <div class="notif-content">
                        <div class="notif-title">{{ $bus->dqn }}</div>
                        <div class="notif-desc">Bu gün üçün KM məlumatı daxil edilməyib</div>
                        <div class="notif-time">Xətt: {{ $bus->xett_no ?? '-' }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
