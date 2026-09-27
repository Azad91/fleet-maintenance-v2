<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Fleet OS — Avtobus parkınızın əməliyyat sistemi</title>
    <meta name="description" content="Fleet OS — nasazlıq kartları, anbar transferləri, yağ dəyişmələri və 30+ hesabat bir platformada. Multi-tenant, çoxdilli, enterprise-grade.">

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- FAVICONS                                                   --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <meta name="theme-color" content="#0a1628">

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- OPEN GRAPH / SOCIAL MEDIA                                  --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="Fleet OS — Avtobus parkınızın əməliyyat sistemi">
    <meta property="og:description" content="Nasazlıq kartları, anbar transferləri, yağ dəyişmələri və 30+ hesabat bir platformada.">
    <meta property="og:image" content="{{ asset('og-image.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:site_name" content="Fleet OS">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Fleet OS — Avtobus parkınızın əməliyyat sistemi">
    <meta name="twitter:description" content="Nasazlıq kartları, anbar transferləri, yağ dəyişmələri və 30+ hesabat bir platformada.">
    <meta name="twitter:image" content="{{ asset('og-image.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">

    <style>
        /* ═══════════════════════════════════════════════════════════
           FLEET OS — LANDING DESIGN SYSTEM v2
           ═══════════════════════════════════════════════════════════ */
        :root {
            --navy:      #0a1628;
            --navy-2:    #0f1f35;
            --navy-3:    #17273f;
            --blue:      #2563eb;
            --blue-2:    #3b82f6;
            --sky:       #38bdf8;
            --gold:      #d4a74c;
            --emerald:   #10b981;
            --rose:      #ef4444;

            --bg:        #ffffff;
            --bg-soft:   #f8fafc;
            --bg-muted:  #f1f5f9;
            --border:    #e2e8f0;

            --text:      #0f172a;
            --text-2:    #334155;
            --muted:     #64748b;
            --muted-2:   #94a3b8;

            --radius-sm: 8px;
            --radius:    12px;
            --radius-lg: 20px;
            --radius-xl: 28px;

            --shadow-sm: 0 1px 2px rgba(15,23,42,.06);
            --shadow:    0 4px 24px rgba(15,23,42,.06);
            --shadow-lg: 0 24px 60px rgba(15,23,42,.10);
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }

        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: var(--text);
            background: var(--bg);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        h1, h2, h3, h4 { letter-spacing: -0.02em; margin: 0; }
        h1 { font-size: clamp(2.2rem, 5vw, 4rem); font-weight: 800; line-height: 1.05; }
        h2 { font-size: clamp(1.8rem, 3.5vw, 2.75rem); font-weight: 800; line-height: 1.15; }
        h3 { font-size: clamp(1.25rem, 2vw, 1.5rem); font-weight: 700; }

        a { text-decoration: none; }

        .container-os {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .eyebrow--dark  { background: rgba(59,130,246,.15); color: #93c5fd; border: 1px solid rgba(59,130,246,.3); }
        .eyebrow--light { background: rgba(37,99,235,.08); color: var(--blue); border: 1px solid rgba(37,99,235,.15); }

        .text-muted-os { color: var(--muted); }

        /* ─── LOGO COMPONENTS ─── */
        .logo-os {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            font-size: 18px;
            letter-spacing: -0.02em;
            color: #fff;
            text-decoration: none;
        }
        .logo-os__mark {
            width: 34px;
            height: 34px;
            flex-shrink: 0;
            border-radius: 9px;
            background: linear-gradient(135deg, var(--blue), var(--sky));
            color: #fff;
            display: grid;
            place-items: center;
            font-size: 16px;
            box-shadow: 0 8px 20px rgba(37,99,235,.35);
        }
        .logo-os__text {
            display: inline-flex;
            align-items: baseline;
            gap: 3px;
        }
        .logo-os__text span:last-child { color: #60a5fa; font-weight: 500; }

        /* ─── Buttons ─── */
        .btn-os {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 13px 24px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 15px;
            border: none;
            cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
            white-space: nowrap;
        }
        .btn-os:hover { transform: translateY(-1px); }

        .btn-os--primary {
            background: var(--blue);
            color: #fff;
            box-shadow: 0 8px 24px rgba(37,99,235,.25);
        }
        .btn-os--primary:hover { background: #1d4ed8; color: #fff; box-shadow: 0 12px 32px rgba(37,99,235,.35); }

        .btn-os--ghost {
            background: rgba(255,255,255,.08);
            color: #fff;
            border: 1px solid rgba(255,255,255,.18);
            backdrop-filter: blur(8px);
        }
        .btn-os--ghost:hover { background: rgba(255,255,255,.14); color: #fff; }

        .btn-os--outline {
            background: #fff;
            color: var(--text);
            border: 1px solid var(--border);
        }
        .btn-os--outline:hover { border-color: var(--blue); color: var(--blue); }

        .btn-os--lg { padding: 16px 32px; font-size: 16px; }

        /* ─── NAV ─── */
        .nav-os {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(10,22,40,.85);
            backdrop-filter: saturate(180%) blur(20px);
            border-bottom: 1px solid rgba(255,255,255,.06);
            transition: all .2s ease;
        }
        .nav-os__inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 68px;
        }
        .nav-os__links {
            display: none;
            align-items: center;
            gap: 32px;
        }
        .nav-os__links a {
            color: rgba(255,255,255,.75);
            font-size: 14px;
            font-weight: 600;
            transition: color .15s;
        }
        .nav-os__links a:hover { color: #fff; }

        @media (min-width: 900px) { .nav-os__links { display: flex; } }

        /* ─── HERO ─── */
        .hero {
            position: relative;
            background: var(--navy);
            color: #fff;
            padding: 90px 0 120px;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 50% at 20% 10%, rgba(37,99,235,.35), transparent 60%),
                radial-gradient(ellipse 50% 50% at 85% 80%, rgba(56,189,248,.20), transparent 60%);
            pointer-events: none;
        }
        .hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse at center, black 40%, transparent 75%);
            -webkit-mask-image: radial-gradient(ellipse at center, black 40%, transparent 75%);
            pointer-events: none;
        }
        .hero__inner {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 820px;
            margin: 0 auto;
        }
        .hero h1 {
            margin: 22px 0 20px;
            background: linear-gradient(180deg, #fff 0%, #b9d4ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero__sub {
            font-size: clamp(1rem, 1.5vw, 1.2rem);
            color: rgba(255,255,255,.72);
            max-width: 620px;
            margin: 0 auto 36px;
            line-height: 1.65;
        }
        .hero__cta {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 60px;
        }
        .hero__stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            max-width: 640px;
            margin: 0 auto;
            padding-top: 40px;
            border-top: 1px solid rgba(255,255,255,.08);
        }
        @media (min-width: 720px) { .hero__stats { grid-template-columns: repeat(4, 1fr); } }
        .hero__stat strong {
            display: block;
            font-size: 26px;
            font-weight: 800;
            background: linear-gradient(180deg, #fff, #93c5fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero__stat span {
            display: block;
            font-size: 12px;
            color: rgba(255,255,255,.55);
            font-weight: 600;
            margin-top: 4px;
            letter-spacing: 0.03em;
        }

        .hero__mockup {
            position: relative;
            max-width: 980px;
            margin: 40px auto 0;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,.10);
            background: linear-gradient(180deg, rgba(255,255,255,.05), rgba(255,255,255,.02));
            padding: 12px;
            box-shadow: 0 40px 100px rgba(0,0,0,.4);
        }
        .hero__mockup-bar {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 8px 14px;
        }
        .hero__mockup-dot { width: 10px; height: 10px; border-radius: 50%; }
        .hero__mockup-content {
            background: #0f1f35;
            border-radius: 10px;
            aspect-ratio: 16 / 9;
            display: grid;
            place-items: center;
            color: rgba(255,255,255,.25);
            font-size: 14px;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,.05);
        }

        section.os-section { padding: 90px 0; }
        .os-section--soft { background: var(--bg-soft); }
        .os-section--dark { background: var(--navy-2); color: #fff; }

        .os-heading {
            max-width: 720px;
            margin: 0 auto 60px;
            text-align: center;
        }
        .os-heading h2 { margin: 16px 0 14px; }
        .os-heading p { font-size: 17px; color: var(--muted); margin: 0; }
        .os-section--dark .os-heading p { color: rgba(255,255,255,.65); }
        .os-section--dark .os-heading h2 { color: #fff; }

        .problem-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        @media (min-width: 720px)  { .problem-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1000px) { .problem-grid { grid-template-columns: repeat(4, 1fr); } }

        .problem-card {
            padding: 26px 24px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            transition: all .2s ease;
        }
        .problem-card:hover {
            border-color: rgba(239,68,68,.35);
            box-shadow: 0 12px 32px rgba(239,68,68,.06);
            transform: translateY(-2px);
        }
        .problem-card__icon {
            width: 44px; height: 44px;
            display: grid; place-items: center;
            border-radius: 10px;
            background: #fef2f2;
            color: var(--rose);
            font-size: 18px;
            margin-bottom: 16px;
        }
        .problem-card h4 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .problem-card p {
            font-size: 14px;
            color: var(--muted);
            margin: 0;
            line-height: 1.55;
        }

        .pillars {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }
        @media (min-width: 900px) { .pillars { grid-template-columns: repeat(3, 1fr); } }

        .pillar {
            position: relative;
            padding: 34px 28px;
            background: linear-gradient(180deg, #fff, #fafbfd);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: all .25s ease;
        }
        .pillar::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--blue), var(--sky));
        }
        .pillar:hover {
            transform: translateY(-4px);
            border-color: rgba(37,99,235,.25);
            box-shadow: 0 24px 60px rgba(37,99,235,.08);
        }
        .pillar__icon {
            width: 56px; height: 56px;
            display: grid; place-items: center;
            border-radius: 14px;
            background: linear-gradient(135deg, #dbeafe, #eff6ff);
            color: var(--blue);
            font-size: 22px;
            margin-bottom: 20px;
        }
        .pillar h3 { font-size: 20px; margin-bottom: 12px; }
        .pillar p {
            color: var(--muted);
            font-size: 15px;
            margin: 0 0 18px;
            line-height: 1.6;
        }
        .pillar ul { list-style: none; padding: 0; margin: 0; }
        .pillar li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 6px 0;
            font-size: 14px;
            color: var(--text-2);
        }
        .pillar li i {
            color: var(--emerald);
            font-size: 14px;
            margin-top: 4px;
            flex-shrink: 0;
        }

        .features-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }
        @media (min-width: 640px)  { .features-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1000px) { .features-grid { grid-template-columns: repeat(3, 1fr); } }

        .feature {
            padding: 24px 22px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            transition: all .2s;
        }
        .feature:hover {
            border-color: rgba(37,99,235,.25);
            box-shadow: 0 10px 30px rgba(15,23,42,.05);
        }
        .feature__icon {
            width: 40px; height: 40px;
            display: grid; place-items: center;
            border-radius: 9px;
            background: var(--bg-muted);
            color: var(--blue);
            font-size: 16px;
            margin-bottom: 14px;
        }
        .feature h4 { font-size: 15px; font-weight: 700; margin-bottom: 6px; }
        .feature p { font-size: 13.5px; color: var(--muted); margin: 0; line-height: 1.55; }

        .role-tabs {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 28px;
        }
        .role-tab {
            padding: 10px 20px;
            border: 1px solid rgba(255,255,255,.12);
            background: transparent;
            color: rgba(255,255,255,.75);
            border-radius: 999px;
            font-weight: 600;
            font-size: 13.5px;
            cursor: pointer;
            transition: all .18s ease;
        }
        .role-tab:hover { color: #fff; background: rgba(255,255,255,.06); }
        .role-tab.is-active {
            background: #fff;
            color: var(--navy);
            border-color: #fff;
        }

        .role-panel {
            display: none;
            padding: 34px;
            background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
            border: 1px solid rgba(255,255,255,.10);
            border-radius: var(--radius-lg);
        }
        .role-panel.is-active { display: block; animation: fadeUp .3s ease; }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .role-panel__title { display: flex; align-items: center; gap: 14px; margin-bottom: 22px; }
        .role-panel__icon {
            width: 48px; height: 48px;
            display: grid; place-items: center;
            border-radius: 12px;
            background: rgba(59,130,246,.15);
            color: #93c5fd;
            font-size: 20px;
        }
        .role-panel__title h3 { color: #fff; margin: 0; }
        .role-panel__title p { color: rgba(255,255,255,.55); margin: 2px 0 0; font-size: 13px; }
        .role-panel__grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
        }
        @media (min-width: 720px) { .role-panel__grid { grid-template-columns: repeat(2, 1fr); } }
        .role-feature {
            padding: 16px 18px;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 10px;
            color: rgba(255,255,255,.85);
            font-size: 14px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .role-feature i { color: var(--sky); margin-top: 3px; flex-shrink: 0; }

        .reports-showcase {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        @media (min-width: 720px)  { .reports-showcase { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1000px) { .reports-showcase { grid-template-columns: repeat(3, 1fr); } }

        .report-category {
            padding: 24px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            transition: all .2s;
        }
        .report-category:hover {
            border-color: rgba(37,99,235,.25);
            box-shadow: 0 12px 30px rgba(15,23,42,.06);
        }
        .report-category__header { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
        .report-category__header i {
            width: 38px; height: 38px;
            display: grid; place-items: center;
            border-radius: 9px;
            background: #eff6ff;
            color: var(--blue);
            font-size: 15px;
        }
        .report-category__header h4 { font-size: 15px; font-weight: 700; margin: 0; }
        .report-category__header small {
            display: block;
            font-size: 11px;
            color: var(--muted);
            font-weight: 500;
            margin-top: 2px;
        }
        .report-list { display: flex; flex-wrap: wrap; gap: 6px; }
        .report-tag {
            padding: 4px 10px;
            background: var(--bg-muted);
            border-radius: 6px;
            font-size: 11.5px;
            color: var(--text-2);
            font-weight: 500;
        }

        .trust-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }
        @media (min-width: 900px) { .trust-grid { grid-template-columns: 1fr 1fr; } }

        .trust-panel {
            padding: 34px 30px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
        }
        .trust-panel__icon {
            width: 52px; height: 52px;
            display: grid; place-items: center;
            border-radius: 14px;
            background: linear-gradient(135deg, #ecfdf5, #f0fdf4);
            color: var(--emerald);
            font-size: 22px;
            margin-bottom: 20px;
        }
        .trust-panel h3 { font-size: 19px; margin-bottom: 12px; }
        .trust-panel p { color: var(--muted); font-size: 14.5px; margin: 0 0 18px; line-height: 1.6; }
        .trust-panel ul { list-style: none; padding: 0; margin: 0; }
        .trust-panel li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 6px 0;
            font-size: 14px;
            color: var(--text-2);
        }
        .trust-panel li i { color: var(--emerald); margin-top: 4px; font-size: 13px; flex-shrink: 0; }

        .pricing-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            max-width: 1000px;
            margin: 0 auto;
        }
        @media (min-width: 900px) { .pricing-grid { grid-template-columns: repeat(3, 1fr); } }

        .price-card {
            position: relative;
            padding: 32px 28px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            display: flex;
            flex-direction: column;
            transition: all .2s;
        }
        .price-card--featured {
            border: 2px solid var(--blue);
            box-shadow: 0 24px 60px rgba(37,99,235,.10);
            transform: scale(1.02);
        }
        @media (min-width: 900px) { .price-card--featured { transform: scale(1.04); } }
        .price-card__badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            padding: 5px 14px;
            background: var(--blue);
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.05em;
            border-radius: 999px;
            text-transform: uppercase;
        }
        .price-card__name {
            font-size: 14px;
            font-weight: 700;
            color: var(--blue);
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .price-card__price { display: flex; align-items: baseline; gap: 6px; margin-bottom: 8px; }
        .price-card__price strong { font-size: 40px; font-weight: 800; letter-spacing: -0.03em; }
        .price-card__price span { color: var(--muted); font-size: 13px; }
        .price-card__desc { font-size: 14px; color: var(--muted); margin-bottom: 24px; }
        .price-card ul {
            list-style: none;
            padding: 0;
            margin: 0 0 24px;
            flex-grow: 1;
        }
        .price-card li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 7px 0;
            font-size: 14px;
            color: var(--text-2);
        }
        .price-card li i { color: var(--blue); margin-top: 4px; font-size: 13px; flex-shrink: 0; }
        .price-card__cta { width: 100%; justify-content: center; }

        .faq-list { max-width: 780px; margin: 0 auto; }
        .faq-item { border-bottom: 1px solid var(--border); padding: 22px 0; }
        .faq-item:first-child { border-top: 1px solid var(--border); }
        .faq-item summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            list-style: none;
            font-weight: 700;
            font-size: 16px;
            color: var(--text);
        }
        .faq-item summary::-webkit-details-marker { display: none; }
        .faq-item summary::after {
            content: '+';
            font-size: 22px;
            font-weight: 400;
            color: var(--muted);
            transition: transform .2s;
        }
        .faq-item[open] summary::after { transform: rotate(45deg); }
        .faq-item p { margin: 14px 0 0; color: var(--muted); font-size: 14.5px; line-height: 1.65; }

        .cta-band {
            position: relative;
            padding: 80px 0;
            background: var(--navy);
            color: #fff;
            overflow: hidden;
        }
        .cta-band::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(ellipse at center, rgba(37,99,235,.30), transparent 60%);
        }
        .cta-band__inner {
            position: relative; z-index: 1;
            text-align: center;
            max-width: 640px;
            margin: 0 auto;
        }
        .cta-band h2 { color: #fff; margin-bottom: 16px; }
        .cta-band p { color: rgba(255,255,255,.7); font-size: 17px; margin-bottom: 32px; }

        .footer-os {
            background: #060d1c;
            color: rgba(255,255,255,.6);
            padding: 60px 0 30px;
            font-size: 14px;
        }
        .footer-os__grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        @media (min-width: 720px) { .footer-os__grid { grid-template-columns: 2fr 1fr 1fr 1fr; } }
        .footer-os h5 {
            color: #fff; font-size: 13px; font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 16px;
        }
        .footer-os a {
            display: block;
            color: rgba(255,255,255,.6);
            padding: 5px 0;
            transition: color .15s;
        }
        .footer-os a:hover { color: #fff; }
        .footer-os__bottom {
            padding-top: 28px;
            border-top: 1px solid rgba(255,255,255,.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            font-size: 12.5px;
        }

        .reveal {
            opacity: 0;
            transform: translateY(16px);
            transition: opacity .6s ease, transform .6s ease;
        }
        .reveal.is-visible { opacity: 1; transform: translateY(0); }
    </style>
</head>
<body>

{{-- ═══ NAV ═══ --}}
<nav class="nav-os">
    <div class="container-os">
        <div class="nav-os__inner">
            <a href="/" class="logo-os" aria-label="Fleet OS — Ana səhifə">
                <span class="logo-os__mark">
                    <svg width="20" height="20" viewBox="0 0 40 40" fill="none" aria-hidden="true">
                        <path d="M14 14h13v4.5h-8.5v4h6.5v4.5h-6.5V34h-4.5V14z" fill="currentColor"/>
                    </svg>
                </span>
                <span class="logo-os__text">
                    <span>Fleet</span>
                    <span>OS</span>
                </span>
            </a>
            <div class="nav-os__links">
                <a href="#features">Funksiyalar</a>
                <a href="#reports">Hesabatlar</a>
                <a href="#roles">Rollar</a>
                <a href="#pricing">Qiymət</a>
                <a href="#faq">FAQ</a>
            </div>
            <div style="display:flex;gap:10px;align-items:center">
                <a href="{{ route('login') }}" class="btn-os btn-os--ghost" style="padding:10px 18px;font-size:14px">
                    Daxil ol
                </a>
                <a href="#demo" class="btn-os btn-os--primary" style="padding:10px 18px;font-size:14px">
                    Demo istə
                </a>
            </div>
        </div>
    </div>
</nav>

{{-- ═══ HERO ═══ --}}
<header class="hero">
    <div class="container-os">
        <div class="hero__inner">
            <span class="eyebrow eyebrow--dark">
                <i class="fas fa-bolt"></i>
                Erkən əlçatanlıq açıqdır
            </span>
            <h1>Avtobus parkınızın<br>əməliyyat sistemi</h1>
            <p class="hero__sub">
                Nasazlıq kartlarından anbar transferlərinə, yağ dəyişmələrindən 30+ hesabata qədər —
                fleet əməliyyatlarınızın hamısı bir platformada. Çoxşirkətli, çoxqarajlı, çoxdilli.
            </p>
            <div class="hero__cta">
                <a href="#demo" class="btn-os btn-os--primary btn-os--lg">
                    <i class="fas fa-play"></i> Canlı demo istə
                </a>
                <a href="#features" class="btn-os btn-os--ghost btn-os--lg">
                    Funksiyalara bax <i class="fas fa-arrow-down"></i>
                </a>
            </div>
        </div>

        <div class="hero__mockup">
            <div class="hero__mockup-bar">
                <span class="hero__mockup-dot" style="background:#ef4444"></span>
                <span class="hero__mockup-dot" style="background:#f59e0b"></span>
                <span class="hero__mockup-dot" style="background:#10b981"></span>
            </div>
            <div class="hero__mockup-content">
                <span style="opacity:.5">📊 Dashboard önizləmə</span>
            </div>
        </div>

        <div class="hero__stats">
            <div class="hero__stat"><strong>30+</strong><span>Hazır hesabat</span></div>
            <div class="hero__stat"><strong>4</strong><span>Dil dəstəyi</span></div>
            <div class="hero__stat"><strong>∞</strong><span>Qaraj & İstifadəçi</span></div>
            <div class="hero__stat"><strong>100%</strong><span>Audit izlənə bilən</span></div>
        </div>
    </div>
</header>

{{-- ═══ PROBLEM ═══ --}}
<section class="os-section os-section--soft" id="problem">
    <div class="container-os">
        <div class="os-heading reveal">
            <span class="eyebrow eyebrow--light">Problem</span>
            <h2>Fleet idarəsi hələ də Excel-də qalıb?</h2>
            <p>Orta ölçülü avtobus parklarında hər gün itirilən vaxt, stok və məlumat.</p>
        </div>
        <div class="problem-grid reveal">
            <div class="problem-card">
                <div class="problem-card__icon"><i class="fas fa-file-excel"></i></div>
                <h4>Excel cəngəlliyi</h4>
                <p>5 fərqli fayl, 3 fərqli versiya, "sonuncu kim yeniləyib?" sualı hər gün.</p>
            </div>
            <div class="problem-card">
                <div class="problem-card__icon"><i class="fas fa-cubes"></i></div>
                <h4>İtən stok</h4>
                <p>Anbardan çıxan detal kartda görünmür. Ayın sonunda 20% fərq çıxır.</p>
            </div>
            <div class="problem-card">
                <div class="problem-card__icon"><i class="fas fa-file-signature"></i></div>
                <h4>Kağız aktlar</h4>
                <p>İmzalanan aktlar qovluqda itir, heç kim geriyə baxa bilmir.</p>
            </div>
            <div class="problem-card">
                <div class="problem-card__icon"><i class="fas fa-users-slash"></i></div>
                <h4>Kor idarəetmə</h4>
                <p>Direktor qarajda nə baş verdiyini bilmir. Hesabat üçün 1 həftə gözləyir.</p>
            </div>
        </div>
    </div>
</section>

{{-- ═══ PILLARS ═══ --}}
<section class="os-section" id="pillars">
    <div class="container-os">
        <div class="os-heading reveal">
            <span class="eyebrow eyebrow--light">Həll</span>
            <h2>Üç sütun, bir sistem</h2>
            <p>Fleet OS-u fleet əməliyyatlarınızın tam dövrünü əhatə edəcək şəkildə qurmuşuq.</p>
        </div>
        <div class="pillars reveal">
            <div class="pillar">
                <div class="pillar__icon"><i class="fas fa-clipboard-list"></i></div>
                <h3>Kartlar & Aktlar</h3>
                <p>Qəza, nasazlıq və texniki xidmət kartları. Rəqəmsal PDF aktlar, imza yerləri ilə.</p>
                <ul>
                    <li><i class="fas fa-check"></i> 3 növ kart: qəza / nasazlıq / texniki xidmət</li>
                    <li><i class="fas fa-check"></i> Yol və qaraj yerləri üçün ayrı workflow</li>
                    <li><i class="fas fa-check"></i> PDF akt — dərhal çap və ya endirmə</li>
                    <li><i class="fas fa-check"></i> Status FSM: gözləmədə → işdə → həll</li>
                </ul>
            </div>
            <div class="pillar">
                <div class="pillar__icon"><i class="fas fa-boxes-stacked"></i></div>
                <h3>Anbar & Transferlər</h3>
                <p>Qaraj daxili anbar, servis maşını stoku və qarajlar arası tam transfer workflow.</p>
                <ul>
                    <li><i class="fas fa-check"></i> Kartdan çıxan detal → stok avtomatik azalır</li>
                    <li><i class="fas fa-check"></i> Servis maşınları üçün mobil stok</li>
                    <li><i class="fas fa-check"></i> Karantin — nasaz detallar üçün ayrı vedrə</li>
                    <li><i class="fas fa-check"></i> Dispatch → Qəbul / Rədd → Həll workflow</li>
                </ul>
            </div>
            <div class="pillar">
                <div class="pillar__icon"><i class="fas fa-chart-line"></i></div>
                <h3>Hesabat & Analitika</h3>
                <p>30+ hazır hesabat: xərc, dayanma müddəti, təkrarlanan nasazlıq, utilizasiya və daha çoxu.</p>
                <ul>
                    <li><i class="fas fa-check"></i> Hər hesabat Excel-ə ixrac olunur</li>
                    <li><i class="fas fa-check"></i> Dövr filtri: günlük / həftəlik / aylıq / xüsusi</li>
                    <li><i class="fas fa-check"></i> Marka üzrə filtr</li>
                    <li><i class="fas fa-check"></i> Direktor üçün şirkət üzrə cəmlənmiş</li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- ═══ FEATURES ═══ --}}
<section class="os-section os-section--soft" id="features">
    <div class="container-os">
        <div class="os-heading reveal">
            <span class="eyebrow eyebrow--light">Funksiyalar</span>
            <h2>Hər detala fikir verilmiş</h2>
            <p>Sadəcə "feature list" deyil — hər biri real fleet operatorunun gündəlik işindən doğub.</p>
        </div>
        <div class="features-grid reveal">
            <div class="feature"><div class="feature__icon"><i class="fas fa-bus"></i></div><h4>Avtobus kataloqu</h4><p>DQN, VIN, xətt nömrəsi, marka, yürüş. Excel idxal ilə 500 avtobusu 5 dəqiqədə yüklə.</p></div>
            <div class="feature"><div class="feature__icon"><i class="fas fa-gas-pump"></i></div><h4>Yağ dəyişmə izləmə</h4><p>Motor, korobka və most üçün ayrı intervallar. Gecikən avtobuslar avtomatik qırmızıya keçir.</p></div>
            <div class="feature"><div class="feature__icon"><i class="fas fa-gauge-high"></i></div><h4>Günlük KM qeydləri</h4><p>Hər gün hər avtobus üçün yürüş. Excel-dən toplu idxal, günlük fərq avtomatik hesablanır.</p></div>
            <div class="feature"><div class="feature__icon"><i class="fas fa-flag-checkered"></i></div><h4>Günlük statuslar</h4><p>Xəttə hazır, təmirdə, istismara yararsız. Aylıq xülasə və tarixçə hər avtobus səhifəsində.</p></div>
            <div class="feature"><div class="feature__icon"><i class="fas fa-truck-medical"></i></div><h4>Servis maşınları</h4><p>Yolda xidmət üçün mobil anbarlar. Hər maşının öz stoku, öz sürücüsü, öz hərəkəti.</p></div>
            <div class="feature"><div class="feature__icon"><i class="fas fa-shield-exclamation"></i></div><h4>Karantin sistemi</h4><p>Nasaz detallar aktiv stokdan ayrılır. Hesabatlara qarışmır, amma auditdə qalır.</p></div>
            <div class="feature"><div class="feature__icon"><i class="fas fa-file-invoice-dollar"></i></div><h4>Xərc izləmə</h4><p>Hər detalın istifadə anındaki qiyməti snapshot kimi saxlanır. Keçmiş hesabatlar dəyişmir.</p></div>
            <div class="feature"><div class="feature__icon"><i class="fas fa-history"></i></div><h4>Tam audit jurnalı</h4><p>Kim, nə vaxt, nəyi dəyişdi. Hər modelin hər dəyişikliyi avtomatik yazılır.</p></div>
            <div class="feature"><div class="feature__icon"><i class="fas fa-language"></i></div><h4>4 dildə</h4><p>Azərbaycan, İngilis, Rus, Türk. Hər istifadəçi öz dilini seçir, sistem yadda saxlayır.</p></div>
        </div>
    </div>
</section>

{{-- ═══ ROLES ═══ --}}
<section class="os-section os-section--dark" id="roles">
    <div class="container-os">
        <div class="os-heading reveal">
            <span class="eyebrow eyebrow--dark">Rollar</span>
            <h2>Hər kəsə öz görünüşü</h2>
            <p>Direktordan işçiyə qədər — hər rol yalnız öz işinə lazım olanı görür.</p>
        </div>
        <div class="role-tabs reveal">
            <button class="role-tab is-active" data-role="director"><i class="fas fa-user-tie"></i> Direktor</button>
            <button class="role-tab" data-role="admin"><i class="fas fa-user-shield"></i> Qaraj Admin</button>
            <button class="role-tab" data-role="manager"><i class="fas fa-user-gear"></i> Menecer</button>
            <button class="role-tab" data-role="worker"><i class="fas fa-user-hard-hat"></i> İşçi</button>
        </div>

        <div class="role-panel is-active" data-panel="director">
            <div class="role-panel__title">
                <div class="role-panel__icon"><i class="fas fa-user-tie"></i></div>
                <div><h3>Şirkət Direktoru</h3><p>Read-only — bütün şirkəti bir baxışda görür</p></div>
            </div>
            <div class="role-panel__grid">
                <div class="role-feature"><i class="fas fa-check-circle"></i> Şirkət üzrə bütün qarajların KPI-ı</div>
                <div class="role-feature"><i class="fas fa-check-circle"></i> Qaraj-qaraj drill-down</div>
                <div class="role-feature"><i class="fas fa-check-circle"></i> Bütün hesabatlar şirkət üzrə cəmlənmiş</div>
                <div class="role-feature"><i class="fas fa-check-circle"></i> Heç bir data dəyişdirə bilmir — təhlükəsiz</div>
            </div>
        </div>

        <div class="role-panel" data-panel="admin">
            <div class="role-panel__title">
                <div class="role-panel__icon"><i class="fas fa-user-shield"></i></div>
                <div><h3>Qaraj Admini</h3><p>Qarajın tam sahibi — hər şeyə giriş</p></div>
            </div>
            <div class="role-panel__grid">
                <div class="role-feature"><i class="fas fa-check-circle"></i> Avtobus, anbar, işçi, sürücü idarəsi</div>
                <div class="role-feature"><i class="fas fa-check-circle"></i> İstifadəçi yaratmaq və rol vermək</div>
                <div class="role-feature"><i class="fas fa-check-circle"></i> Excel idxal/ixrac tam açıq</div>
                <div class="role-feature"><i class="fas fa-check-circle"></i> Qarajlar arası transferləri idarə etmək</div>
            </div>
        </div>

        <div class="role-panel" data-panel="manager">
            <div class="role-panel__title">
                <div class="role-panel__icon"><i class="fas fa-user-gear"></i></div>
                <div><h3>Menecer</h3><p>Öz domeninin tam sahibi</p></div>
            </div>
            <div class="role-panel__grid">
                <div class="role-feature"><i class="fas fa-check-circle"></i> Kart / Anbar / KM / Status menecerləri</div>
                <div class="role-feature"><i class="fas fa-check-circle"></i> Öz domenində tam CRUD + hesabatlar</div>
                <div class="role-feature"><i class="fas fa-check-circle"></i> İşçilərin fəaliyyətini görə bilir</div>
                <div class="role-feature"><i class="fas fa-check-circle"></i> Digər domenlərə giriş yoxdur</div>
            </div>
        </div>

        <div class="role-panel" data-panel="worker">
            <div class="role-panel__title">
                <div class="role-panel__icon"><i class="fas fa-user-hard-hat"></i></div>
                <div><h3>İşçi</h3><p>Sadəcə öz işini görür</p></div>
            </div>
            <div class="role-panel__grid">
                <div class="role-feature"><i class="fas fa-check-circle"></i> Yalnız öz yaratdığını redaktə edə bilir</div>
                <div class="role-feature"><i class="fas fa-check-circle"></i> PIN ilə giriş — kod + 4 rəqəm</div>
                <div class="role-feature"><i class="fas fa-check-circle"></i> İlk girişdə PIN dəyişmə məcburi</div>
                <div class="role-feature"><i class="fas fa-check-circle"></i> Menecer hesabatlarını görə bilmir</div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ REPORTS ═══ --}}
