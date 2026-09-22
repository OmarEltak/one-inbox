<p>Hey {{ $team->owner?->name ?? 'there' }},</p>

<p>Your 14-day trial on <strong>{{ $team->name }}</strong> is up today. Nothing's turned off — you've got 7 more days to arrange payment, and even after that we don't auto-cancel.</p>

<p>To keep going, please pay by bank transfer using the link below:</p>

<p><a href="{{ url('/pay-wire?plan=' . ($team->subscription_plan ?? 'starter')) }}">Upload your bank-transfer receipt</a></p>

<p>Bank details are on the page. Once we receive the receipt we'll mark your account paid — usually within a business day.</p>

<p>If the trial didn't quite work for what you needed, just reply to this email and tell me why. I'd rather learn than chase.</p>

<p>— Omar<br>
Founder, OT1-Pro</p>
