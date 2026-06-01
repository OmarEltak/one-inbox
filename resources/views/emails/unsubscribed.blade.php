<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unsubscribed</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
               background: #0A0A0F; color: #fff; margin: 0; padding: 0;
               min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.15);
                border-radius: 16px; padding: 40px; max-width: 480px; width: 100%; text-align: center; }
        h1 { font-size: 22px; margin: 0 0 12px; }
        p { color: rgba(255,255,255,0.6); line-height: 1.5; }
        .email { color: #C27AFF; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <h1>You've been unsubscribed</h1>
        <p><span class="email">{{ $recipient->email }}</span> will no longer receive emails from this sender.</p>
    </div>
</body>
</html>
