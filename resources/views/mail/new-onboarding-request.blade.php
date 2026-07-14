<!doctype html>
<html>
<body style="font-family: system-ui, sans-serif; color: #111; line-height: 1.5; max-width: 640px;">
    <h2 style="margin-bottom: 4px;">Accept Page invitation — action needed</h2>
    <p style="color: #555; margin-top: 0;">A customer just submitted a managed onboarding request. They have (or are about to) add <strong>facebook.com/omarEltak88</strong> as a Page admin on their {{ ucfirst($req->platform) }} page.</p>

    <div style="background: #fff8e1; border: 1px solid #f0c419; padding: 12px 16px; border-radius: 8px; margin: 20px 0;">
        <p style="margin: 0 0 8px 0;"><strong>What to do:</strong></p>
        <ol style="margin: 0; padding-left: 20px;">
            <li>Open your Facebook pending requests: <a href="https://business.facebook.com/latest/settings/pending_requests">business.facebook.com/latest/settings/pending_requests</a></li>
            <li>Also check the mobile app: Meta Business Suite → Notifications (some invites only appear there)</li>
            <li>Accept the invitation for <strong>{{ $req->business_name }}</strong></li>
            <li>The automator will retry within 15 minutes and finish the setup — no further action needed on your side</li>
        </ol>
    </div>

    <h3>Request details</h3>
    <ul>
        <li>Request ID: #{{ $req->id }}</li>
        <li>Platform: {{ ucfirst($req->platform) }}</li>
        <li>Business name: <strong>{{ $req->business_name }}</strong></li>
        <li>Page URL: {{ $req->page_url ? '' : '(none provided)' }}
            @if($req->page_url)
                <a href="{{ $req->page_url }}">{{ $req->page_url }}</a>
            @endif
        </li>
        <li>Customer email: {{ $req->contact_email }}</li>
        <li>Customer phone: {{ $req->contact_phone ?: '(none)' }}</li>
        <li>Customer team ID: {{ $req->team_id }}</li>
        <li>Submitted: {{ $req->created_at?->toDateTimeString() }} UTC</li>
    </ul>

    @if($req->notes)
        <p><strong>Customer notes:</strong><br>{{ $req->notes }}</p>
    @endif

    <p style="margin-top: 24px;">
        <a href="{{ url('/super-admin/onboarding-requests') }}" style="display: inline-block; background: #7c3aed; color: white; padding: 10px 18px; text-decoration: none; border-radius: 6px;">Open admin queue</a>
    </p>
</body>
</html>
