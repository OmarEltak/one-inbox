<!doctype html>
<html>
<body style="font-family: system-ui, sans-serif; color: #111; line-height: 1.55; max-width: 640px; margin: 0 auto; padding: 24px;">
    <h2 style="margin-bottom: 4px;">Welcome to OT1-Pro, {{ $user->name }} 👋</h2>
    <p style="color: #555; margin-top: 0;">Thanks for signing up. OT1-Pro brings every customer conversation — Facebook, Instagram, WhatsApp, Telegram, and Email — into one shared inbox with an AI sales responder that never sleeps.</p>

    <div style="background: #f5f3ff; border: 1px solid #ddd6fe; padding: 16px 20px; border-radius: 8px; margin: 24px 0;">
        <p style="margin: 0 0 8px 0;"><strong>Get started in 3 steps:</strong></p>
        <ol style="margin: 0; padding-left: 20px;">
            <li>Open your dashboard: <a href="{{ route('dashboard') }}">{{ route('dashboard') }}</a></li>
            <li>Connect your first channel (Facebook Page, Instagram, WhatsApp, Telegram, or Email inbox) on the <a href="{{ route('connections.index') }}">Connections page</a></li>
            <li>Turn on the AI responder for that channel and watch conversations start flowing in</li>
        </ol>
    </div>

    <p>If anything's unclear, just reply to this email or message us on WhatsApp: <a href="https://wa.me/201026361218">+20 102 636 1218</a>. A human on our team reads every message.</p>

    <p style="margin-top: 24px;">
        <a href="{{ route('dashboard') }}" style="display: inline-block; background: #7c3aed; color: white; padding: 10px 18px; text-decoration: none; border-radius: 6px;">Go to dashboard</a>
    </p>

    <p style="color: #888; font-size: 13px; margin-top: 32px;">— The OT1-Pro team</p>
</body>
</html>
