<?php
/**
 * Lavin round 4 — three owner asks:
 *   1. Add نيبي (navy) to available colors.
 *   2. Name column must show ONLY the captured customer name — never the
 *      WhatsApp display name fallback.
 *   3. Order column should look like "2 شال كبير أسود + 3 شال صغير بيج"
 *      with QUANTITY first, then per-line item, multiple items per order.
 *
 * Implementation:
 *   - product_catalog + FAQ updated with نيبي (5 colors now).
 *   - required_capture_fields trimmed back to 4 (name, address, phone,
 *     order_summary). The LLM-extracts-from-customer-text strategy for
 *     color/quantity/product was producing garbage ("Product: الزيتون"
 *     when the customer meant the neighborhood). Now the order summary is
 *     parsed deterministically from the AI's own confirmation message (see
 *     SendAiResponse::parseAiOrderConfirmation).
 *   - system_prompt rewritten so the AI confirmation format MATCHES the
 *     parser exactly: `- <qty> × شال <size> <color>` per line.
 *   - Sheet cleared by owner → re-push every lead in the window so the new
 *     column layout (name blank where not captured, order_summary blank
 *     where AI hasn't confirmed yet) is visible.
 */

use App\Jobs\PushSalesConnectorRow;
use App\Models\AiConfig;
use App\Models\Conversation;
use App\Services\SalesConnectors\SalesConnectors;

const LAVIN_CONFIG_IDS     = [17, 18, 19];
const LAVIN_TEAM_ID        = 60;
const BACKFILL_SINCE_UTC   = '2026-11-15 20:00:00';

echo "=== PART 1: product catalog + FAQ (نيبي added) ===\n";

$firstCfg = AiConfig::find(LAVIN_CONFIG_IDS[0]);
$oldCatalog = (array) ($firstCfg->product_catalog ?? []);
$productKabirId = $oldCatalog[0]['id'];
$productSagirId = $oldCatalog[1]['id'];

$productCatalog = [
    [
        'id'          => $productKabirId,
        'name'        => 'شال كبير',
        'description' => 'شال نسائي شتوي — خامة ريب مضلعة مستوردة سميكة. الطول 120 سم. ألوان متاحة: بيج، أسود، برجندي، بني، نيبي. السعر الفردي 220 ج.م.',
        'price'       => '220 ج.م (قطاعي) · 200 ج.م (جملة 12+ قطعة)',
    ],
    [
        'id'          => $productSagirId,
        'name'        => 'شال صغير',
        'description' => 'شال نسائي بنفس خامة الريب المضلعة المستوردة بحجم أصغر. ألوان متاحة: بيج، أسود، برجندي، بني، نيبي. السعر الفردي 200 ج.م.',
        'price'       => '200 ج.م (قطاعي) · 180 ج.م (جملة 12+ قطعة)',
    ],
];

$faq = [
    [
        'question' => 'إيه الألوان المتاحة؟',
        'answer'   => 'متاح 5 ألوان لكل من الشال الكبير والصغير: بيج، أسود، برجندي، بني، نيبي. الزيتي والبيبي مش متاحين حاليًا.',
    ],
    [
        'question' => 'عندكم جملة؟',
        'answer'   => 'أيوه، الجملة من 12 قطعة فيما فوق، بخصم 20 ج.م لكل قطعة. يعني الشال الكبير يبقى 200 ج.م بدل 220، والصغير 180 ج.م بدل 200.',
    ],
    [
        'question' => 'الشحن بكام؟',
        'answer'   => 'الشحن 65 ج.م داخل القاهرة والجيزة، ويزيد 10 ج.م لكل محافظة بعد كده. يوصل خلال 1-3 أيام عمل.',
    ],
    [
        'question' => 'الدفع إزاي؟',
        'answer'   => 'كاش عند الاستلام (الدفع عند الباب).',
    ],
    [
        'question' => 'الخامة إيه؟',
        'answer'   => 'خامة ريب مضلعة مستوردة شتوية — سميكة ودافية ومناسبة لطقس الشتاء.',
    ],
    [
        'question' => 'إيه مقاسات الشالات؟',
        'answer'   => 'الشال الكبير بطول 120 سم. الشال الصغير مقاس أصغر بنفس الخامة.',
    ],
    [
        'question' => 'في معاينة قبل الاستلام؟',
        'answer'   => 'أيوه، معاينة فقط أمام المندوب — تقدر تشوف الشال قبل ما تدفع. بس مش فتح للمنتج ولا تجربة فعلية، ومفيش استرجاع بعد ما تستلم وتدفع.',
    ],
    [
        'question' => 'لو ما عجبنيش ممكن أرفض الاستلام؟',
        'answer'   => 'أكيد، لو رفضت تستلم الأوردر عند المندوب — بتدفع الشحن فقط (65 داخل القاهرة والجيزة، أو حسب محافظتك) من غير ثمن المنتج.',
    ],
    [
        'question' => 'في استرجاع أو استبدال بعد الاستلام؟',
        'answer'   => 'لا، مفيش استرجاع ولا استبدال بعد ما تدفع. اللي عندنا هو المعاينة أمام المندوب قبل الدفع فقط — بعد كده الأوردر نهائي.',
    ],
];

