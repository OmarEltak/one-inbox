<?php
/**
 * Full audit of Lavin team (60) activity over the last 2 days.
 * Surfaces:
 *   A. Sheet verification (what's live now)
 *   B. All confirmed vs incomplete orders
 *   C. AI behaviour failures:
 *      - Empty AI replies to customer messages
 *      - AI replying about "I can't see the image" (vision failed)
 *      - AI calling the customer by nickname/model/color (name hallucination)
 *      - AI confirming orders without full data
 *      - Convos where customer sent image but no vision description
 */

use App\Models\AiConfig;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\SalesConnectors\SalesConnectors;

const LAVIN_TEAM_ID = 60;
const SINCE = '2026-11-15 00:00:00';

$since = Carbon\Carbon::parse(SINCE, 'UTC');

echo "=== A. SHEET STATE (confirmed orders captured_data) ===\n\n";

$confirmed = Conversation::whereHas('page', fn ($q) => $q->where('team_id', LAVIN_TEAM_ID))
    ->whereNotNull('captured_data->confirmed_at')
    ->orderBy('id')
    ->get();

echo "Confirmed orders in DB: " . $confirmed->count() . "\n";
foreach ($confirmed as $c) {
    $cap = $c->captured_data;
    $r = SalesConnectors::row($c, 'lead_captured');
    echo "  conv#{$c->id} {$c->platform} | {$cap['name']} | {$cap['phone']} | {$cap['order_summary']}\n";
}

echo "\n=== B. AI confirmed but BLOCKED from sheet (incomplete) ===\n\n";

$aiConfirmedMsgs = Message::whereHas('conversation.page', fn ($q) => $q->where('team_id', LAVIN_TEAM_ID))
    ->where('sender_type', 'ai')
    ->where('content', 'like', '%سجلت الأوردر%')
    ->where('created_at', '>=', $since)
    ->get()
    ->groupBy('conversation_id');

$incomplete = 0;
foreach ($aiConfirmedMsgs as $convId => $msgs) {
    $c = Conversation::find($convId);
    if (! empty($c->captured_data['confirmed_at'])) continue; // already in complete list

    $incomplete++;
    $cap = $c->captured_data ?? [];
    $missing = [];
    if (empty($cap['name']))          $missing[] = 'name';
    if (empty($cap['phone']))         $missing[] = 'phone';
    if (empty($cap['address']))       $missing[] = 'address';
    if (empty($cap['order_summary'])) $missing[] = 'order';
    echo "  conv#{$c->id} {$c->platform} | contact=" . substr((string)$c->contact?->name, 0, 20) . " | missing: " . implode(', ', $missing) . "\n";
    echo "    AI confirmation: " . mb_substr(preg_replace('/\s+/', ' ', $msgs->last()->content), 0, 150) . "\n";
}
echo "\nTotal BLOCKED: {$incomplete}\n";

echo "\n=== C. AI BEHAVIOUR FAILURES in last 2 days ===\n\n";

// C1: AI replies saying "can't see the image" — vision pipeline likely failed
echo "C1. AI replies about failed vision (examples of 'مش ظاهرة' / 'ما قدرت أشوفها' / 'مش واضحة'):\n";
$visionFailReplies = Message::whereHas('conversation.page', fn ($q) => $q->where('team_id', LAVIN_TEAM_ID))
    ->where('sender_type', 'ai')
    ->where('direction', 'outbound')
    ->where('created_at', '>=', $since)
    ->where(function ($q) {
        $q->where('content', 'like', '%مش ظاهرة%')
          ->orWhere('content', 'like', '%مش واضح%')
          ->orWhere('content', 'like', '%ما قدرت أشوف%')
          ->orWhere('content', 'like', '%ما وصلني%')
          ->orWhere('content', 'like', '%صورة ما وصلت%')
          ->orWhere('content', 'like', "%I can't see%");
    })
    ->orderByDesc('id')
    ->limit(10)
    ->get();
echo "  found: " . $visionFailReplies->count() . "\n";
foreach ($visionFailReplies as $m) {
    echo "    conv#{$m->conversation_id} @" . $m->created_at->format('m-d H:i') . " \"" . mb_substr($m->content, 0, 100) . "\"\n";
}