<section class="os-section" id="reports">
    <div class="container-os">
        <div class="os-heading reveal">
            <span class="eyebrow eyebrow--light">Hesabatlar</span>
            <h2>30+ hazır hesabat, bir kliklə</h2>
            <p>Excel-də saatlarla hesabat qurmaq əvəzinə — hazır cavablar. Hər hesabat ixrac olunur.</p>
        </div>
        <div class="reports-showcase reveal">
            <div class="report-category">
                <div class="report-category__header"><i class="fas fa-clipboard-list"></i><div><h4>Şikayət / Kart</h4><small>5 hesabat</small></div></div>
                <div class="report-list"><span class="report-tag">İcmal</span><span class="report-tag">Top növlər</span><span class="report-tag">Avtobus üzrə</span><span class="report-tag">İşçi fəaliyyəti</span><span class="report-tag">Orta bağlanma vaxtı</span></div>
            </div>
            <div class="report-category">
                <div class="report-category__header"><i class="fas fa-boxes-stacked"></i><div><h4>Anbar</h4><small>6 hesabat</small></div></div>
                <div class="report-list"><span class="report-tag">Qəbul</span><span class="report-tag">İstifadə</span><span class="report-tag">Kritik stok</span><span class="report-tag">Hərəkət tarixçəsi</span><span class="report-tag">Servis maşını</span><span class="report-tag">İşçi fəaliyyəti</span></div>
            </div>
            <div class="report-category">
                <div class="report-category__header"><i class="fas fa-gauge-high"></i><div><h4>Günlük KM</h4><small>3 hesabat</small></div></div>
                <div class="report-list"><span class="report-tag">Qeyd olunmayan</span><span class="report-tag">Top avtobuslar</span><span class="report-tag">İşçi fəaliyyəti</span></div>
            </div>
            <div class="report-category">
                <div class="report-category__header"><i class="fas fa-wrench"></i><div><h4>Texniki Xidmət</h4><small>5 hesabat</small></div></div>
                <div class="report-list"><span class="report-tag">İcmal</span><span class="report-tag">Avtobus üzrə</span><span class="report-tag">Detal üzrə</span><span class="report-tag">Motor yağı</span><span class="report-tag">Ən çox təmir</span></div>
            </div>
            <div class="report-category">
                <div class="report-category__header"><i class="fas fa-heart-pulse"></i><div><h4>Fleet Sağlamlığı</h4><small>6 hesabat</small></div></div>
                <div class="report-list"><span class="report-tag">KM başına xərc</span><span class="report-tag">Boş dayanma</span><span class="report-tag">Təkrarlanan nasazlıq</span><span class="report-tag">Qəzalar</span><span class="report-tag">İstifadə faizi</span><span class="report-tag">Eyni avtobusda təkrar</span></div>
            </div>
            <div class="report-category">
                <div class="report-category__header"><i class="fas fa-arrow-right-arrow-left"></i><div><h4>Transfer</h4><small>6 hesabat</small></div></div>
                <div class="report-list"><span class="report-tag">İcmal</span><span class="report-tag">Ətraflı</span><span class="report-tag">Marşrut üzrə</span><span class="report-tag">Ən çox göndərilən</span><span class="report-tag">Mübahisəli</span><span class="report-tag">İşçi fəaliyyəti</span></div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ SECURITY ═══ --}}
