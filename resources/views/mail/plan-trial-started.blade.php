<!doctype html>
<html>
<body style="font-family: system-ui, sans-serif; color: #111; line-height: 1.55; max-width: 640px; margin: 0 auto; padding: 24px;">
    <h2 style="margin-bottom: 4px;">You're on the {{ ucfirst($plan) }} plan 🎉</h2>
    <p style="color: #555; margin-top: 0;">Hi {{ $user->name }}, your 14-day trial of OT1-Pro {{ ucfirst($plan) }} just started.</p>

    <div style="background: #ecfdf5; border: 1px solid #a7f3d0; padding: 16px 20px; border-radius: 8px; margin: 24px 0;">
        <p style="margin: 0 0 8px 0;"><strong>What this means:</strong></p>
        <ul style="margin: 0; padding-left: 20px;">
            <li>Full access to every feature of the {{ ucfirst($plan) }} plan — no drip feed.</li>
            <li><strong>No card required.</strong> We invoice by bank transfer only when you decide to keep it.</li>
            <li>Cancel by email anytime — one reply and we handle the rest.</li>
        </ul>
    </div>

    <p>If you're on free, no invoice will ever be sent. If you're on a paid tier, we'll email an invoice after day 14 — pay only if you love it.</p>

    <p style="margin-top: 24px;">
        <a href="{{ route('dashboard') }}" style="display: inline-block; background: #059669; color: white; padding: 10px 18px; text-decoration: none; border-radius: 6px;">Open OT1-Pro</a>
    </p>

    <p style="color: #666; font-size: 13px;">Questions? Reply to this email, or WhatsApp us on <a href="https://wa.me/201026361218" style="color: #059669;">+20 102 636 1218</a>.</p>

    <p style="color: #888; font-size: 13px; margin-top: 32px;">— Omar &amp; the OT1-Pro team</p>
</body>
</html>
