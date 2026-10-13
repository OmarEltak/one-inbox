<!doctype html>
<html>
<body style="font-family: system-ui, sans-serif; color: #111; line-height: 1.55; max-width: 640px; margin: 0 auto; padding: 24px;">
    <h2 style="margin-bottom: 4px;">{{ $page->name }} is all loaded 🎉</h2>
    <p style="color: #555; margin-top: 0;">Hi {{ $user->name }}, we finished loading <strong>{{ number_format($chatsLoaded) }} chats</strong> from {{ $page->name }} on {{ ucfirst($page->platform) }}.</p>

    <div style="background: #ecfdf5; border: 1px solid #a7f3d0; padding: 16px 20px; border-radius: 8px; margin: 24px 0;">
        <p style="margin: 0 0 8px 0;"><strong>Your AI now has full context for every chat.</strong></p>
        <p style="margin: 0; color: #065f46;">Ask it anything: themes customers talk about, what your moderator did vs what you'd do, who's a hot lead, which objections come up most. The deeper the dataset, the sharper the answers.</p>
    </div>

    <p style="margin-top: 24px;">
        <a href="{{ route('ai-chat') }}" style="display: inline-block; background: #059669; color: white; padding: 10px 18px; text-decoration: none; border-radius: 6px; margin-right: 8px;">Ask the AI</a>
        <a href="{{ route('connections.index') }}" style="display: inline-block; background: white; color: #059669; border: 1px solid #059669; padding: 9px 18px; text-decoration: none; border-radius: 6px;">View connections</a>
    </p>

    <p style="color: #666; font-size: 13px;">Need a deeper pull than what we auto-loaded? Just ask the AI — it'll offer to pull more right in the chat.</p>

    <p style="color: #888; font-size: 13px; margin-top: 32px;">— Omar &amp; the OT1-Pro team</p>
</body>
</html>