<section class="os-section os-section--soft" id="security">
    <div class="container-os">
        <div class="os-heading reveal">
            <span class="eyebrow eyebrow--light">Etibarlılıq</span>
            <h2>Enterprise səviyyə, startup sadəliyi</h2>
            <p>Bank sistemlərində istifadə olunan pattern-lar — fleet üçün.</p>
        </div>
        <div class="trust-grid reveal">
            <div class="trust-panel">
                <div class="trust-panel__icon"><i class="fas fa-shield-halved"></i></div>
                <h3>Multi-Tenant İzolyasiya</h3>
                <p>Hər şirkət tam ayrıdır. Bir qaraj digər qarajın datasını fiziki olaraq görə bilmir.</p>
                <ul>
                    <li><i class="fas fa-check"></i> Hər model avtomatik qaraj filtri ilə yüklənir</li>
                    <li><i class="fas fa-check"></i> Kontekst yoxdursa — yazma bloklanır</li>
                    <li><i class="fas fa-check"></i> SQL səviyyəsində izolyasiya</li>
                </ul>
            </div>
            <div class="trust-panel">
                <div class="trust-panel__icon"><i class="fas fa-lock"></i></div>
                <h3>Təhlükəsizlik</h3>
                <p>Bank səviyyəli autentifikasiya və hər əməliyyatın izi.</p>
                <ul>
                    <li><i class="fas fa-check"></i> Super Admin üçün iki faktorlu təsdiq (2FA)</li>
                    <li><i class="fas fa-check"></i> Timing-attack qorunması</li>
                    <li><i class="fas fa-check"></i> Rate limiting — IP + hesab səviyyəsində</li>
                    <li><i class="fas fa-check"></i> Tam audit jurnalı (kim nə dəyişdi?)</li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- ═══ PRICING ═══ --}}
