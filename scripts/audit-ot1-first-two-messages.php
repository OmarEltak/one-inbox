<?php
/**
 * 2026-11-09 — Audit the last 20 conversations on OT1-Pro's pages (17 FB, 41 IG).
 * For each one, show the first customer inbound + the FIRST TWO AI replies. Omar
 * reports the first two AI messages always feel "like a chatbot" — we need
 * side-by-side evidence to see the actual failure pattern before rewriting
 * the prompt to v3.
 *
 * Nothing persists. Read-only.
 */

use App\Models\Conversation;
use App\Models\Message;

$pageIds = [17, 41];

$convs = Conversation::whereIn('page_id', $pageIds)
    ->whereNotNull('last_message_at')
    ->orderByDesc('last_message_at')
    ->with('contact', 'page')
    ->take(25)
    ->get();

echo "Pulled " . $convs->count() . " conversations.\n\n";

$idx = 0;
foreach ($convs as $conv) {
    $firstCustomer = $conv->messages()->where('direction', 'inbound')->orderBy('id')->first();
    if (! $firstCustomer) {
        continue;
    }
    $firstTwoAi = $conv->messages()
        ->where('direction', 'outbound')
        ->where('sender_type', 'ai')
        ->orderBy('id')
        ->take(2)
        ->get();

    // Skip conversations where the AI never got a chance to reply
    if ($firstTwoAi->isEmpty()) {
        continue;
    }

    $idx++;
    if ($idx > 20) break;

    echo "═══ #{$idx} — conv_id={$conv->id}  contact='{$conv->contact?->name}'  page='{$conv->page?->name}' ({$conv->platform})  stage={$conv->sales_stage}  last_msg_at=" . $conv->last_message_at?->toIso8601String() . " ═══\n";
    echo "  CUSTOMER #1: " . mb_substr((string) $firstCustomer->content, 0, 200) . "\n";
    foreach ($firstTwoAi as $i => $msg) {
        $tag = $i === 0 ? 'AI #1' : 'AI #2';
        echo "  {$tag}:        " . mb_substr((string) $msg->content, 0, 600) . "\n";
    }
    echo "\n";
}
