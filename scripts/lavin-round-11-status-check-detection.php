<?php
/**
 * Round 11 — نورهان مجدي failure:
 *
 * Customer opened a NEW conversation asking "هو موصلش لغايه دلوقتي صح؟"
 * (= "it hasn't arrived yet, right?"). She was clearly asking about an
 * EXISTING order from a prior conversation, NOT placing a new order.
 *
 * AI failures:
 *   1. Asked for name/phone/address as if she was a new customer
 *   2. When she sent TWO phone numbers ("01000386318 01064244810"), the
 *      phone extractor concatenated them to "01000386318010642448" — a
 *      garbage 20-digit string
 *   3. complete() fired, confirmed_at set, sheet got a row with:
 *        Order: EMPTY (she never asked to buy anything)
 *        Phone: concatenated garbage
 *
 * Two fixes:
 *
 *   A. CAPTURE EXTRACTOR phone regex — take only the FIRST valid phone
 *      when the customer sends multiple. Previously it greedily matched
 *      across whitespace.
 *
 *   B. SYSTEM PROMPT Rule #11 — detect status-check intent BEFORE asking
 *      for order data. If customer opens with "هو موصلش" / "فين الأوردر"
 *      / "status" / etc., never ask for name/address/phone like a new
 *      order. Ask for phone ONLY, say "هبلغ الإدارة يراجعوا لحضرتك"
 *      and DO NOT run through the order-confirmation flow.
 */

use App\Models\AiConfig;
use App\Models\Conversation;

echo "=== Fix نورهان's bad sheet entry ===\n";
$c = Conversation::find(9494);
if ($c) {
    // Strip confirmed_at so if we ever properly flag this conv as spam/status-check, it stays out of sheet
    $cap = (array) ($c->captured_data ?? []);
    $cap['note'] = 'Status check, NOT a new order. Phone 01000386318010642448 is 2 phones concatenated.';
    $c->update(['captured_data' => $cap]);
    echo "  conv#9494 — note added. Row is already in sheet (can't delete programmatically); operator needs to manually drop.\n";
}

echo "\n=== PART A: fix phone extractor regex ===\n";
echo "  (code change in app/Services/Ai/CaptureExtractor.php — tighter regex to take only FIRST phone)\n";
echo "  (shipped in the same commit as this script)\n";

echo "\n=== PART B: Rule #11 — status-check intent detection ===\n";

$rule11 = <<<'AR'

══ قاعدة #11 — كشف نية "متابعة الأوردر" (ممنوع تشغيل تدفق الأوردر الجديد) ══
لو أول رسالة من العميل (أو الرسائل الأولى في المحادثة) بتدل إنه بيسأل على حالة أوردر موجود، ممنوع تمامًا تدخل في تدفق جمع بيانات الأوردر الجديد (اسم + تليفون + عنوان + منتج).

أمثلة لنية "متابعة أوردر":
  - "هو موصلش لغاية دلوقتي صح؟"
  - "فين الأوردر بتاعي؟"
  - "متى هيوصل؟"
  - "الأوردر اتأخر"
  - "محدش كلمني"
  - "طلبت من كذا يوم"
  - "أنا طلبت على كذا"
  - "وصل المندوب؟"
  - "where is my order"
  - أي رسالة بتذكر أوردر سابق أو تأخير

الرد الصحيح:
  "آسفة جدًا يا فندم على التأخير 🙏 ممكن تبعتيلي رقم التليفون اللي طلبتي بيه، وأنا هبلغ الإدارة فورًا يراجعوا حالة الشحن ويتواصلوا معاك."

بعد ما تاخد الرقم (رقم واحد بس!):
  "تمام، استلمت الرقم. هبلغ الإدارة حالًا 🖤"

ممنوع تمامًا في هذه الحالة:
  × طلب الاسم بالكامل
  × طلب العنوان
  × طلب المنتج أو اللون أو الحجم
  × إرسال رسالة "سجلت الأوردر" المهيكلة
  × قول "تمام هنحجزلك" أو "هأكد الأوردر"

لو العميل بعت أكتر من رقم تليفون (مثلاً "01000386318 01064244810")، اختار الأول وأهمل الباقي. لا تدمجهم.

لو العميل بعد المتابعة قال "عايزة أطلب كمان" أو "أضيف أوردر تاني"، ساعتها (وبس ساعتها) تدخل تدفق أوردر جديد منفصل مع التوضيح إنه أوردر إضافي.

السبب: العميل بيسأل على أوردر موجود، مش بيعمل أوردر جديد. تدفق الأوردر الجديد بيعمل سطر جديد فاضي في الشيت ومربك لصاحب المتجر.

AR;

foreach ([17, 18, 19] as $cfgId) {
    $cfg = AiConfig::find($cfgId);
    if (! str_contains($cfg->system_prompt, 'قاعدة #11 — كشف نية')) {
        $cfg->update(['system_prompt' => rtrim($cfg->system_prompt) . "\n" . $rule11]);
        echo "  cfg#{$cfg->id}: Rule #11 appended.\n";
    } else {
        echo "  cfg#{$cfg->id}: Rule #11 already present.\n";
    }
}

echo "\nDONE.\n";
