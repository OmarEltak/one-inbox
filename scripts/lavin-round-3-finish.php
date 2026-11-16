<?php
/**
 * Lavin round 3 — three concrete owner asks:
 *
 *   1. Captured info for orders was vague ("Product: الشال") — add color +
 *      quantity to required_capture_fields so they're extracted, structured,
 *      and surfaced in the Order column of the new sheet layout.
 *
 *   2. Previously only 2 of the actual ~18 AI-confirmed orders got a
 *      name-follow-up WhatsApp message because my pattern "سجلت الأوردر"
 *      was too narrow. Widen it to catch any confirmation-pattern AI reply
 *      (contains "الإجمالي" / "جنيه كاش" / etc.) and re-dispatch the
 *      follow-up to the 16 outstanding conversations. Still guarded by
 *      metadata.name_followup_sent_at so already-contacted customers are
 *      not spammed.
 *
 *   3. End-to-end sheet test: pick a Lavin conversation, overwrite its
 *      captured_data with a fully populated test payload (name + product
 *      + color + qty + address + phone), call complete() which triggers
 *      SalesConnectors::notify(), and verify a row lands in the operator's
 *      sheet with the new column order. Uses conv#9357 (Mazin) which
 *      already has 3 of 4 real fields — adds synthetic name + color +
 *      quantity so the test is realistic end-to-end.
 */

use App\Jobs\PushSalesConnectorRow;
use App\Models\AiConfig;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\EvolutionApiService;
use App\Services\SalesConnectors\SalesConnectors;

const LAVIN_CONFIG_IDS     = [17, 18, 19];
const NAME_FOLLOWUP_PROMPT = "عفواً يا فندم، الأوردر مسجل بس باقي حاجة بس — ممكن اسمك بالكامل عشان نكده الأوردر ونقوله للمندوب يسألك عليه؟ 🙏";

// ──────────────────────────────────────────────────────────────────
//  PART 1 — Add color + quantity to required_capture_fields
// ──────────────────────────────────────────────────────────────────

echo "=== PART 1: add color + quantity to all 3 Lavin configs ===\n";

$newFields = [
    ['key' => 'name',     'label' => 'الاسم بالكامل',   'type' => 'text'],
    ['key' => 'product',  'label' => 'المنتج (شال كبير/صغير)', 'type' => 'text'],
    ['key' => 'color',    'label' => 'اللون',           'type' => 'text'],
    ['key' => 'quantity', 'label' => 'عدد القطع',        'type' => 'text'],
    ['key' => 'address',  'label' => 'العنوان كامل',    'type' => 'address'],
    ['key' => 'phone',    'label' => 'رقم التليفون',    'type' => 'phone'],
];

foreach (LAVIN_CONFIG_IDS as $cfgId) {
    $cfg = AiConfig::find($cfgId);
    $cfg->update(['required_capture_fields' => $newFields]);
    echo "  cfg#{$cfg->id} (page#{$cfg->page_id}): now 6 required fields (added color + quantity).\n";
}

// ──────────────────────────────────────────────────────────────────
//  PART 2 — Widened name follow-up for the 16 outstanding convs
// ──────────────────────────────────────────────────────────────────

echo "\n=== PART 2: name follow-ups (wider match) ===\n";

$widerMatch = function (Conversation $c): bool {
    $patterns = ['%سجلت الأوردر%', '%الإجمالي%', '%جنيه كاش%', '%هيوصلك خلال%', '%التوصيل خلال%'];
    foreach ($patterns as $p) {
        $has = Message::where('conversation_id', $c->id)
            ->where('sender_type', 'ai')
            ->where('direction', 'outbound')
            ->where('content', 'like', $p)
            ->exists();
        if ($has) return true;
    }
    return false;
};

$candidates = Conversation::whereHas('page', fn ($q) => $q->where('team_id', 60)->where('platform', 'whatsapp'))
    ->get();

