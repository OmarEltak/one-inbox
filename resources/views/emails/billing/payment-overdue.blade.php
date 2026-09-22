<p>Hey {{ $team->owner?->name ?? 'there' }},</p>

<p>Just a friendly nudge — we haven't received the bank transfer for <strong>{{ $team->name }}</strong> yet. Your AI is still running (I don't switch people off for a billing lag), but I wanted to check in.</p>

<p>If the transfer is already on the way, ignore this. If you got stuck, or the timing's wrong for you, reply to this email and let's sort it. I've moved dates around for founders before — no big deal.</p>

<p>You can upload the receipt any time here: <a href="{{ url('/pay-wire?plan=' . ($team->subscription_plan ?? 'starter')) }}">upload receipt</a></p>

<p>— Omar<br>
Founder, OT1-Pro</p>