<section class="os-section" id="pricing">
    <div class="container-os">
        <div class="os-heading reveal">
            <span class="eyebrow eyebrow--light">Qiymət</span>
            <h2>Ölçünüzə uyğun paket</h2>
            <p>Gizli ödəniş yoxdur. İstənilən vaxt yüksəldə və ya dayandıra bilərsiniz.</p>
        </div>
        <div class="pricing-grid reveal">
            <div class="price-card">
                <div class="price-card__name">Start</div>
                <div class="price-card__price"><strong>₼0</strong><span>/ ilk ay</span></div>
                <p class="price-card__desc">Kiçik fleet üçün başlanğıc paket.</p>
                <ul>
                    <li><i class="fas fa-check"></i> 1 qaraj</li>
                    <li><i class="fas fa-check"></i> 25 avtobusa qədər</li>
                    <li><i class="fas fa-check"></i> 5 istifadəçi</li>
                    <li><i class="fas fa-check"></i> Əsas hesabatlar</li>
                    <li><i class="fas fa-check"></i> Excel idxal/ixrac</li>
                </ul>
                <a href="#demo" class="btn-os btn-os--outline price-card__cta">Pulsuz başla</a>
            </div>
            <div class="price-card price-card--featured">
                <span class="price-card__badge">Ən populyar</span>
                <div class="price-card__name">Business</div>
                <div class="price-card__price"><strong>₼299</strong><span>/ ay</span></div>
                <p class="price-card__desc">Böyüyən fleet üçün tam güc.</p>
                <ul>
                    <li><i class="fas fa-check"></i> 5 qaraja qədər</li>
                    <li><i class="fas fa-check"></i> 250 avtobusa qədər</li>
                    <li><i class="fas fa-check"></i> Limitsiz istifadəçi</li>
                    <li><i class="fas fa-check"></i> Bütün hesabatlar</li>
                    <li><i class="fas fa-check"></i> Qarajlar arası transferlər</li>
                    <li><i class="fas fa-check"></i> Servis maşınları</li>
                    <li><i class="fas fa-check"></i> Prioritet dəstək</li>
                </ul>
                <a href="#demo" class="btn-os btn-os--primary price-card__cta">Demo istə</a>
            </div>
            <div class="price-card">
                <div class="price-card__name">Enterprise</div>
                <div class="price-card__price"><strong>Fərdi</strong></div>
                <p class="price-card__desc">Böyük operatorlar üçün.</p>
                <ul>
                    <li><i class="fas fa-check"></i> Limitsiz qaraj</li>
                    <li><i class="fas fa-check"></i> Limitsiz avtobus</li>
                    <li><i class="fas fa-check"></i> Self-hosted variant</li>
                    <li><i class="fas fa-check"></i> SLA zəmanəti</li>
                    <li><i class="fas fa-check"></i> Fərdi inteqrasiya</li>
                    <li><i class="fas fa-check"></i> Xüsusi təlim</li>
                </ul>
                <a href="#demo" class="btn-os btn-os--outline price-card__cta">Əlaqə saxla</a>
            </div>
        </div>
    </div>
