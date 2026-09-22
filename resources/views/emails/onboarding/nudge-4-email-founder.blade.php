@php
    $name = trim(explode(' ', (string) ($user->name ?? ''))[0] ?? '');
    $greeting = $name !== '' ? 'Hey ' . e($name) : 'Hey';
@endphp
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Need a hand?</title></head>
<body style="margin:0; padding:0; background:#f5f5f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#222;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f5f5f5; padding:24px 12px;">
        <tr><td align="center">
            <table role="presentation" cellpadding="0" cellspacing="0" width="560" style="max-width:560px; background:#ffffff; border-radius:12px; padding:32px;">
                <tr><td>
                    <p style="margin:0 0 16px; font-size:16px; line-height:1.55;">{!! $greeting !!},</p>

                    <p style="margin:0 0 16px; font-size:16px; line-height:1.55;">
                        This is my last check-in — I promise. It's been about a week since you signed up for OT1-Pro and I don't want to be one of those tools that spam-drips forever.
                    </p>

                    <p style="margin:0 0 16px; font-size:16px; line-height:1.55;">
                        If you want a hand getting set up — connecting a page, tuning the AI voice, walking through the sales flow — <strong>just hit reply to this email</strong>. It comes to me directly. I'll get back inside a working day.
                    </p>

                    <p style="margin:0 0 16px; font-size:16px; line-height:1.55;">
                        Or if OT1-Pro isn't right for you, reply with a one-liner on why. That feedback is worth more to me than a signup.
                    </p>

                    <p style="margin:0 0 24px; text-align:center;">
                        <a href="mailto:omareltak7@gmail.com?subject=OT1-Pro%20setup%20help"
                           style="display:inline-block; padding:12px 24px; background:#7C3AED; color:#ffffff; text-decoration:none; border-radius:8px; font-weight:600; font-size:15px;">
                            Email Omar
                        </a>
                    </p>

                    <p style="margin:0 0 0; font-size:16px; line-height:1.55;">
                        — Omar<br>
                        omareltak7@gmail.com
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
