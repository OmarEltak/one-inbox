{{-- HTML alternate for Nudge 3 — keeps the "Omar typed this in Gmail" tone
     (single sans font, no product chrome, no logo), but wraps the copy in a
     real HTML body so the unsubscribe link renders as a styled button
     instead of a raw signed URL. Plaintext version at nudge-3-personal.blade.php
     is still shipped as multipart alternative for text-only clients. --}}
@php
    $name = trim(explode(' ', (string) ($user->name ?? ''))[0] ?? '');
    $greeting = $name !== '' ? 'hey ' . $name : 'hey';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>quick question about your OT1-Pro setup</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;padding:0;background:#ffffff;">
    <div style="max-width:560px;margin:0 auto;padding:24px 20px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;line-height:1.55;color:#222;">
        <p style="margin:0 0 14px;">{{ $greeting }},</p>

        <p style="margin:0 0 14px;">it's Omar — I built OT1-Pro.</p>

        <p style="margin:0 0 14px;">I noticed you signed up a few days ago and haven't sent or received a message through it yet. before I keep quiet and let you get on with your day, I want to check: is something in the way?</p>

        <p style="margin:0 0 8px;">the honest options I can think of:</p>

        <ol style="margin:0 0 14px;padding-left:22px;">
            <li style="margin:0 0 6px;">the setup is confusing and you gave up (this is on me — tell me where you got stuck and I'll fix the flow)</li>
            <li style="margin:0 0 6px;">you were curious but it's not the right fit right now (also totally fine — reply "not now" and I'll stop nudging)</li>
            <li style="margin:0 0 6px;">you're waiting on us to connect your facebook / instagram page (reply with the page URL and I'll do it myself today)</li>
        </ol>

        <p style="margin:0 0 14px;">I'd genuinely rather hear "not for me" than have you sitting on an empty account wondering if this is worth it.</p>

        <p style="margin:0 0 14px;">either way, thanks for giving it a look.</p>

        <p style="margin:0 0 4px;">— Omar<br>
        <a href="mailto:omareltak7@gmail.com" style="color:#059669;text-decoration:none;">omareltak7@gmail.com</a></p>

        <hr style="border:none;border-top:1px solid #e6dfd0;margin:28px 0 20px;">

        <p style="margin:0 0 14px;font-size:12px;color:#888;">you're getting this because you signed up for OT1-Pro.</p>

        <p style="margin:0;">
            <a href="{{ $unsubscribeUrl }}"
               style="display:inline-block;padding:10px 18px;border-radius:999px;background:#f5f5f5;color:#555;text-decoration:none;font-size:12px;font-weight:500;border:1px solid #e6dfd0;">
                Unsubscribe from these check-ins
            </a>
        </p>
    </div>
</body>
</html>
