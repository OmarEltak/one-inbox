<?php
/**
 * Round 6 — three items:
 *   1. System prompt: NEVER address the customer by their first name.
 *      Always use "يا فندم". Observed violations: "تمام يا محمد" (conv#9433),
 *      "تسلمي يا هبه" (conv#9430). This rule mirrors the one on OT1-Pro
 *      (memory pin: "NEVER use the customer's first name").
 *
 *   2. Push TWO missed orders that are in fact complete but didn't get
 *      confirmed_at stamped because the AI never emitted the final structured
 *      confirmation (customer gave data out of order):
 *        - conv#9430 (ام روضه جمال → هبه محمد): شال كبير أسود + شال صغير بني
 *        - conv#9439 (メڊوُڊوُ → دعاء عبد الدايم): شال صغير برجندي
 *      Also fix conv#9430's address which was truncated to just "الجيزه" by
 *      the LLM extractor (customer gave detail in a second message).
 *
 *   3. Find any other recent convo where the customer gave a complete-looking
 *      order (name + phone + address + has order discussion) but the AI
 *      never confirmed. Report them so operator can send follow-ups.
 */

use App\Models\AiConfig;
use App\Models\Conversation;
use App\Models\Message;
use App\Jobs\PushSalesConnectorRow;
use App\Services\SalesConnectors\SalesConnectors;

// ──────────────────────────────────────────────────────────────────
//  PART 1 — new Rule #8 (no first-name addressing)
// ──────────────────────────────────────────────────────────────────

echo "=== PART 1: Rule #8 — never address customer by first name ===\n";

$nameRule = <<<'AR'

══ قاعدة #8 — ممنوع مناداة العميل باسمه الأول ══
مهما حصل، لا تخاطب العميل باسمه الأول. ممنوع كلمات زي "يا محمد" / "يا هبه" / "يا أحمد" / أي اسم أول حتى لو العميل كتبهولك بنفسه.

البديل الصحيح دايمًا: استخدم "يا فندم" أو "حضرتك" أو "يا قمر" أو مفيش أي نداء على الإطلاق.

أمثلة صح:
  "تسلمي يا فندم 🌸 ممكن العنوان بالتفصيل؟"
  "تمام حضرتك، مستنيك 🤍"
  "ممكن اسمك بالكامل عشان أثبت الأوردر؟"

أمثلة غلط (ممنوع):
  "تسلمي يا هبه 🌸"            ← استخدام الاسم الأول
  "تمام يا محمد، مستنيك"        ← استخدام الاسم الأول
  "آسف يا محمد"                ← استخدام الاسم الأول

السبب: مش كل العملاء بيحبوا يُنادوا باسمهم، وبعضهم بيحس إنه تطفل. استخدام "يا فندم" أو "حضرتك" آمن مع الكل.

AR;

foreach ([17, 18, 19] as $cfgId) {
    $cfg = AiConfig::find($cfgId);
    if (! str_contains($cfg->system_prompt, 'قاعدة #8 — ممنوع مناداة العميل')) {
        $cfg->update(['system_prompt' => rtrim($cfg->system_prompt) . "\n" . $nameRule]);
        echo "  cfg#{$cfgId}: Rule #8 appended.\n";
    } else {
        echo "  cfg#{$cfgId}: Rule #8 already present — skipping.\n";
    }
}

// ──────────────────────────────────────────────────────────────────
//  PART 2 — push 2 missed orders (manual stamp of captured_data)
// ──────────────────────────────────────────────────────────────────

echo "\n=== PART 2: push 2 missed orders to the sheet ===\n";

$missed = [
    [
        'conv_id'  => 9430,
        'contact'  => 'ام روضه جمال → هبه محمد',
        'captured' => [
            'name'          => 'هبه محمد',
            'phone'         => '01129646619',
            'address'       => 'الجيزة - أكتوبر - ال800 فدان، عمارة 11، قطاع ألف',
            'order_summary' => '1 × شال كبير أسود + 1 × شال صغير بني',
            'confirmed_at'  => now()->toIso8601String(),
        ],
    ],
    [
        'conv_id'  => 9439,
        'contact'  => 'メڊوُڊوُ → دعاء عبد الدايم',
        'captured' => [
            'name'          => 'دعاء عبد الدايم',
            'phone'         => '01009460680',
            'address'       => 'السويس - برج اللؤلؤة، شارع ناصر، أمام كارفور',
            'order_summary' => '1 × شال صغير برجندي',
            'confirmed_at'  => now()->toIso8601String(),
        ],
    ],
];

foreach ($missed as $m) {
    $c = Conversation::find($m['conv_id']);
    if (! $c) { echo "  conv#{$m['conv_id']} not found\n"; continue; }
    $c->update(['captured_data' => $m['captured']]);
    try {
        PushSalesConnectorRow::dispatchSync($c->id, SalesConnectors::EVENT_LEAD_CAPTURED);
        $cfg = AiConfig::where('page_id', $c->page_id)->first()->fresh();
        $d = $cfg->sales_connectors['last_delivery'] ?? [];
        $ok = ($d['ok'] ?? false) ? 'OK' : 'FAILED';
        echo "  {$m['contact']} → conv#{$c->id} delivery={$ok}\n";
    } catch (\Throwable $e) {
        echo "  {$m['contact']} → EXCEPTION: " . $e->getMessage() . "\n";
    }
    usleep(500_000);
}

// ──────────────────────────────────────────────────────────────────
//  PART 3 — report convos that look like near-complete orders
// ──────────────────────────────────────────────────────────────────

echo "\n=== PART 3: near-complete orders (operator may want to follow up) ===\n";

$since = Carbon\Carbon::parse('2026-11-15 00:00:00', 'UTC');

$candidates = Conversation::whereHas('page', fn ($q) => $q->where('team_id', 60))
    ->where('last_message_at', '>=', $since)
    ->with(['contact', 'messages'])
    ->get()
    ->filter(function ($c) {
        $cap = $c->captured_data ?? [];
        if (! empty($cap['confirmed_at'])) return false; // already in sheet
        // Has at least 2 of (name/phone/address) AND customer discussed an order (شال)
        $filled = 0;
        foreach (['name', 'phone', 'address'] as $k) if (! empty($cap[$k])) $filled++;
        if ($filled < 2) return false;
        $hasOrderChat = $c->messages->contains(fn ($m) => str_contains((string) $m->content, 'شال'));
        return $hasOrderChat;
    });

echo "  found: " . $candidates->count() . " near-complete convos (NOT in sheet, with order discussion + 2+ fields)\n";
foreach ($candidates->take(20) as $c) {
    $cap = $c->captured_data;
    $missing = [];
    foreach (['name', 'phone', 'address'] as $k) {
        if (empty($cap[$k])) $missing[] = $k;
    }
    $status = $c->ai_paused ? 'PAUSED' : 'AI active';
    echo "  conv#{$c->id} {$c->platform} {$status} stage={$c->sales_stage} contact=" . substr((string)$c->contact?->name,0,20) . " missing: " . implode(',', $missing) . "\n";
}

echo "\nDONE.\n";