$requiredCaptureFields = [
    ['key' => 'name',    'label' => 'الاسم بالكامل',   'type' => 'text'],
    ['key' => 'address', 'label' => 'العنوان كامل',    'type' => 'address'],
    ['key' => 'phone',   'label' => 'رقم التليفون',    'type' => 'phone'],
    // order_summary is populated by SendAiResponse::parseAiOrderConfirmation
    // from the AI's own confirmation message — NOT by the LLM extractor from
    // customer messages. No 'required' semantics for it; it's a captured
    // field that marks the deal as closed.
    ['key' => 'order_summary', 'label' => 'ملخص الأوردر', 'type' => 'text'],
];

$systemPrompt = <<<'AR'
══ قاعدة #0 — إجبارية ══
مطلقًا لا تأكد أوردر بدون اسم العميل كامل. ممنوع تبعت رسالة "سجلت الأوردر" أو "تمام يا فندم" أو أي شكل من أشكال التأكيد النهائي قبل ما تاخد الاسم.

الترتيب الصحيح لجمع بيانات الأوردر:
  1. المنتج (شال كبير/صغير) + اللون + العدد (كل قطعة لها لون + حجم)
  2. العنوان (المحافظة + التفاصيل)
  3. رقم التليفون
  4. **الاسم بالكامل** ← قبل كلمة "سجلت الأوردر" مباشرة

لو العميل أعطاك العنوان والتليفون بس نسي الاسم، اسأله صريح:
  "تمام يا فندم ✅ قبل ما أثبت الأوردر، ممكن اسمك بالكامل؟"

لا تختصر أبدًا هذه الخطوة. الأوردر بدون اسم ما بيتسجلش في النظام.

══ قاعدة #1 — الألوان المتاحة ══
الألوان المتاحة فقط: بيج، أسود، برجندي، بني، نيبي. ونفسها للشال الكبير والصغير.
الزيتي والبيبي مش متاحين — ولا تفترض توافرهم أبدًا.
لو العميل طلب "زيتي" أو "بيبي" أو أي لون تاني، قوله بصراحة: "آسفة يا فندم، اللون ده مش متاح حاليًا. المتاح: بيج، أسود، برجندي، بني، نيبي. تحب تختار منهم؟"

══ قاعدة #2 — السعر والجملة ══
القطاعي: شال كبير 220 ج.م، شال صغير 200 ج.م.
الجملة: من 12 قطعة فيما فوق — خصم 20 ج.م لكل قطعة. شال كبير 200، شال صغير 180.
لو العميل طلب كمية 12+ ذكّره تلقائيًا بسعر الجملة.

══ قاعدة #3 — الشحن ══
65 ج.م داخل القاهرة والجيزة. +10 ج.م لكل محافظة بعد كده (البحيرة 75، أسوان 95، إلخ).
احسب الإجمالي صراحة في التأكيد: سعر المنتج + شحن.

══ قاعدة #4 — المعاينة والإرجاع ══
المعاينة **فقط أمام المندوب قبل الدفع** — العميل يشوف الشنطة من بره وبيقرر. مش فتح للمنتج ولا تجربة فعلية.
بعد ما العميل يستلم ويدفع، **مفيش استرجاع ولا استبدال** نهائي.
لو العميل سأل "ممكن أرفض؟" أو "لو ما عجبنيش؟" — قوله صريح: "أكيد، لو رفضت تستلم بتدفع الشحن فقط (65 داخل القاهرة/الجيزة أو حسب محافظتك)، من غير ثمن المنتج." ده مش استرجاع — ده رفض استلام قبل الدفع.

