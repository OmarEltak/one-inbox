<?php
/**
 * 2026-11-09 — Dry-run the AI reply against a real conversation on prod,
 * WITHOUT actually dispatching anything to Meta. Prints the conversation
 * history + what the AI would reply with the current (v2) prompt.
 *
 * Nothing persists. No outbound Meta send. No Message row created.
 * Burns a single AI generation call.
 *
 * Usage (set CONVERSATION_ID or CONTACT_NAME via env):
 *   cd /var/www/ot1-pro.com
 *   sudo -u deploy HOME=/home/deploy XDG_CONFIG_HOME=/home/deploy/.config \
 *     CONTACT_NAME="Omar Mohamed" \
 *     php artisan tinker --execute="require 'scripts/simulate-ai-reply-on-conv.php';"
 */

use App\Contracts\AiProviderInterface;
use App\Models\Conversation;
use App\Models\Contact;
use App\Models\Page;

$contactNameEnv = getenv('CONTACT_NAME') ?: 'Omar Mohamed';
$convIdEnv      = (int) (getenv('CONVERSATION_ID') ?: 0);

// 1. Locate the conversation(s) — scope to OT1-Pro's own pages (17 FB, 41 IG).
$ownedPageIds = [17, 41];

$candidates = Conversation::query()
    ->whereIn('page_id', $ownedPageIds)
    ->when($convIdEnv > 0, fn ($q) => $q->where('id', $convIdEnv))
    ->when($convIdEnv === 0, fn ($q) => $q->whereHas('contact', fn ($c) => $c->where('name', 'like', "%{$contactNameEnv}%")))
    ->with(['contact', 'page'])
    ->orderByDesc('last_message_at')
    ->take(5)
    ->get();

if ($candidates->isEmpty()) {
    echo "No conversations found for contact matching '{$contactNameEnv}' on OT1-Pro pages (17, 41).\n";
    echo "Candidates (any page matching this contact name across whole DB, top 5):\n";
    $any = Contact::where('name', 'like', "%{$contactNameEnv}%")->take(10)->get();
    foreach ($any as $c) {
        echo "  contact_id={$c->id}  team_id={$c->team_id}  platform={$c->platform}  name='{$c->name}'\n";
    }
    exit(0);
}

echo "Found " . $candidates->count() . " conversation(s) matching '{$contactNameEnv}' on OT1-Pro pages:\n\n";
foreach ($candidates as $i => $conv) {
    echo "  [{$i}] conv_id={$conv->id}  contact='{$conv->contact?->name}'  page='{$conv->page?->name}' ({$conv->platform})  stage={$conv->sales_stage}  last_msg_at={$conv->last_message_at?->toIso8601String()}\n";
}
echo "\nSimulating AI reply on the MOST RECENT one (index 0).\n\n";

$conv = $candidates->first();

// 2. Print the conversation history (last 15 messages).
$history = $conv->messages()->orderBy('id')->take(30)->get();
echo "══════════ CONVERSATION HISTORY ({$history->count()} messages) ══════════\n";
foreach ($history as $m) {
    $who = $m->direction === 'inbound' ? 'CUSTOMER   ' : strtoupper(str_pad($m->sender_type, 11));
    $ts  = $m->created_at?->format('m-d H:i:s') ?? '?';
    $content = mb_substr((string) $m->content, 0, 350);
    echo "[{$ts}] {$who}: {$content}\n";
}
echo "\n";

// 3. Simulate AI reply on the latest inbound message.
$latestInbound = $conv->messages()->where('direction', 'inbound')->latest('id')->first();
if (! $latestInbound) {
    echo "No inbound messages in this conversation — nothing to reply to.\n";
    exit(0);
}

$page = $conv->page;
if (! $page || ! $page->aiConfig) {
    echo "Page or AiConfig missing — cannot simulate.\n";
    exit(1);
}

echo "══════════ SIMULATED AI REPLY (dry run, no dispatch) ══════════\n";
echo "Replying to:\n  CUSTOMER [{$latestInbound->created_at?->format('m-d H:i:s')}]: " . mb_substr((string) $latestInbound->content, 0, 350) . "\n\n";

try {
    $ai = app(AiProviderInterface::class);
    $reply = $ai->generateResponse($conv, $latestInbound, $page->aiConfig);
    echo "AI WOULD SEND:\n{$reply}\n\n";

    // Heuristic checks — flag any rule violations in the preview
    $flags = [];
    if (preg_match('/عمر|Omar|founder|مؤسس|\+?201026361218|201026361218/i', $reply)) {
        // OK if a prior buying signal was in the transcript
        $historyText = $history->pluck('content')->implode(' ');
        if (! preg_match('/تمام اشتركني|let\'s do it|كيف أدفع|كيف أشترك|I\'m ready|I\'ll take it|ابعتلي الفاتورة/i', $historyText)) {
            $flags[] = 'MENTIONED Omar/WhatsApp without buying signal in transcript';
        }
    }
    if (preg_match('/مصر\s*(ولا|or|أو)\s*الخليج|Egypt\s+or\s+Gulf/i', $reply)) {
        $flags[] = 'ASKED Egypt-or-Gulf explicitly';
    }
    if (mb_strlen($reply) > 800) {
        $flags[] = 'LONG REPLY (' . mb_strlen($reply) . ' chars)';
    }
    // Price without question in same reply
    $priceHit = preg_match('/\b3,?500\b|\b٣،?٥٠٠\b|\bPilot\b|\b499\b|\bFounding Partner\b|\bWhite[- ]Glove\b|\$149\b|\$499\b/i', $reply);
    if ($priceHit) {
        $hasQ = str_contains($reply, '؟') || str_contains($reply, '?');
        if (!$hasQ) $flags[] = 'PRICE WITHOUT QUESTION — violates RULE #1';
    }

    echo empty($flags)
        ? "✅ No rule violations in the simulated reply.\n"
        : "⚠️  Heuristic flags: " . implode(' | ', $flags) . "\n";

    echo "\n(This was a DRY RUN. No message was stored, no Meta dispatch happened.)\n";
} catch (\Throwable $e) {
    echo "AI ERROR: " . $e->getMessage() . "\n";
}
