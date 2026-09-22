<p>Hey {{ $team->owner?->name ?? 'there' }},</p>

<p>Quick heads-up — your OT1-Pro trial for <strong>{{ $team->name }}</strong> ends in {{ $daysLeft }} day{{ $daysLeft === 1 ? '' : 's' }}.</p>

<p>Nothing breaks automatically. If it's been useful, we'll email you an invoice with our bank details when the trial's up. Bank transfer only — no card capture, no auto-charge.</p>

<p>If it hasn't been useful, or if something's confusing, just reply to this email and tell me what got in the way. I read every reply.</p>

<p>— Omar<br>
Founder, OT1-Pro</p>
