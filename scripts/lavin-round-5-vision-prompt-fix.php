<?php
/**
 * Round 5 — three fixes after full 2-day audit:
 *
 *   1. SYSTEM PROMPT: strong new rule about customer-sent images. When the
 *      vision pipeline flags product_match_id (meaning the image IS one of
 *      our product reference photos), the AI must treat it as "the customer
 *      wants to see/buy THIS product", NOT "the person in the image is my
 *      customer" (which produced "البني والأسود حلوين عليكِ" on conv#9452
 *      — the model in the photo isn't the customer).
 *
 *   2. CLEAR conv#9355's bad confirmed_at — AI confirmed without name/phone
 *      but my retroactive re-parse earlier stamped confirmed_at anyway
 *      (before the strict validator was shipped).
 *
 *   3. RE-DISPATCH DescribeImage on any customer image with empty
 *      ai_description from last 2 days (20 found in audit). These failed
 *      vision silently — the AI got "I can't see it" instead of real data.
 */

use App\Models\AiConfig;
use App\Models\Conversation;
use App\Models\Message;

// ──────────────────────────────────────────────────────────────────
//  PART 1 — stronger system prompt rule about customer images
// ──────────────────────────────────────────────────────────────────

echo "=== PART 1: update system prompt (image interpretation rule) ===\n";

$firstCfg = AiConfig::find(17);
$oldPrompt = $firstCfg->system_prompt;

$imageRule = <<<'AR'

══ قاعدة #7 — صور العميل (مهمة جدًا) ══
لما العميل يبعت صورة، لا تفترض أبدًا إن الشخص اللي في الصورة هو العميل نفسه. العميل في الغالب بيبعت صورة لمنتج شاف عندنا أو عند حد تاني، أو صورة من حسابنا الرسمي، وبيسأل عنه. لا تقول أبدًا "طالع عليكِ شيك" أو "الاتنين حلوين عليكِ" أو "بتلبسيه كويس" لمجرد إنه بعت صورة.

لو النظام قالّك إن الصورة اتطابقت مع واحد من منتجاتنا (علامة Product match hint)، ردّك يبقى واضح وصريح: "أيوه، دي صورة شال [كبير/صغير] [اللون]ـ اللي عندنا بالظبط — السعر [X] جنيه، متوفر منه الألوان دي: [...]. تحب نحجزهولك؟" — بدون أي تعليق على شكل الشخص في الصورة.

لو النظام مقالش إن في تطابق، اسألي صريح: "ممكن تقوليلي بتسألي عن المنتج ده ولا عايزة تشوفي شكله عندنا؟ أو تحبي أبعتلك صور الموديلات المتاحة؟"

ممنوع تمامًا:
  - التعليق على جسم أو شكل أو مقاس الشخص اللي في الصورة.
  - افتراض إن العميل هو الموديل في الصورة.
  - الكلام كأن العميل لابس المنتج دلوقتي.

AR;

$newPrompt = rtrim($oldPrompt) . "\n" . $imageRule;

foreach ([17, 18, 19] as $cfgId) {
    AiConfig::find($cfgId)->update(['system_prompt' => $newPrompt]);
    echo "  cfg#{$cfgId}: Rule #7 appended.\n";
}

// ──────────────────────────────────────────────────────────────────
//  PART 2 — clear conv#9355's bad confirmed_at
// ──────────────────────────────────────────────────────────────────

echo "\n=== PART 2: clear stale confirmed_at on incomplete orders ===\n";

$incomplete = Conversation::whereHas('page', fn ($q) => $q->where('team_id', 60))
    ->whereNotNull('captured_data->confirmed_at')
    ->get()
    ->filter(function ($c) {
        $cap = $c->captured_data;
        return empty($cap['name']) || empty($cap['phone'])
            || empty($cap['address']) || empty($cap['order_summary']);
    });

foreach ($incomplete as $c) {
    $cap = $c->captured_data;
    $missing = [];
    foreach (['name', 'phone', 'address', 'order_summary'] as $k) {
        if (empty($cap[$k])) $missing[] = $k;
    }
    unset($cap['confirmed_at']);
    $c->update(['captured_data' => $cap]);
    echo "  conv#{$c->id}: stripped confirmed_at (was missing " . implode(',', $missing) . ")\n";
}
echo "  stripped: " . $incomplete->count() . "\n";

// ──────────────────────────────────────────────────────────────────
//  PART 3 — re-dispatch vision on images with empty descriptions
// ──────────────────────────────────────────────────────────────────

echo "\n=== PART 3: re-dispatch vision for failed images (last 2 days) ===\n";

$since = Carbon\Carbon::parse('2026-11-15 00:00:00', 'UTC');

$failed = Message::whereHas('conversation.page', fn ($q) => $q->where('team_id', 60))
    ->where('content_type', 'image')
    ->where('direction', 'inbound')
    ->where('created_at', '>=', $since)
    ->with('mediaAsset')
    ->get()
    ->filter(function ($m) {
        $desc = $m->mediaAsset?->metadata['ai_description'] ?? null;
        return $m->mediaAsset !== null && empty($desc);
    });

echo "  images with empty vision description: " . $failed->count() . "\n";

$redispatched = 0;
foreach ($failed as $m) {
    try {
        \App\Jobs\DescribeImage::dispatch($m->id);
        $redispatched++;
    } catch (\Throwable $e) {
        echo "  FAILED to dispatch for msg#{$m->id}: " . $e->getMessage() . "\n";
    }
}
echo "  dispatched vision jobs: {$redispatched}\n";
echo "  (new vision descriptions + product matching will land in ~30-60s per image)\n";

echo "\nDONE.\n";
