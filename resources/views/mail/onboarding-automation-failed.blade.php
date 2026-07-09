<!doctype html>
<html>
<body style="font-family: system-ui, sans-serif; color: #111; line-height: 1.5;">
    <h2>Onboarding request #{{ $req->id }} needs human review</h2>

    <p><strong>Why:</strong> {{ $note }}</p>

    <h3>Request details</h3>
    <ul>
        <li>Platform: {{ ucfirst($req->platform) }}</li>
        <li>Business name: {{ $req->business_name }}</li>
        <li>Page URL: {{ $req->page_url ?: '(none)' }}</li>
        <li>Contact email: {{ $req->contact_email }}</li>
        <li>Contact phone: {{ $req->contact_phone ?: '(none)' }}</li>
        <li>Team ID: {{ $req->team_id }}</li>
        <li>Submitted: {{ $req->created_at?->toDateTimeString() }}</li>
    </ul>

    @if($req->notes)
        <p><strong>Customer notes:</strong><br>{{ $req->notes }}</p>
    @endif

    <p>
        <a href="{{ url('/super-admin/onboarding-requests') }}">Open the admin queue →</a>
    </p>
</body>
</html>