══ قاعدة #5 — الصور والفيديو ══
لو العميل طلب يشوف الشال أو قال "ممكن صور" / "اوريني" / "شكله" — ابعت فورًا الصور باستخدام الماركر [[SEND_MEDIA:product_id=<id>]].
لو طلب فيديو أو حركة أو طريقة اللبس — ابعت الفيديو كمان.

══ قاعدة #6 — صيغة التأكيد النهائي (مُهمة جدًا — النظام بيقرأها آليًا) ══
لما تجمع كل الحاجات (الاسم + القطع + العنوان + التليفون)، ابعت رسالة التأكيد بالصيغة دي بالضبط. السطور اللي بتبدأ بـ "-" لازم تكون زي كده بالحرف:

تمام يا فندم ✅ سجلت الأوردر:

- الاسم: <الاسم بالكامل>
- 2 × شال كبير أسود
- 1 × شال صغير بيج
- العنوان: <المحافظة + التفاصيل>
- الإجمالي: <السعر + الشحن> جنيه كاش عند الاستلام
- رقم التواصل: <التليفون>

التوصيل خلال 1-3 أيام عمل، والمندوب هيتواصل معاك قبل ما يوصل. 🖤

قواعد الصيغة:
  • لكل قطعة أو مجموعة قطع نفس اللون/الحجم → سطر منفصل بالصيغة: `- <العدد> × شال <حجم> <لون>`
  • لو الأوردر 3 قطع مختلفة → 3 سطور منفصلة.
  • لو الأوردر قطعة واحدة → `- 1 × شال <حجم> <لون>`
  • لا تجمع قطع مختلفة الألوان أو المقاسات في سطر واحد.
  • ترتيب السطور بعد الاسم مباشرة، قبل العنوان.

مثال صح:
- الاسم: هند محمد
- 2 × شال كبير بني
- 1 × شال صغير نيبي
- العنوان: الزمالك شارع 26 يوليو
- الإجمالي: 735 جنيه كاش عند الاستلام
- رقم التواصل: 01011008475

مثال غلط (ممنوع):
- شال × 2 (ناقص اللون والحجم)
- 2 شال كبير بيج + 1 صغير بني (أكتر من قطعة في نفس السطر)

لو أي حاجة من دول ناقصة، اسأل عليها واحدة واحدة. ما تسجلش الأوردر غير لما كل الحقول متوفرة.
AR;

foreach (LAVIN_CONFIG_IDS as $cfgId) {
    $cfg = AiConfig::find($cfgId);
    $cfg->update([
        'product_catalog'         => $productCatalog,
        'faq'                     => $faq,
        'required_capture_fields' => $requiredCaptureFields,
        'system_prompt'           => $systemPrompt,
    ]);
    echo "  cfg#{$cfg->id} (page#{$cfg->page_id}): catalog+faq+prompt+fields updated.\n";
}

// ──────────────────────────────────────────────────────────────────
//  PART 2 — Rebuild the sheet from empty state
// ──────────────────────────────────────────────────────────────────

echo "\n=== PART 2: refill sheet (owner cleared it) ===\n";

$since = Carbon\Carbon::parse(BACKFILL_SINCE_UTC, 'UTC');

$convs = Conversation::whereHas('page', fn ($q) => $q->where('team_id', LAVIN_TEAM_ID))
    ->where('last_message_at', '>=', $since)
    ->with(['contact', 'page', 'messages'])
    ->orderBy('last_message_at')
    ->get();

echo "  scanning " . $convs->count() . " conversations.\n";

$pushed = 0;
foreach ($convs as $c) {
    $captured = (array) ($c->captured_data ?? []);
    $hasProduct = ! empty($captured['product']) || ! empty($captured['order_summary']);
    $hasContact = ! empty($captured['address']) || ! empty($captured['phone']);

    $aiConfirmed = $c->messages->contains(function ($m) {
        if ($m->sender_type !== 'ai' || $m->direction !== 'outbound') return false;
        foreach (['سجلت الأوردر', 'الإجمالي', 'جنيه كاش'] as $p) {
            if (str_contains((string) $m->content, $p)) return true;
        }
        return false;
    });

    if (! $aiConfirmed && (! $hasProduct || ! $hasContact)) continue;

    PushSalesConnectorRow::dispatchSync($c->id, SalesConnectors::EVENT_LEAD_CAPTURED);
    $pushed++;
    if ($pushed === 1) echo "  first push on empty sheet — Apps Script will write new HEADERS.\n";
    usleep(300_000);
}

echo "  pushed: {$pushed}\n";
echo "\nDONE.\n";
