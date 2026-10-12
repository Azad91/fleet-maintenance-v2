<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') · Fleet Control</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <style>
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #0a1628;
            background-image:
                radial-gradient(ellipse 60% 50% at 20% 10%, rgba(37,99,235,.35), transparent 60%),
                radial-gradient(ellipse 50% 50% at 85% 80%, rgba(56,189,248,.20), transparent 60%);
            color: #fff;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .error-card {
            max-width: 480px;
            width: 100%;
            text-align: center;
            padding: 48px 32px;
            background: rgba(15, 31, 53, 0.9);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(16px);
            box-shadow: 0 40px 80px rgba(0, 0, 0, 0.4);
        }
        .error-icon { font-size: 44px; color: #60a5fa; margin-bottom: 16px; }
        .error-code {
            font-size: 72px; font-weight: 800; line-height: 1; letter-spacing: -0.04em;
            background: linear-gradient(180deg, #fff 0%, #60a5fa 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .error-title { font-size: 22px; font-weight: 700; margin: 16px 0 12px; letter-spacing: -0.5px; }
        .error-message { font-size: 14px; color: rgba(255, 255, 255, 0.65); line-height: 1.65; margin: 0 0 24px; }
        .error-actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        .error-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 11px 20px; border-radius: 9px; font-size: 13px; font-weight: 700;
            text-decoration: none; transition: all 0.15s ease; border: none; cursor: pointer;
        }
        .error-btn--primary { background: #2563eb; color: #fff; box-shadow: 0 8px 24px rgba(37, 99, 235, 0.25); }
        .error-btn--primary:hover { background: #1d4ed8; color: #fff; transform: translateY(-1px); }
        .error-btn--ghost { background: rgba(255, 255, 255, 0.08); color: #fff; border: 1px solid rgba(255, 255, 255, 0.18); }
        .error-btn--ghost:hover { background: rgba(255, 255, 255, 0.14); color: #fff; }
        .error-logo {
            display: inline-flex; align-items: center; gap: 8px;
            font-weight: 800; font-size: 14px; color: rgba(255, 255, 255, 0.5);
            margin-bottom: 24px; text-decoration: none;
        }
        .error-logo i { color: #60a5fa; }
    </style>
</head>
<body>
    <div class="error-card">
        <a href="{{ url('/') }}" class="error-logo">
            <i class="fas fa-bus"></i><span>Fleet Control</span>
        </a>
        <div class="error-icon"><i class="fas @yield('icon', 'fa-circle-exclamation')"></i></div>
        <div class="error-code">@yield('code', '500')</div>
        <h1 class="error-title">@yield('title', 'An error occurred')</h1>
        <p class="error-message">@yield('message', 'An unexpected error occurred.')</p>
        <div class="error-actions">
            <a href="{{ url('/') }}" class="error-btn error-btn--primary"><i class="fas fa-house"></i> Home</a>
            @auth
                <a href="javascript:history.back()" class="error-btn error-btn--ghost"><i class="fas fa-arrow-left"></i> Back</a>
            @endauth
        </div>
    </div>
</body>
</html>
