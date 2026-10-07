@php
    /** @var string|null $title */
    /** @var string $badge */
    /** @var string $heading */
    /** @var string $body */
    /** @var string|null $primaryHref */
    /** @var string|null $primaryLabel */
    /** @var string|null $secondaryHref */
    /** @var string|null $secondaryLabel */
    /** @var string $footLabel */
    $title ??= $heading;
    $primaryHref ??= url('/');
    $primaryLabel ??= __('Back to home');
    $secondaryHref ??= null;
    $secondaryLabel ??= null;
    // OT1-Pro is a solo-founder shop — no support@/legal@ aliases. Errors reach
    // Omar directly on his personal email so he can respond without routing.
    $supportEmail = 'omareltak7@gmail.com';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title }} · {{ config('app.name', 'OT1-Pro') }}</title>

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
            width: 56px; height: 56px; border-radius: 14px; object-fit: cover;
            margin: 0 auto 20px; display: block;
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.35);
        }
        .badge {
            display: inline-block; padding: 4px 10px;
            font-size: 11px; font-weight: 600; letter-spacing: 0.12em; text-transform: uppercase;
            color: #6EE7B7; background: rgba(5, 150, 105, 0.12);
            border: 1px solid rgba(5, 150, 105, 0.30);
            border-radius: 999px; margin-bottom: 14px;
        }
        h1 { font-size: 22px; font-weight: 700; margin: 0 0 10px; letter-spacing: -0.01em; }
        p { font-size: 14px; line-height: 1.6; color: rgba(255, 255, 255, 0.65); margin: 0 0 24px; }
        .actions { display: flex; flex-direction: column; gap: 10px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 12px 18px; font-size: 14px; font-weight: 600;
            border-radius: 12px; text-decoration: none; cursor: pointer;
            border: 1px solid transparent;
            transition: transform 0.08s ease, background 0.15s ease, border-color 0.15s ease;
        }
        .btn:active { transform: translateY(1px); }
        .btn-primary {
            color: #fff;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 4px 16px rgba(5, 150, 105, 0.35);
        }
        .btn-primary:hover { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .btn-secondary {
            color: rgba(255, 255, 255, 0.80); background: transparent;
            border-color: rgba(255, 255, 255, 0.15);
        }
        .btn-secondary:hover {
            color: #fff; border-color: rgba(255, 255, 255, 0.25);
            background: rgba(255, 255, 255, 0.04);
        }
        .foot {
            margin-top: 22px; font-size: 11px; color: rgba(255, 255, 255, 0.35);
            letter-spacing: 0.06em; text-transform: uppercase;
        }
        .support {
            margin-top: 10px; font-size: 12px; color: rgba(255, 255, 255, 0.45);
        }
        .support a { color: #6EE7B7; text-decoration: none; }
        .support a:hover { text-decoration: underline; }
        .icon { width: 16px; height: 16px; flex-shrink: 0; }
    </style>
</head>
<body>
    <main class="card" role="main">
        <img src="/logo.png" alt="{{ config('app.name', 'OT1-Pro') }}" class="logo">
        <span class="badge">{{ $badge }}</span>
        <h1>{{ $heading }}</h1>
        <p>{{ $body }}</p>

        <div class="actions">
            <a href="{{ $primaryHref }}" class="btn btn-primary" rel="noopener">
                {{ $primaryLabel }}
            </a>
            @if($secondaryHref && $secondaryLabel)
                <a href="{{ $secondaryHref }}" class="btn btn-secondary">{{ $secondaryLabel }}</a>
            @endif
        </div>

        <p class="support">
            {{ __('Still stuck? Email me at') }}
            <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
        </p>

        <p class="foot">{{ $footLabel }}</p>
    </main>
</body>
</html>
