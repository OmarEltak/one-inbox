<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
               background: #0A0A0F; color: #fff; margin: 0; padding: 0;
               min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);
                border-radius: 16px; padding: 40px; max-width: 480px; width: 100%; text-align: center; }
        h1 { font-size: 22px; margin: 0 0 12px; }
        p { color: rgba(255,255,255,0.6); margin: 0 0 24px; line-height: 1.5; }
        .email { color: #C27AFF; font-weight: 600; }
        button { background: linear-gradient(135deg, #7C3AED, #6D28D9); color: #fff;
                 border: 0; border-radius: 10px; padding: 12px 28px; font-size: 14px;
                 font-weight: 600; cursor: pointer; }
        button:hover { opacity: 0.9; }
        .note { font-size: 12px; color: rgba(255,255,255,0.4); margin-top: 16px; }
    </style>
</head>
<body>
    <div class="card">
        @if($already)
            <h1>You're already unsubscribed</h1>
            <p>The address <span class="email">{{ $recipient->email }}</span> is on our suppression list and will not receive further emails.</p>
        @else
            <h1>Unsubscribe</h1>
            <p>Click the button below to stop receiving emails at <span class="email">{{ $recipient->email }}</span>.</p>
            <form method="POST" action="{{ url()->current() }}">
                @csrf
                <button type="submit">Unsubscribe</button>
            </form>
            <p class="note">This action cannot be undone via this page.</p>
        @endif
    </div>
</body>
</html>
