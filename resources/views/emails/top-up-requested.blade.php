<!doctype html>
<html>
<body style="font-family: system-ui, sans-serif; color: #111; line-height: 1.5; max-width: 640px;">
    <h2 style="margin-bottom: 4px;">Top-up request — action needed</h2>
    <p style="color: #555; margin-top: 0;">
        A customer just told us they sent payment and want their account topped up.
        Confirm the money is in and then grant the credits via the super-admin billing screen.
    </p>

    <div style="background: #ecfdf5; border: 1px solid #10b981; padding: 12px 16px; border-radius: 8px; margin: 20px 0;">
        <p style="margin: 0 0 8px 0;"><strong>What to do:</strong></p>
        <ol style="margin: 0; padding-left: 20px;">
            <li>Check PayPal / bank for the incoming payment matching <strong>{{ $productLabel }}</strong>.</li>
            <li>If confirmed, open the super-admin grant screen below (team is pre-selected).</li>
            <li>Grant credits per the pack size or flip the plan; add the payment reference in the note.</li>
        </ol>
    </div>

    <h3 style="margin-bottom: 6px;">Request details</h3>
    <ul>
        <li>Team ID: <strong>#{{ $team->id }}</strong></li>
        <li>Team name: <strong>{{ $team->name }}</strong></li>
        <li>Requested by: {{ $requester->name }} &lt;{{ $requester->email }}&gt;</li>
        <li>Product: <strong>{{ $productLabel }}</strong> ({{ $product }})</li>
        <li>Submitted: {{ $submittedAt }} UTC</li>
    </ul>

    <p style="margin-top: 24px;">
        <a href="{{ $grantUrl }}" style="display: inline-block; background: #059669; color: white; padding: 10px 18px; text-decoration: none; border-radius: 6px;">
            Open super-admin billing
        </a>
    </p>

    <p style="color: #888; font-size: 12px; margin-top: 32px;">
        This mail is informational — no credits were granted automatically. The customer is waiting for your confirmation.
    </p>
</body>
</html>
