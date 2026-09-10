<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('messages.nav.dashboard')) · Fleet Control</title>
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
                    <span>
                        <strong>Fleet</strong><span class="fleet-brand__accent">Control</span>
                        <small>{{ __('messages.common.app_subtitle') }}</small>
                    </span>
                </a>
                @auth
                    <div class="fleet-user">
                        <span class="fleet-user__avatar">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span>
                        <span class="fleet-user__details">
                            <strong>{{ Auth::user()->name }}</strong>
                            <small><i class="fas fa-circle"></i> {{ __('messages.common.active_user') }}</small>
                        </span>
                    </div>
                @endauth
                @if(session('current_garage_name'))
                    <a href="{{ route('garage.selection') }}" class="fleet-garage text-decoration-none">
                        <i class="fas fa-warehouse"></i>
                        <span>
                            <small>{{ __('messages.common.current_garage') }}</small>
                            <strong>{{ session('current_garage_name') }}</strong>
                            @if(session('current_company_name'))
                                <em>{{ session('current_company_name') }}</em>
                            @endif
                        </span>
                        <i class="fas fa-chevron-right fleet-garage__arrow"></i>
                    </a>
                @endif
            </div>

            <nav class="fleet-nav" aria-label="{{ __('messages.nav.main_navigation') }}">
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

                <p class="fleet-nav__label">{{ __('messages.nav.main_menu') }}</p>
                <a href="{{ route('dashboard') }}" class="fleet-nav__link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                    <i class="fas fa-chart-pie"></i><span>{{ __('messages.nav.dashboard') }}</span>
                </a>

                @if($canViewBuses || $canViewComplaints || $canViewWarehouse || $canManageMotorOil)
                    <p class="fleet-nav__label">{{ __('messages.nav.operations') }}</p>
                    @if($canViewBuses)
                        <a href="{{ route('buses.index') }}" class="fleet-nav__link {{ request()->routeIs('buses.*') ? 'is-active' : '' }}">
                            <i class="fas fa-bus"></i><span>{{ __('messages.nav.buses') }}</span>
                        </a>
                    @endif
                    @if($canViewComplaints)
                        <a href="{{ route('complaints.index') }}" class="fleet-nav__link {{ request()->routeIs('complaints.*') ? 'is-active' : '' }}">
                            <i class="fas fa-screwdriver-wrench"></i><span>{{ __('messages.nav.complaints') }}</span>
                        </a>
                    @endif
                    @if($canViewWarehouse)
                        <a href="{{ route('warehouses.index') }}" class="fleet-nav__link {{ request()->routeIs('warehouses.*') ? 'is-active' : '' }}">
                            <i class="fas fa-boxes-stacked"></i><span>{{ __('messages.nav.warehouses') }}</span>
                        </a>
                    @endif
                    @if($canManageMotorOil)
                        <a href="{{ route('motor-oil.index') }}" class="fleet-nav__link {{ request()->routeIs('motor-oil.*') ? 'is-active' : '' }}">
                            <i class="fas fa-oil-can"></i><span>{{ __('messages.nav.motor_oil') }}</span>
                        </a>
                    @endif
                @endif

                @if($canViewDailyStatus || $canViewDailyKm)
                    <p class="fleet-nav__label">{{ __('messages.nav.daily_records') }}</p>
                    @if($canViewDailyStatus)
                        <a href="{{ route('bus-daily-statuses.index') }}" class="fleet-nav__link {{ request()->routeIs('bus-daily-statuses.*') ? 'is-active' : '' }}">
                            <i class="fas fa-clipboard-check"></i><span>{{ __('messages.nav.daily_statuses') }}</span>
                        </a>
                    @endif
                    @if($canViewDailyKm)
                        <a href="{{ route('daily-km-records.index') }}" class="fleet-nav__link {{ request()->routeIs('daily-km-records.*') ? 'is-active' : '' }}">
                            <i class="fas fa-gauge-high"></i><span>{{ __('messages.nav.daily_km') }}</span>
                        </a>
                    @endif
                @endif

                @if($canManage)
                    <p class="fleet-nav__label">{{ __('messages.nav.data') }}</p>
                    <a href="{{ route('drivers.index') }}" class="fleet-nav__link {{ request()->routeIs('drivers.*') ? 'is-active' : '' }}">
                        <i class="fas fa-id-card"></i><span>{{ __('messages.nav.drivers') }}</span>
                    </a>
                    <a href="{{ route('employees.index') }}" class="fleet-nav__link {{ request()->routeIs('employees.*') ? 'is-active' : '' }}">
                        <i class="fas fa-users"></i><span>{{ __('messages.nav.employees') }}</span>
                    </a>
                    <p class="fleet-nav__label">{{ __('messages.nav.administration') }}</p>
                    <a href="{{ route('users.index') }}" class="fleet-nav__link {{ request()->routeIs('users.*') ? 'is-active' : '' }}">
                        <i class="fas fa-user-shield"></i><span>{{ __('messages.nav.users') }}</span>
                    </a>
                @endif
            </nav>

            <div class="fleet-sidebar__bottom">
                <a href="{{ route('profile.edit') }}" class="fleet-nav__link {{ request()->routeIs('profile.*') ? 'is-active' : '' }}">
                    <i class="fas fa-user-gear"></i><span>{{ __('messages.nav.profile') }}</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="fleet-nav__link fleet-nav__link--logout">
                        <i class="fas fa-arrow-right-from-bracket"></i><span>{{ __('messages.nav.logout') }}</span>
                    </button>
                </form>
            </div>
        </aside>

        <section class="fleet-workspace">
            <header class="fleet-topbar">
                <button class="fleet-menu-toggle" type="button" aria-label="{{ __('messages.common.open_menu') }}" aria-controls="fleetSidebar" aria-expanded="false">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="fleet-topbar__context">
                    <span>{{ __('messages.nav.app_name') }}</span>
                    <strong>@yield('title', __('messages.nav.dashboard'))</strong>
                </div>
                <div class="fleet-topbar__actions">
                    {{-- Language switcher --}}
                    <div class="dropdown">
                        <button class="fleet-theme-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('messages.common.language') }}">
                            <i class="fas fa-globe"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @foreach(config('app.supported_locales', []) as $code => $locale)
                                <li>
                                    <a class="dropdown-item {{ app()->getLocale() === $code ? 'active' : '' }}"
                                       href="{{ request()->fullUrlWithQuery(['lang' => $code]) }}">
                                        <span class="me-2">{{ $locale['flag'] }}</span>{{ $locale['name'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Theme toggle --}}
                    <button class="fleet-theme-toggle" type="button" data-role="theme-toggle" aria-label="{{ __('messages.common.theme_toggle') }}" title="{{ __('messages.common.theme_toggle') }}">
                        <i class="fas fa-moon"></i>
                    </button>

                    @if(session('current_garage_name'))
                        <a href="{{ route('garage.selection') }}" class="fleet-topbar__garage text-decoration-none">
                            <i class="fas fa-building"></i>
                            <span>{{ session('current_garage_name') }}</span>
                            <i class="fas fa-chevron-down"></i>
                        </a>
                    @endif
                    @auth
                        <span class="fleet-topbar__avatar" title="{{ Auth::user()->name }}">
                            {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                        </span>
                    @endauth
                </div>
            </header>

            <main class="fleet-page">
                @if(session('success'))
                    <div class="fleet-alert fleet-alert--success">
                        <i class="fas fa-circle-check"></i>{{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="fleet-alert fleet-alert--error">
                        <i class="fas fa-circle-exclamation"></i>{{ session('error') }}
                    </div>
                @endif

                @if(session('warning'))
                    <div class="fleet-alert fleet-alert--warning">
                        <i class="fas fa-triangle-exclamation"></i>{{ session('warning') }}
                    </div>
                @endif

                @if(session('import_report'))
                    @php $report = session('import_report'); @endphp
                    <div class="fleet-import-report">
                        <div class="fleet-import-report__summary">
                            <strong>{{ __('messages.imports.report_title') }}:</strong>
                            <span class="fleet-import-report__badge fleet-import-report__badge--success">
                                ✅ {{ __('messages.imports.report_imported', ['count' => $report['imported']]) }}
                            </span>
                            @if(count($report['skipped']) > 0)
                                <span class="fleet-import-report__badge fleet-import-report__badge--warning">
                                    ⏭️ {{ __('messages.imports.report_skipped', ['count' => count($report['skipped'])]) }}
                                </span>
                            @endif
                            @if(count($report['failed']) > 0)
                                <span class="fleet-import-report__badge fleet-import-report__badge--danger">
                                    ❌ {{ __('messages.imports.report_failed', ['count' => count($report['failed'])]) }}
                                </span>
                            @endif
                        </div>

                        @if(count($report['skipped']) > 0)
                            <details class="fleet-import-report__details">
                                <summary>{{ __('messages.imports.report_skipped', ['count' => count($report['skipped'])]) }}</summary>
                                <table class="fleet-import-report__table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('messages.imports.report_row') }}</th>
                                            <th>{{ __('messages.imports.report_dqn') }}</th>
                                            <th>{{ __('messages.imports.report_reason') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($report['skipped'] as $item)
                                            <tr>
                                                <td>{{ $item['row'] }}</td>
                                                <td><code>{{ $item['dqn'] }}</code></td>
                                                <td>{{ $item['reason'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </details>
                        @endif

                        @if(count($report['failed']) > 0)
                            <details class="fleet-import-report__details" open>
                                <summary>{{ __('messages.imports.report_failed', ['count' => count($report['failed'])]) }}</summary>
                                <table class="fleet-import-report__table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('messages.imports.report_row') }}</th>
                                            <th>{{ __('messages.imports.report_dqn') }}</th>
                                            <th>{{ __('messages.imports.report_error') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($report['failed'] as $item)
                                            <tr>
                                                <td>{{ $item['row'] }}</td>
                                                <td><code>{{ $item['dqn'] }}</code></td>
                                                <td>{{ $item['reason'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </details>
                        @endif
                    </div>
                @endif
                @yield('content')
            </main>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const menuToggle = document.querySelector('.fleet-menu-toggle');
        if (menuToggle) {
            menuToggle.addEventListener('click', () => {
                const isOpen = document.body.classList.toggle('fleet-sidebar-open');
                menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        }

        const themeToggle = document.querySelector('[data-role="theme-toggle"]');
        const syncThemeToggle = () => {
            const isDark = document.documentElement.dataset.fleetTheme === 'dark';
            if (themeToggle) {
                themeToggle.setAttribute('aria-label', isDark ? @json(__('messages.common.theme_light')) : @json(__('messages.common.theme_dark')));
                themeToggle.innerHTML = '<i class="fas fa-' + (isDark ? 'sun' : 'moon') + '"></i>';
            }
        };
        syncThemeToggle();
        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                const nextTheme = document.documentElement.dataset.fleetTheme === 'dark' ? 'light' : 'dark';
                if (nextTheme === 'dark') {
                    document.documentElement.dataset.fleetTheme = 'dark';
                } else {
                    delete document.documentElement.dataset.fleetTheme;
                }
                localStorage.setItem('fleet-theme', nextTheme);
                syncThemeToggle();
            });
        }
    </script>
    @yield('scripts')
    @stack('scripts')
</body>
</html>