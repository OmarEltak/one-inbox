<?php
/**
 * Round 9 — three issues from Lavin's voice notes:
 *
 *   1. Rule #9 appended: AI must NEVER offer discounts, free shipping, or
 *      any financial concession that isn't explicitly in the config.
 *      Observed violation: AI told a <12-piece customer "عشان خاطرك هعملك
 *      الشحن مجاني" to compensate for not having wholesale pricing. That
 *      promise cost Lavin 65 ج.م and he had no idea it was made. Rule #9
 *      bans this across all 3 Lavin pages (WA, Telegram, FB).
 *
 *   2. Also stamp sheet_pushed_<event>_at on the 10 existing rows so the
 *      new lifetime dedupe doesn't accidentally re-push them when any
 *      flow retriggers (e.g. operator un-completes + re-completes).
 *
 *   3. Nothing to do for the "FB orders not in sheet" complaint — those
 *      conversations were handled EXTERNALLY by the owner (replying from
 *      his phone's WhatsApp/Messenger app directly). Our system never
 *      saw the owner's confirmation messages because they bypassed our
 *      app entirely. We'll surface this clearly to the owner so he knows
 *      to use our inbox for any order he wants tracked.
 */

use App\Models\AiConfig;
use App\Models\Conversation;

echo "=== PART 1: Rule #9 — no discount/shipping hallucination ===\n";

$rule9 = <<<'AR'

══ قاعدة #9 — ممنوع الوعود المالية غير المصرح بها (فائقة الأهمية) ══
ممنوع تمامًا على الذكاء الاصطناعي أن:
  × يقدم خصم من عنده (ولو "عشان خاطرك" أو كتعويض)
  × يقدم شحن مجاني (الشحن دايمًا بالسعر الحقيقي في قاعدة #3)
  × يقدم هدية أو قطعة مجانية أو عينة
  × يتنازل عن أي جزء من السعر لأي سبب (العميل عاوز خصم، العميل بيقارن أسعار، العميل يعتبر السعر غالي، أوردر متأخر، أي مشكلة جودة، إلخ)

لو العميل طلب خصم ومش مستوفي شروط الجملة (أقل من 12 قطعة):
  "آسفة يا فندم، الخصم عندنا بيبدأ من 12 قطعة فيما فوق — الشال الكبير يبقى 200 بدل 220، والصغير 180 بدل 200. أقل من 12 قطعة، السعر القطاعي ثابت."

لو العميل يشتكي من تأخر الأوردر:
  "آسفة جدًا على التأخير يا فندم 🙏 هبلغ الإدارة حالًا يراجعوا أي حاجة ممكن نعملها لحضرتك."
  (لا تعد بتعويض مالي)

لو العميل يشتكي من مشكلة في المنتج بعد الاستلام:
  "آسفة جدًا يا فندم على اللي حصل 🌷 هبلغ الإدارة فورًا يراجعوك بالحل المناسب."
  (قاعدة #4 بتقول مفيش استرجاع/استبدال بعد الاستلام — ما تخالفهاش بتعويض)

السبب: أي تنازل مالي بيحصل، صاحب المتجر بيدفعه من جيبه من غير ما يعرف. الذكاء الاصطناعي مش مفوض يأخذ قرارات تكلف فلوس. لو موقف صعب يحتاج استثناء، كلمة "هبلغ الإدارة" هي الحل الوحيد المسموح.

AR;

foreach ([17, 18, 19] as $cfgId) {
    $cfg = AiConfig::find($cfgId);
    if (! str_contains($cfg->system_prompt, 'قاعدة #9 — ممنوع الوعود المالية')) {
        $cfg->update(['system_prompt' => rtrim($cfg->system_prompt) . "\n" . $rule9]);
        echo "  cfg#{$cfg->id}: Rule #9 appended.\n";
    } else {
        echo "  cfg#{$cfg->id}: Rule #9 already present.\n";
    }
}

echo "\n=== PART 2: backstamp sheet_pushed flag on existing rows ===\n";

$alreadyInSheet = Conversation::whereHas('page', fn ($q) => $q->where('team_id', 60))
    ->whereNotNull('captured_data->confirmed_at')
    ->get();

$stamped = 0;
foreach ($alreadyInSheet as $c) {
    $cap = (array) ($c->captured_data ?? []);
    $flagKey = 'sheet_pushed_lead_captured_at';
    if (empty($cap[$flagKey])) {
        $cap[$flagKey] = $cap['confirmed_at'] ?? now()->toIso8601String();
        $c->update(['captured_data' => $cap]);
        $stamped++;
    }
}
echo "  backstamped sheet_pushed flag on {$stamped} already-in-sheet conversations\n";
echo "  going forward: these won't be re-pushed even if complete() retriggers\n";

echo "\nDONE.\n";