</section>

{{-- ═══ FAQ ═══ --}}
<section class="os-section os-section--soft" id="faq">
    <div class="container-os">
        <div class="os-heading reveal">
            <span class="eyebrow eyebrow--light">FAQ</span>
            <h2>Tez-tez soruşulan suallar</h2>
        </div>
        <div class="faq-list reveal">
            <details class="faq-item" open>
                <summary>Mövcud Excel məlumatlarımızı köçürə bilərikmi?</summary>
                <p>Bəli. Avtobus, anbar, işçi, sürücü, günlük KM və kart məlumatları Excel-dən idxal olunur. Standart sütunlar göstərilir, kifayət qədər Excel faylını atmaqdır.</p>
            </details>
            <details class="faq-item">
                <summary>Bir neçə qarajımız var, necə işləyir?</summary>
                <p>Fleet OS çoxqarajlıdır. Hər qaraj tam izolə edilmiş data ilə işləyir. Üst səviyyədə direktor bütün qarajları bir baxışda görür, amma heç nəyi dəyişdirə bilmir.</p>
            </details>
            <details class="faq-item">
                <summary>İşçilər PIN ilə necə daxil olur?</summary>
                <p>Hər işçiyə işçi kodu + 4 rəqəmli PIN verilir. İlk girişdə PIN dəyişmə məcburidir. Bu, sahədə işləyən işçilər üçün email/parol xatırlamaqdan qat-qat asandır.</p>
            </details>
            <details class="faq-item">
                <summary>PDF aktlar rəsmi sənəd kimi istifadə oluna bilərmi?</summary>
                <p>Bəli. Hər bağlanan kart üçün şirkət başlığı, qaraj məlumatı, avtobus, detallar, işi görən işçi və 3 imza yeri olan rəsmi akt PDF kimi generasiya olunur.</p>
            </details>
            <details class="faq-item">
                <summary>Data harada saxlanılır?</summary>
                <p>İki variant var: bulud (bizim serverlərimizdə) və ya self-hosted (sizin serverlərinizdə). Self-hosted variant Enterprise paketdə mövcuddur.</p>
            </details>
            <details class="faq-item">
                <summary>Hesabatları Excel-ə çıxarmaq mümkündürmü?</summary>
                <p>Bəli. 30+ hesabatın hamısı bir kliklə Excel-ə ixrac olunur. Cari filtrlər (dövr, marka, axtarış) ixraca da tətbiq olunur.</p>
            </details>
        </div>
    </div>