$sent = 0;
$skipped = [];

foreach ($candidates as $c) {
    $cap = (array) ($c->captured_data ?? []);
    if (! empty($cap['name'])) continue;

    $meta = $c->metadata ?? [];
    if (! empty($meta['name_followup_sent_at'])) continue;

    if (! $widerMatch($c)) continue;

    try {
        $instance = $c->page->metadata['gateway_instance'] ?? $c->page->platform_page_id;
        $apiKey   = $c->page->page_access_token;
        $msgId    = app(EvolutionApiService::class)
            ->sendText($instance, $apiKey, $c->platform_conversation_id, NAME_FOLLOWUP_PROMPT);

        if (! $msgId) {
            $skipped[] = "conv#{$c->id}: Wuzapi returned null";
            continue;
        }

        Message::create([
            'conversation_id'     => $c->id,
            'direction'           => 'outbound',
            'sender_type'         => 'ai',
            'content_type'        => 'text',
            'content'             => NAME_FOLLOWUP_PROMPT,
            'platform_message_id' => $msgId,
            'platform_sent_at'    => now(),
        ]);

        $meta['name_followup_sent_at'] = now()->toIso8601String();
        $c->update(['metadata' => $meta]);
        echo "  sent: conv#{$c->id} contact=" . ($c->contact?->name ?: '-') . "\n";
        $sent++;
    } catch (\Throwable $e) {
        $skipped[] = "conv#{$c->id}: " . $e->getMessage();
    }
}

echo "  TOTAL sent this run: {$sent}\n";
echo "  skipped: " . count($skipped) . "\n";
foreach ($skipped as $s) echo "    - {$s}\n";

// ──────────────────────────────────────────────────────────────────
//  PART 3 — End-to-end sheet test (synthetic row)
// ──────────────────────────────────────────────────────────────────

echo "\n=== PART 3: end-to-end sheet test ===\n";

// Pick conv#9357 (Mazin) — already on page 55 with real phone + address.
// Inject synthetic values for the fields he didn't send so the sheet row
// shows the FULL new column layout, including the combined Order column.
$testConv = Conversation::find(9357);
if (! $testConv) {
    echo "  conv#9357 not found — skipping test.\n";
    return;
}

$origCaptured = (array) ($testConv->captured_data ?? []);
$testCaptured = array_merge($origCaptured, [
    'name'     => 'اختبار الشيت (ليس عميل)',
    'product'  => 'شال كبير',
    'color'    => 'أسود',
    'quantity' => '1',
]);

echo "  test conv: #{$testConv->id}, page=" . $testConv->page_id . "\n";
echo "  test captured_data: " . json_encode($testCaptured, JSON_UNESCAPED_UNICODE) . "\n";

// Persist only for the duration of the push — we don't want to permanently
// stamp a fake name on a real conversation.
$testConv->update(['captured_data' => $testCaptured]);

try {
    PushSalesConnectorRow::dispatchSync($testConv->id, SalesConnectors::EVENT_LEAD_CAPTURED);
    $cfg = AiConfig::where('page_id', $testConv->page_id)->first()->fresh();
    $delivery = $cfg->sales_connectors['last_delivery'] ?? null;
    echo "  last_delivery: " . json_encode($delivery, JSON_UNESCAPED_UNICODE) . "\n";
} catch (\Throwable $e) {
    echo "  TEST FAILED: " . $e->getMessage() . "\n";
}

// Restore real captured_data so we don't pollute Mazin's actual record.
$testConv->update(['captured_data' => $origCaptured]);
echo "  restored original captured_data on conv#9357.\n";

// Show what the new row shape looks like for Omar's inspection
$preview = SalesConnectors::row($testConv, SalesConnectors::EVENT_LEAD_CAPTURED);
echo "\n  preview of row posted to sheet (for inspection):\n";
foreach ($preview as $col => $val) {
    echo "    {$col}: {$val}\n";
}
