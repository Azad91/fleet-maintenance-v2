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
<body class="fleet-guest">
    <header class="fleet-guest__header">
        <a href="{{ url('/') }}" class="fleet-brand text-decoration-none">
            <span class="fleet-brand__mark"><i class="fas fa-bus"></i></span>
            <span>
                <strong>Fleet</strong>
                <span class="fleet-brand__accent">Control</span>
                <small>MAINTENANCE SYSTEM</small>
            </span>
        </a>
        @auth
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="fleet-guest__logout" type="submit">
                    <i class="fas fa-arrow-right-from-bracket"></i> Çıxış
                </button>
            </form>
        @endauth
    </header>
    <main class="fleet-guest__main">
        @yield('content')
    </main>
    <footer class="fleet-guest__footer">
        &copy; {{ date('Y') }} Fleet Control · Nəqliyyat parkının idarəetmə sistemi
    </footer>
    @stack('scripts')
</body>
</html>