</section>

{{-- ═══ CTA BAND ═══ --}}
<section class="cta-band" id="demo">
    <div class="container-os">
        <div class="cta-band__inner reveal">
            <span class="eyebrow eyebrow--dark">
                <i class="fas fa-rocket"></i>
                Başlamağa hazırsınız?
            </span>
            <h2 style="margin-top:18px">Fleet-inizi 15 dəqiqədə rəqəmsallaşdırın</h2>
            <p>Demo istəyin, komandamız sizinlə əlaqə saxlayacaq və real datanızla sistemi göstərəcək.</p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
                <a href="mailto:hello@fleetos.az?subject=Demo%20İstəyi" class="btn-os btn-os--primary btn-os--lg">
                    <i class="fas fa-envelope"></i> Demo istə
                </a>
                <a href="{{ route('login') }}" class="btn-os btn-os--ghost btn-os--lg">
                    <i class="fas fa-arrow-right"></i> Sistemə daxil ol
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ═══ FOOTER ═══ --}}
<footer class="footer-os">
    <div class="container-os">
        <div class="footer-os__grid">
            <div>
                <a href="/" class="logo-os" style="margin-bottom:14px">
                    <span class="logo-os__mark">
                        <svg width="20" height="20" viewBox="0 0 40 40" fill="none" aria-hidden="true">
                            <path d="M14 14h13v4.5h-8.5v4h6.5v4.5h-6.5V34h-4.5V14z" fill="currentColor"/>
                        </svg>
                    </span>
                    <span class="logo-os__text"><span>Fleet</span><span>OS</span></span>
                </a>
                <p style="color:rgba(255,255,255,.5);font-size:13.5px;max-width:280px;line-height:1.65">
                    Avtobus parklarının texniki xidmətini idarə etmək üçün müasir platforma.
                </p>
            </div>
            <div>
                <h5>Məhsul</h5>
                <a href="#features">Funksiyalar</a>
                <a href="#reports">Hesabatlar</a>
                <a href="#pricing">Qiymət</a>
                <a href="#roles">Rollar</a>
            </div>
            <div>
                <h5>Şirkət</h5>
                <a href="#demo">Əlaqə</a>
                <a href="#">Haqqımızda</a>
                <a href="#">Blog</a>
            </div>
            <div>
                <h5>Hüquqi</h5>
                <a href="#">İstifadə şərtləri</a>
                <a href="#">Məxfilik siyasəti</a>
            </div>
        </div>
        <div class="footer-os__bottom">
            <span>© {{ date('Y') }} Fleet OS. Bütün hüquqlar qorunur.</span>
            <div style="display:flex;gap:16px">
                <a href="#" style="padding:0"><i class="fab fa-linkedin"></i></a>
                <a href="#" style="padding:0"><i class="fab fa-github"></i></a>
                <a href="#" style="padding:0"><i class="fab fa-youtube"></i></a>
            </div>
        </div>
    </div>
</footer>

<script>
    document.querySelectorAll('.role-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const role = tab.dataset.role;
            document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('is-active'));
            document.querySelectorAll('.role-panel').forEach(p => p.classList.remove('is-active'));
            tab.classList.add('is-active');
            document.querySelector(`.role-panel[data-panel="${role}"]`).classList.add('is-active');
        });
    });

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

    const nav = document.querySelector('.nav-os');
    window.addEventListener('scroll', () => {
        nav.style.background = window.scrollY > 20
            ? 'rgba(10,22,40,.95)'
            : 'rgba(10,22,40,.85)';
    }, { passive: true });
</script>

</body>
</html>
