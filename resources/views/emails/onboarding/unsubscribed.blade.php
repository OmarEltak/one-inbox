<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Unsubscribed — OT1-Pro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { margin:0; padding:0; background:#f5f5f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#222; }
        .card { max-width:480px; margin:64px auto; background:#fff; border-radius:12px; padding:32px; text-align:center; }
        h1 { font-size:20px; margin:0 0 12px; }
        p { font-size:15px; line-height:1.55; color:#555; margin:0 0 16px; }
        a { color:#059669; text-decoration:none; font-weight:600; }
    </style>
</head>
<body>
    <div class="card">
        <h1>You're unsubscribed</h1>
        <p>We won't send any more onboarding nudge emails to <strong>{{ e($team->name) }}</strong>.</p>
        <p>You'll still receive important account and billing emails. If you want them turned off too, reply to any nudge and I'll handle it manually.</p>
        <p style="margin-top:24px;">— Omar<br><a href="mailto:omareltak7@gmail.com">omareltak7@gmail.com</a></p>
    </div>
</body>
</html>
