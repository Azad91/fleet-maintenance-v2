<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Fleet Maintenance V2')</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- V2 CSS (əsas) -->
<link rel="stylesheet" href="{{ asset('css/app-v2.css') }}">


    @stack('styles')
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- SIDEBAR -->
            <div class="col-auto sidebar">
                <!-- Brand -->
                <div class="brand text-center">
                <a href="{{ route('dashboard') }}" class="text-decoration-none">
                    <div class="logo-icon">
                            <i class="fas fa-bus"></i>
                        </div>
                        <span class="brand-name">Fleet</span>
                        <span class="brand-sub">Maintenance V2</span>
                    </a>
                </div>

                <!-- User Info -->
                <div class="user-info">
                    <div class="user-avatar">
                        {{ Auth::user() ? substr(Auth::user()->name, 0, 1) : 'A' }}
                    </div>
                    <div class="user-details">
                        <div class="user-name">{{ Auth::user()->name ?? 'Qonaq' }}</div>
                        <div class="user-role">{{ Auth::user()->role ?? 'İstifadəçi' }}</div>
                    </div>
                </div>

                @if(session('current_garage_name'))
                <div class="px-3 py-2 mb-3">
                    <div class="bg-dark rounded p-2">
                        <small class="text-muted d-block">🏠 Cari Qaraj</small>
                        <div class="text-white fw-bold">{{ session('current_garage_name') }}</div>
                        <div class="text-muted small">{{ session('current_company_name') ?? '' }}</div>
                        <a href="{{ route('garage.selection') }}" class="text-warning small text-decoration-none">
                            <i class="fas fa-exchange-alt"></i> Dəyiş
                        </a>
                    </div>
                </div>
                @endif

                <!-- Navigation -->
                <nav class="nav flex-column">
                    <div class="nav-label">ƏSAS</div>

                    <div class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <a href="{{ route('dashboard') }}">
                            <i class="fas fa-chart-pie"></i>
                            <span>Dashboard</span>
                        </a>
                    </div>

                    <div class="nav-label">İDARƏETMƏ</div>

                    <div class="nav-item {{ request()->routeIs('buses.*') ? 'active' : '' }}">
                        <a href="{{ route('buses.index') }}">
                            <i class="fas fa-bus"></i>
                            <span>Avtobuslar</span>
                        </a>
                    </div>

                    <div class="nav-item {{ request()->routeIs('complaints.*') ? 'active' : '' }}">
                        <a href="{{ route('complaints.index') }}">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Kartlar</span>
                        </a>
                    </div>

                    <div class="nav-item {{ request()->routeIs('warehouses.*') ? 'active' : '' }}">
                        <a href="{{ route('warehouses.index') }}">
                            <i class="fas fa-warehouse"></i>
                            <span>Anbar</span>
                        </a>
                    </div>

                    <div class="nav-label">TEXNİKİ XİDMƏT</div>

                    <div class="nav-item {{ request()->routeIs('motor-oil.*') ? 'active' : '' }}">
                        <a href="{{ route('motor-oil.index') }}">
                            <i class="fas fa-oil-can"></i>
                            <span>Motor Yağı</span>
                        </a>
                    </div>

                    <div class="nav-item {{ request()->routeIs('bus-daily-statuses.*') ? 'active' : '' }}">
                        <a href="{{ route('bus-daily-statuses.index') }}">
                            <i class="fas fa-check-circle"></i>
                            <span>Gündəlik Status</span>
                        </a>
                    </div>

                    <div class="nav-item {{ request()->routeIs('daily-km-records.*') ? 'active' : '' }}">
                        <a href="{{ route('daily-km-records.index') }}">
                            <i class="fas fa-road"></i>
                            <span>Gündəlik KM</span>
                        </a>
                    </div>

                    <div class="nav-item {{ request()->routeIs('drivers.*') ? 'active' : '' }}">
                        <a href="{{ route('drivers.index') }}">
                            <i class="fas fa-id-card"></i>
                            <span>Sürücülər</span>
                        </a>
                    </div>

                    <hr>

                    <div class="nav-label">SİSTEM</div>

                    <div class="nav-item {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                        <a href="{{ route('employees.index') }}">
                            <i class="fas fa-users"></i>
                            <span>İşçilər</span>
                        </a>
                    </div>

                    <div class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                        <a href="{{ route('profile.edit') }}">
                            <i class="fas fa-user-cog"></i>
                            <span>Profil</span>
                        </a>
                    </div>

                    <div class="nav-item">
                        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Çıxış</span>
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </div>
                </nav>
            </div>

            <!-- MAIN CONTENT -->
            <div class="col content-wrapper">
                @yield('content')
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