// C2: Inbound images with NO vision description cached
echo "\nC2. Customer-sent images with EMPTY vision description (vision pipeline failed):\n";
$imgsNoVision = Message::whereHas('conversation.page', fn ($q) => $q->where('team_id', LAVIN_TEAM_ID))
    ->where('content_type', 'image')
    ->where('direction', 'inbound')
    ->where('created_at', '>=', $since)
    ->with('mediaAsset')
    ->get();
$noVisionCount = 0;
foreach ($imgsNoVision as $m) {
    $desc = $m->mediaAsset?->metadata['ai_description'] ?? null;
    if (empty($desc)) $noVisionCount++;
}
echo "  images sent by customers: " . $imgsNoVision->count() . "\n";
echo "  images with NO vision description: {$noVisionCount}\n";

// C3: AI used customer's WhatsApp nickname as a form of addressing
echo "\nC3. AI replies that appear to treat the person in an image as the customer:\n";
$personAsCustomer = Message::whereHas('conversation.page', fn ($q) => $q->where('team_id', LAVIN_TEAM_ID))
    ->where('sender_type', 'ai')
    ->where('direction', 'outbound')
    ->where('created_at', '>=', $since)
    ->where(function ($q) {
        $q->where('content', 'like', '%عليكِ%')
          ->orWhere('content', 'like', '%عليكي%')
          ->orWhere('content', 'like', '%لابساه%')
          ->orWhere('content', 'like', '%لابساها%')
          ->orWhere('content', 'like', '%لابستيه%')
          ->orWhere('content', 'like', '%شكلك%')
          ->orWhere('content', 'like', '%بتلبسي%');
    })
    ->orderByDesc('id')
    ->limit(15)
    ->get();
echo "  found: " . $personAsCustomer->count() . "\n";
foreach ($personAsCustomer as $m) {
    $imgBefore = Message::where('conversation_id', $m->conversation_id)
        ->where('id', '<', $m->id)
        ->where('content_type', 'image')
        ->where('direction', 'inbound')
        ->exists();
    $flag = $imgBefore ? '⚠️ AFTER customer image' : '';
    echo "    conv#{$m->conversation_id} @" . $m->created_at->format('m-d H:i') . " {$flag}\n";
    echo "      \"" . mb_substr(preg_replace('/\s+/', ' ', $m->content), 0, 150) . "\"\n";
}

// C4: Empty AI replies (content is empty string and no media_url)
echo "\nC4. Empty AI reply messages (no content + no media):\n";
$empty = Message::whereHas('conversation.page', fn ($q) => $q->where('team_id', LAVIN_TEAM_ID))
    ->where('sender_type', 'ai')
    ->where('direction', 'outbound')
    ->where('created_at', '>=', $since)
    ->where(function ($q) { $q->whereNull('content')->orWhere('content', ''); })
    ->whereNull('media_url')
    ->count();
echo "  count: {$empty}\n";

echo "\n=== D. OVERALL STATS ===\n\n";

$totalConvs = Conversation::whereHas('page', fn ($q) => $q->where('team_id', LAVIN_TEAM_ID))
    ->where('last_message_at', '>=', $since)->count();
$totalInbound = Message::whereHas('conversation.page', fn ($q) => $q->where('team_id', LAVIN_TEAM_ID))
    ->where('direction', 'inbound')->where('created_at', '>=', $since)->count();
$totalAiReplies = Message::whereHas('conversation.page', fn ($q) => $q->where('team_id', LAVIN_TEAM_ID))
    ->where('sender_type', 'ai')->where('created_at', '>=', $since)->count();

echo "total active convs:  {$totalConvs}\n";
echo "total inbound msgs:  {$totalInbound}\n";
echo "total AI replies:    {$totalAiReplies}\n";
echo "confirmed orders:    " . $confirmed->count() . " (in sheet)\n";
echo "blocked confirmations: {$incomplete} (AI tried to confirm but incomplete)\n";
echo "conversion rate:     " . round($confirmed->count() / max($totalConvs, 1) * 100, 1) . "%\n";
