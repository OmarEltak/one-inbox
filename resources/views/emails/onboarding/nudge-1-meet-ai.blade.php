@php
    $name = trim(explode(' ', (string) ($user->name ?? ''))[0] ?? '');
    $greeting = $name !== '' ? 'Hey ' . e($name) : 'Hey';
@endphp
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Meet your AI</title></head>
<body style="margin:0; padding:0; background:#f5f5f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#222;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f5f5f5; padding:24px 12px;">
        <tr><td align="center">
            <table role="presentation" cellpadding="0" cellspacing="0" width="560" style="max-width:560px; background:#ffffff; border-radius:12px; padding:32px;">
                <tr><td>
                    <p style="margin:0 0 16px; font-size:16px; line-height:1.55;">{!! $greeting !!},</p>

                    <p style="margin:0 0 16px; font-size:16px; line-height:1.55;">
                        You signed up for OT1-Pro yesterday and I want to make sure you actually see it working — not just the empty inbox.
                    </p>

                    <p style="margin:0 0 16px; font-size:16px; line-height:1.55;">
                        There's a 90-second thing called <strong>Meet Your AI</strong>. You answer three questions, we generate your sales assistant in your business voice, and you chat with it as a fake customer. No page connection needed — that's the whole point.
                    </p>

                    <p style="margin:0 0 24px; text-align:center;">
                        <a href="{{ url('/onboarding/meet-your-ai') }}"
                           style="display:inline-block; padding:12px 24px; background:#059669; color:#ffffff; text-decoration:none; border-radius:8px; font-weight:600; font-size:15px;">
                            Meet your AI (90 seconds) →
                        </a>
                    </p>

                    <p style="margin:0 0 16px; font-size:16px; line-height:1.55;">
                        If you already tried it and had a rough time, just hit reply and tell me what broke. I read every one.
                    </p>

                    <p style="margin:0 0 0; font-size:16px; line-height:1.55;">
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
