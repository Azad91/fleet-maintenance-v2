<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Fleet Control')</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app-v2.css') }}">
    <script>
        if (localStorage.getItem('fleet-theme') === 'dark') {
            document.documentElement.dataset.fleetTheme = 'dark';
        }
    </script>
    @stack('styles')
</head>
<body class="fleet-app">
    <div class="fleet-shell">
        <aside class="fleet-sidebar" id="fleetSidebar">
            <div class="fleet-sidebar__top">
                <a href="{{ route('dashboard') }}" class="fleet-brand text-decoration-none">
                    <span class="fleet-brand__mark"><i class="fas fa-bus"></i></span>
                    <span><strong>Fleet</strong><span class="fleet-brand__accent">Control</span><small>MAINTENANCE SYSTEM</small></span>
                </a>
                @auth
                    <div class="fleet-user">
                        <span class="fleet-user__avatar">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span>
                        <span class="fleet-user__details"><strong>{{ Auth::user()->name }}</strong><small><i class="fas fa-circle"></i> Aktiv istifadəçi</small></span>
                    </div>
                @endauth
                @if(session('current_garage_name'))
                    <a href="{{ route('garage.selection') }}" class="fleet-garage text-decoration-none">
                        <i class="fas fa-warehouse"></i><span><small>Cari qaraj</small><strong>{{ session('current_garage_name') }}</strong>@if(session('current_company_name'))<em>{{ session('current_company_name') }}</em>@endif</span><i class="fas fa-chevron-right fleet-garage__arrow"></i>
                    </a>
                @endif
            </div>
            <nav class="fleet-nav" aria-label="Əsas naviqasiya">
                @php
                    $currentUser = auth()->user();
                    $canManage = $currentUser?->hasGarageRole('admin');
                    $canViewBuses = $currentUser?->hasGarageRole(['admin', 'bus', 'directorate']);
                    $canViewComplaints = $currentUser?->hasGarageRole(['admin', 'complaint', 'directorate']);
                    $canViewWarehouse = $currentUser?->hasGarageRole(['admin', 'warehouse', 'directorate']);
                    $canManageMotorOil = $currentUser?->hasGarageRole('admin');
                    $canViewDailyStatus = $currentUser?->hasGarageRole(['admin', 'daily_status', 'directorate']);
                    $canViewDailyKm = $currentUser?->hasGarageRole(['admin', 'daily_km', 'directorate']);
                @endphp
                <p class="fleet-nav__label">ƏSAS MENYU</p>
                <a href="{{ route('dashboard') }}" class="fleet-nav__link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}"><i class="fas fa-chart-pie"></i><span>İdarə paneli</span></a>
                @if($canViewBuses || $canViewComplaints || $canViewWarehouse || $canManageMotorOil)
                    <p class="fleet-nav__label">ƏMƏLİYYATLAR</p>
                    @if($canViewBuses)<a href="{{ route('buses.index') }}" class="fleet-nav__link {{ request()->routeIs('buses.*') ? 'is-active' : '' }}"><i class="fas fa-bus"></i><span>Avtobuslar</span></a>@endif
                    @if($canViewComplaints)<a href="{{ route('complaints.index') }}" class="fleet-nav__link {{ request()->routeIs('complaints.*') ? 'is-active' : '' }}"><i class="fas fa-screwdriver-wrench"></i><span>Kartlar / Şikayətlər</span></a>@endif
                    @if($canViewWarehouse)<a href="{{ route('warehouses.index') }}" class="fleet-nav__link {{ request()->routeIs('warehouses.*') ? 'is-active' : '' }}"><i class="fas fa-boxes-stacked"></i><span>Anbar</span></a>@endif
                    @if($canManageMotorOil)<a href="{{ route('motor-oil.index') }}" class="fleet-nav__link {{ request()->routeIs('motor-oil.*') ? 'is-active' : '' }}"><i class="fas fa-oil-can"></i><span>Motor yağı</span></a>@endif
                @endif
                @if($canViewDailyStatus || $canViewDailyKm)
                    <p class="fleet-nav__label">GÜNLÜK QEYDLƏR</p>
                    @if($canViewDailyStatus)<a href="{{ route('bus-daily-statuses.index') }}" class="fleet-nav__link {{ request()->routeIs('bus-daily-statuses.*') ? 'is-active' : '' }}"><i class="fas fa-clipboard-check"></i><span>Günlük statuslar</span></a>@endif
                    @if($canViewDailyKm)<a href="{{ route('daily-km-records.index') }}" class="fleet-nav__link {{ request()->routeIs('daily-km-records.*') ? 'is-active' : '' }}"><i class="fas fa-gauge-high"></i><span>Günlük KM</span></a>@endif
                @endif
                @if($canManage)
                    <p class="fleet-nav__label">MƏLUMATLAR</p>
                    <a href="{{ route('drivers.index') }}" class="fleet-nav__link {{ request()->routeIs('drivers.*') ? 'is-active' : '' }}"><i class="fas fa-id-card"></i><span>Sürücülər</span></a>
                    <a href="{{ route('employees.index') }}" class="fleet-nav__link {{ request()->routeIs('employees.*') ? 'is-active' : '' }}"><i class="fas fa-users"></i><span>İşçilər</span></a>
                    <p class="fleet-nav__label">İDARƏETMƏ</p>
                    <a href="{{ route('users.index') }}" class="fleet-nav__link {{ request()->routeIs('users.*') ? 'is-active' : '' }}"><i class="fas fa-user-shield"></i><span>İstifadəçilər</span></a>
                @endif
            </nav>
            <div class="fleet-sidebar__bottom">
                <a href="{{ route('profile.edit') }}" class="fleet-nav__link {{ request()->routeIs('profile.*') ? 'is-active' : '' }}"><i class="fas fa-user-gear"></i><span>Profil ayarları</span></a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="fleet-nav__link fleet-nav__link--logout"><i class="fas fa-arrow-right-from-bracket"></i><span>Çıxış</span></button></form>
            </div>
        </aside>
        <section class="fleet-workspace">
            <header class="fleet-topbar">
                <button class="fleet-menu-toggle" type="button" aria-label="Menyunu aç" aria-controls="fleetSidebar" aria-expanded="false"><i class="fas fa-bars"></i></button>
                <div class="fleet-topbar__context"><span>Fleet Maintenance</span><strong>@yield('title', 'İdarə paneli')</strong></div>
                <div class="fleet-topbar__actions">
                    <button class="fleet-theme-toggle" type="button" aria-label="Tünd rejimə keç" title="Tema seçimi">
                        <i class="fas fa-moon"></i>
                    </button>
                    @if(session('current_garage_name'))<a href="{{ route('garage.selection') }}" class="fleet-topbar__garage text-decoration-none"><i class="fas fa-building"></i><span>{{ session('current_garage_name') }}</span><i class="fas fa-chevron-down"></i></a>@endif
                    @auth<span class="fleet-topbar__avatar" title="{{ Auth::user()->name }}">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span>@endauth
                </div>
            </header>
            <main class="fleet-page">
                @if(session('success'))<div class="fleet-alert fleet-alert--success"><i class="fas fa-circle-check"></i>{{ session('success') }}</div>@endif
                @if(session('error'))<div class="fleet-alert fleet-alert--error"><i class="fas fa-circle-exclamation"></i>{{ session('error') }}</div>@endif
                @yield('content')
            </main>
        </section>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const menuToggle = document.querySelector('.fleet-menu-toggle');
        if (menuToggle) menuToggle.addEventListener('click', () => {
            const isOpen = document.body.classList.toggle('fleet-sidebar-open');
            menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        const themeToggle = document.querySelector('.fleet-theme-toggle');
        const syncThemeToggle = () => {
            const isDark = document.documentElement.dataset.fleetTheme === 'dark';
            themeToggle?.setAttribute('aria-label', isDark ? 'Açıq rejimə keç' : 'Tünd rejimə keç');
            if (themeToggle) themeToggle.innerHTML = `<i class="fas fa-${isDark ? 'sun' : 'moon'}"></i>`;
        };
        syncThemeToggle();
        themeToggle?.addEventListener('click', () => {
            const nextTheme = document.documentElement.dataset.fleetTheme === 'dark' ? 'light' : 'dark';
            if (nextTheme === 'dark') document.documentElement.dataset.fleetTheme = 'dark';
            else delete document.documentElement.dataset.fleetTheme;
            localStorage.setItem('fleet-theme', nextTheme);
            syncThemeToggle();
        });
    </script>
    @yield('scripts')
    @stack('scripts')
</body>
</html>
