@php
    $name = trim(explode(' ', (string) ($user->name ?? ''))[0] ?? '');
    $greeting = $name !== '' ? 'Hey ' . e($name) : 'Hey';
@endphp
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Connect your first page</title></head>
<body style="margin:0; padding:0; background:#f5f5f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#222;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f5f5f5; padding:24px 12px;">
        <tr><td align="center">
            <table role="presentation" cellpadding="0" cellspacing="0" width="560" style="max-width:560px; background:#ffffff; border-radius:12px; padding:32px;">
                <tr><td>
                    <p style="margin:0 0 16px; font-size:16px; line-height:1.55;">{!! $greeting !!},</p>

                    <p style="margin:0 0 16px; font-size:16px; line-height:1.55;">
                        Quick one — I noticed you haven't connected a page to OT1-Pro yet. That's the piece that starts pulling real customer messages into your inbox.
                    </p>

                    <p style="margin:0 0 16px; font-size:16px; line-height:1.55;">
                        Facebook and Instagram OAuth is currently gated by Meta's app review (real reason, not a soft-sell). So while that clears, <strong>we connect pages for you personally</strong> — usually under 10 minutes during working hours. You send us your page details, we verify, we hand back a live inbox.
                    </p>

                    <p style="margin:0 0 24px; text-align:center;">
                        <a href="{{ url('/connections') }}"
                           style="display:inline-block; padding:12px 24px; background:#7C3AED; color:#ffffff; text-decoration:none; border-radius:8px; font-weight:600; font-size:15px;">
                            Request a page connection →
                        </a>
                    </p>

                    <p style="margin:0 0 16px; font-size:16px; line-height:1.55;">
                        WhatsApp, Telegram and Email are self-serve on the same page — pick whichever channel your customers actually use.
                    </p>

                    <p style="margin:0 0 0; font-size:16px; line-height:1.55;">
                        Any snag, hit reply.<br>
                        — Omar
                    </p>
                </td></tr>
            </table>

            <p style="margin:16px 0 0; font-size:11px; color:#888; text-align:center;">
                You're getting this because you signed up for OT1-Pro. <a href="{{ $unsubscribeUrl }}" style="color:#888;">Unsubscribe from these nudges</a>.
            </p>
        </td></tr>
    </table>
</body>
</html>
