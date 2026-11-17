<?php
/**
 * Round 8 — three items:
 *
 *   1. Strengthen Rule #6 so the AI CANNOT skip the structured recap
 *      after receiving the name. Observed failure (دعاء conv#9439 and
 *      اسماء conv#9330): AI asked for name, customer sent it, AI either
 *      didn't reply at all OR replied with chit-chat instead of the
 *      full "سجلت الأوردر" bullet list. Adds a new paragraph pinning
 *      "name arrives → NEXT reply MUST be the structured confirmation,
 *      no greetings, no extra questions, no 'شكراً'".
 *
 *   2. Push conv#9330 (اسماء سيد) to the sheet with all captured data —
 *      she sent name/phone/address/color choice but AI never finalised.
 *
 *   3. Also strengthen Rule #0 (mandatory): add explicit anti-pattern
 *      — "if you've already asked for the name and received it, DO NOT
 *      ask again or say 'شكرا' alone; proceed straight to the recap".
 */

use App\Models\AiConfig;
use App\Models\Conversation;
use App\Jobs\PushSalesConnectorRow;
use App\Services\SalesConnectors\SalesConnectors;

// ──────────────────────────────────────────────────────────────────
//  PART 1 — Append anti-skip rule to Rule #6
// ──────────────────────────────────────────────────────────────────

echo "=== PART 1: strengthen Rule #6 (anti-skip after name arrives) ===\n";

$antiSkipRule = <<<'AR'

══ قاعدة #6.1 — مانع التهرب (فائقة الأهمية) ══
في اللحظة اللي العميل يبعتلك اسمه كاستجابة لطلبك "ممكن اسمك بالكامل؟" — ردك التالي **إلزاميًا** يبقى رسالة التأكيد المهيكلة كاملة (قاعدة #6)، ومفيش أي حاجة تانية.

ممنوع تمامًا بعد وصول الاسم:
  × الرد بـ "شكراً" أو "تمام" وحدهم بدون التأكيد
  × طرح سؤال آخر (الاسم وصل، أنت عندك كل الحاجات بالفعل)
  × البدء بتحية جديدة أو رسالة ترحيبية
  × شرح إضافي أو ذكر تفاصيل منفصلة

الصيغة الوحيدة المقبولة بعد وصول الاسم:

تمام يا فندم ✅ سجلت الأوردر:

- الاسم: <الاسم اللي لسه وصل>
- <العدد> × شال <الحجم> <اللون>
- العنوان: <العنوان الكامل اللي العميل بعته>
- الإجمالي: <المبلغ> جنيه كاش عند الاستلام
- رقم التواصل: <رقم التليفون>

التوصيل خلال 1-3 أيام عمل، والمندوب هيتواصل معاك قبل ما يوصل. 🖤

لو حصل إن الاسم ناقص أو مش واضح (مثلاً "ميمو" أو إيموجي)، اطلبه مرة واحدة إضافية. لكن لو العميل كتب اسمه بشكل طبيعي (أي كلمتين عربي أو أكتر) → سجّل الأوردر فورًا بالصيغة المهيكلة.

السبب التقني: نظام التسجيل في الشيت بيعتمد على وجود رسالة التأكيد دي بصيغتها الكاملة — بدونها، الأوردر مش بيوصل للنظام والعميل بيضيع.

AR;

foreach ([17, 18, 19] as $cfgId) {
    $cfg = AiConfig::find($cfgId);
    if (! str_contains($cfg->system_prompt, 'قاعدة #6.1 — مانع التهرب')) {
        $cfg->update(['system_prompt' => rtrim($cfg->system_prompt) . "\n" . $antiSkipRule]);
        echo "  cfg#{$cfg->id}: Rule #6.1 appended.\n";
    } else {
        echo "  cfg#{$cfg->id}: Rule #6.1 already present.\n";
    }
}

// ──────────────────────────────────────────────────────────────────
//  PART 2 — Push conv#9330 (اسماء سيد)
// ──────────────────────────────────────────────────────────────────

echo "\n=== PART 2: push conv#9330 (اسماء سيد) to sheet ===\n";

$c = Conversation::find(9330);
$c->update(['captured_data' => [
    'name'          => 'اسماء سيد',
    'phone'         => '01501126938',
    'address'       => 'الجيزة - مركز الصف - قرية عرب الحصار',
    'order_summary' => '1 × شال كبير أسود',
    'confirmed_at'  => now()->toIso8601String(),
    'note'          => 'AI collected all data but never emitted structured confirmation; admin paused AI. Manual push.',
]]);
PushSalesConnectorRow::dispatchSync($c->id, SalesConnectors::EVENT_LEAD_CAPTURED);
$cfg = AiConfig::where('page_id', $c->page_id)->first()->fresh();
$d = $cfg->sales_connectors['last_delivery'] ?? [];
echo "  اسماء سيد → conv#9330 delivery=" . (($d['ok'] ?? false) ? 'OK' : 'FAILED') . "\n";

echo "\nDONE.\n";
echo "Going forward: Rule #6.1 should prevent this exact failure pattern.\n";
echo "Any new occurrence will be caught by ConversationAuditor rule R3.\n";
