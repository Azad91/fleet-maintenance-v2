@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="dashboard-clean">
    <div class="page-header">
        <h1 class="text-white">Dashboard</h1>
        <p class="text-muted">Avtobus parkınızın ümumi vəziyyəti</p>
    </div>

    <!-- Statistik Kartlar -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-clean primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-count">{{ $totalBuses }}</div>
                        <div class="stat-label">Cəmi Avtobus</div>
                    </div>
                    <div class="stat-icon-clean">
                        <i class="fas fa-bus"></i>
                    </div>
                </div>
                <div class="stat-change up">+{{ $activeBuses }} aktiv</div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-clean warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-count">{{ $activeComplaints }}</div>
                        <div class="stat-label">Açıq Şikayət</div>
                    </div>
                    <div class="stat-icon-clean">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
                <div class="stat-change down">Gözləmədə olanlar</div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-clean danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-count">{{ $lowStockItems->count() }}</div>
                        <div class="stat-label">Tükənən Məhsul</div>
                    </div>
                    <div class="stat-icon-clean">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
                <div class="stat-change down">Kritik səviyyə</div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-clean info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-count">{{ $totalWarehouseItems }}</div>
                        <div class="stat-label">Anbar Məhsulları</div>
                    </div>
                    <div class="stat-icon-clean">
                        <i class="fas fa-warehouse"></i>
                    </div>
                </div>
                <div class="stat-change up">Ümumi miqdar</div>
            </div>
        </div>
    </div>

    <!-- Trend və Top Brand -->
    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="card-clean">
                <div class="card-header-clean">
                    <span class="card-title"><i class="fas fa-chart-line"></i> Aylıq Əməliyyat Trendi</span>
                    <span class="text-muted">Bu il</span>
                </div>
                <div class="card-body-clean">
                    <div class="chart-container" style="height: 250px;">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card-clean">
                <div class="card-header-clean">
                    <span class="card-title"><i class="fas fa-star"></i> Ən Çox İstifadə Olunan Detallar</span>
                </div>
                <div class="card-body-clean">
                    <div class="top-brands-clean">
                        <div class="brand-item-clean">
                            <span class="brand-name">Motor Yağı</span>
                            <span class="brand-percent">26%</span>
                            <div class="progress-clean"><div class="progress-bar-clean" style="width: 26%;"></div></div>
                        </div>
                        <div class="brand-item-clean">
                            <span class="brand-name">Yağ Filtr</span>
                            <span class="brand-percent">22%</span>
                            <div class="progress-clean"><div class="progress-bar-clean" style="width: 22%;"></div></div>
                        </div>
                        <div class="brand-item-clean">
                            <span class="brand-name">Hava Filtr</span>
                            <span class="brand-percent">20%</span>
                            <div class="progress-clean"><div class="progress-bar-clean" style="width: 20%;"></div></div>
                        </div>
                        <div class="brand-item-clean">
                            <span class="brand-name">Əyləc Diski</span>
                            <span class="brand-percent">17%</span>
                            <div class="progress-clean"><div class="progress-bar-clean" style="width: 17%;"></div></div>
                        </div>
                        <div class="brand-item-clean">
                            <span class="brand-name">Şin</span>
                            <span class="brand-percent">15%</span>
                            <div class="progress-clean"><div class="progress-bar-clean" style="width: 15%;"></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Son Avtobuslar və Son Şikayətlər -->
    <div class="row g-4">
        <div class="col-xl-6">
            <div class="card-clean">
                <div class="card-header-clean">
                    <span class="card-title"><i class="fas fa-bus"></i> Son Avtobuslar</span>
                    <a href="{{ route('buses.index') }}" class="btn btn-sm btn-primary-clean">Hamısına Bax</a>
                </div>
                <div class="card-body-clean p-0">
                    <div class="table-responsive">
                        <table class="table table-clean table-hover mb-0">
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
            <div class="card-clean">
                <div class="card-header-clean">
                    <span class="card-title"><i class="fas fa-clipboard-list"></i> Son Şikayətlər</span>
                    <a href="{{ route('complaints.index') }}" class="btn btn-sm btn-primary-clean">Hamısına Bax</a>
                </div>
                <div class="card-body-clean p-0">
                    <div class="table-responsive">
                        <table class="table table-clean table-hover mb-0">
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
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('trendChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Yan', 'Fev', 'Mar', 'Apr', 'May', 'İyn', 'İyl', 'Avq', 'Sen', 'Okt', 'Noy', 'Dek'],
                datasets: [{
                    label: 'Əməliyyatlar',
                    data: [12, 19, 15, 22, 30, 45, 55, 78, 65, 50, 40, 35],
                    borderColor: '#d4a74c',
                    backgroundColor: 'rgba(212, 167, 76, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { ticks: { color: '#8b95a9' } },
                    y: { ticks: { color: '#8b95a9' } }
                }
            }
        });
    });
</script>
@endsection
