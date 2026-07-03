@php
    // The referrer is the form the user was on. Falling back to home avoids an
    // empty href if the browser stripped Referer (privacy modes, cross-origin).
    $retryUrl = url()->previous() ?: url('/');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Session timed out') }} · {{ config('app.name', 'OT1-Pro') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon-32.png" type="image/png" sizes="32x32">
    <link rel="icon" href="/favicon-16.png" type="image/png" sizes="16x16">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            min-height: 100vh;
            color: #fff;
            background: linear-gradient(135deg, #0A0A0F 0%, #0D0D1A 30%, #111127 60%, #0A0A0F 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            -webkit-font-smoothing: antialiased;
        }
        .card {
            width: 100%;
            max-width: 460px;
            background: rgba(10, 10, 20, 0.75);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 20px;
            padding: 40px 32px 32px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
            text-align: center;
        }
        .logo {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            object-fit: cover;
            margin: 0 auto 20px;
            display: block;
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.35);
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #C4B5FD;
            background: rgba(124, 58, 237, 0.12);
            border: 1px solid rgba(124, 58, 237, 0.30);
            border-radius: 999px;
            margin-bottom: 14px;
        }
        h1 {
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 10px;
            letter-spacing: -0.01em;
        }
        p {
            font-size: 14px;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.60);
            margin: 0 0 24px;
        }
        .actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px 18px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 12px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: transform 0.08s ease, background 0.15s ease, border-color 0.15s ease;
        }
        .btn:active { transform: translateY(1px); }
        .btn-primary {
            color: #fff;
            background: linear-gradient(135deg, #7C3AED 0%, #6D28D9 100%);
            box-shadow: 0 4px 16px rgba(124, 58, 237, 0.35);
        }
        .btn-primary:hover { background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%); }
        .btn-secondary {
            color: rgba(255, 255, 255, 0.75);
            background: transparent;
            border-color: rgba(255, 255, 255, 0.15);
        }
        .btn-secondary:hover {
            color: #fff;
            border-color: rgba(255, 255, 255, 0.25);
            background: rgba(255, 255, 255, 0.04);
        }
        .foot {
            margin-top: 22px;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.30);
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
        .icon { width: 16px; height: 16px; flex-shrink: 0; }
    </style>
</head>
<body>
    <main class="card" role="main">
        <img src="/logo.png" alt="{{ config('app.name', 'OT1-Pro') }}" class="logo">
        <span class="badge">{{ __('Session expired') }}</span>
        <h1>{{ __('Your session timed out') }}</h1>
        <p>{{ __("You've been away for a while, so we protected your account by expiring the page. Reload to get a fresh session and try again.") }}</p>

        <div class="actions">
            <a href="{{ $retryUrl }}" class="btn btn-primary" rel="noopener">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12a9 9 0 1 1-3.2-6.9"></path>
                    <polyline points="21 4 21 10 15 10"></polyline>
                </svg>
                {{ __('Reload page') }}
            </a>
            <a href="{{ url('/') }}" class="btn btn-secondary">{{ __('Back to home') }}</a>
        </div>

        <p class="foot">Error 419 · Page expired</p>
    </main>
</body>
</html>
