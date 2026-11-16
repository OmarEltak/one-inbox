<?php
/**
 * Comprehensive Lavin Fashion update:
 *
 *   PART A — Config update across all 3 pages (WA, Telegram, FB):
 *     - product_catalog: new colors (بيج/أسود/برجندي/بني for BOTH شالين,
 *       NO زيتي anywhere, NO بيبي), updated شال صغير price (200 EGP retail)
 *     - pricing_info: shipping (60 Cairo/Giza, +10 per محافظة) +
 *       wholesale (12+ pieces = 20 EGP discount per piece)
 *     - faq: wholesale, inspection-only (no returns), colors clarification
 *     - system_prompt: MANDATORY NAME CAPTURE before any order confirmation
 *     - required_capture_fields: add "name" (was missing — root cause of
 *       the sheet never receiving a row, because complete() only fires
 *       when ALL required fields are captured)
 *
 *   PART B — Backfill + follow-up:
 *     - Scan every team 60 conversation since 2026-11-15 20:00 UTC
 *     - For conversations with captured_data containing product + address +
 *       phone, treat as a lead; push to the connector sheet
 *     - For conversations where the AI confirmed an order (message contains
 *       "سجلت الأوردر") but the contact has no name captured, dispatch a
 *       WhatsApp message asking for the name (one per contact — idempotent
 *       via metadata.name_followup_sent_at)
 */

use App\Jobs\PushSalesConnectorRow;
use App\Models\AiConfig;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;
use App\Services\EvolutionApiService;
use App\Services\SalesConnectors\SalesConnectors;

const LAVIN_TEAM_ID        = 60;
const LAVIN_CONFIG_IDS     = [17, 18, 19]; // WA, Telegram, FB
const BACKFILL_SINCE_UTC   = '2026-11-15 20:00:00'; // Nov 15 22:00 Cairo
const NAME_FOLLOWUP_PROMPT = "عفواً يا فندم، الأوردر مسجل بس باقي حاجة بس — ممكن اسمك بالكامل عشان نكده الأوردر ونقوله للمندوب يسألك عليه؟ 🙏";

// ──────────────────────────────────────────────────────────────────
//  PART A — Config update
// ──────────────────────────────────────────────────────────────────

echo "=== PART A: updating 3 Lavin AiConfigs ===\n";

// Shared, stable UUIDs so media rows (already uploaded under these keys)
// keep pointing to the right product. Read the first config to pull them.
$firstCfg = AiConfig::find(LAVIN_CONFIG_IDS[0]);
$oldCatalog = (array) ($firstCfg->product_catalog ?? []);
if (count($oldCatalog) < 2) {
    throw new RuntimeException('Expected 2 products in the existing catalog — aborting.');
}
$productKabirId = $oldCatalog[0]['id'];
$productSagirId = $oldCatalog[1]['id'];
echo "  product UUIDs preserved: كبير={$productKabirId}, صغير={$productSagirId}\n";

$productCatalog = [
    [
        'id'          => $productKabirId,
        'name'        => 'شال كبير',
        'description' => 'شال نسائي شتوي — خامة ريب مضلعة مستوردة سميكة. الطول 120 سم. ألوان متاحة: بيج، أسود، برجندي، بني. السعر الفردي 220 ج.م.',
        'price'       => '220 ج.م (قطاعي) · 200 ج.م (جملة 12+ قطعة)',
    ],
    [
        'id'          => $productSagirId,
        'name'        => 'شال صغير',
        'description' => 'شال نسائي بنفس خامة الريب المضلعة المستوردة بحجم أصغر. ألوان متاحة: بيج، أسود، برجندي، بني. السعر الفردي 200 ج.م.',
        'price'       => '200 ج.م (قطاعي) · 180 ج.م (جملة 12+ قطعة)',
    ],
];

$pricingInfo = [
    ['item' => 'شحن القاهرة والجيزة', 'price' => '60 ج.م', 'notes' => 'يوصل خلال 1-3 أيام عمل.'],
    ['item' => 'شحن باقي المحافظات',  'price' => '70-100 ج.م', 'notes' => 'تزيد 10 ج.م لكل محافظة بعد القاهرة/الجيزة.'],
    ['item' => 'سعر الجملة',            'price' => 'خصم 20 ج.م لكل قطعة', 'notes' => 'يبدأ من 12 قطعة. شال كبير 200 ج.م/قطعة، شال صغير 180 ج.م/قطعة.'],
];

$faq = [
    [
        'question' => 'إيه الألوان المتاحة؟',
        'answer'   => 'متاح 4 ألوان لكل من الشال الكبير والصغير: بيج، أسود، برجندي، بني. مفيش حاليًا زيتي ولا بيبي.',
    ],
    [
        'question' => 'عندكم جملة؟',
        'answer'   => 'أيوه، الجملة من 12 قطعة فيما فوق، بخصم 20 ج.م لكل قطعة. يعني الشال الكبير يبقى 200 ج.م بدل 220، والصغير 180 ج.م بدل 200.',
    ],
    [
        'question' => 'الشحن بكام؟',
        'answer'   => 'الشحن 60 ج.م داخل القاهرة والجيزة، ويزيد 10 ج.م لكل محافظة بعد كده. يوصل خلال 1-3 أيام عمل.',
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
        'answer'   => 'أيوه — معاينة فقط. يعني تقدر تفتح الشنطة وتشوف المنتج قبل ما تدفع، بس لو استلمت المنتج مفيش استرجاع أو استبدال بعد كده.',
    ],
    [
        'question' => 'في استرجاع أو استبدال؟',
        'answer'   => 'لا، مفيش استرجاع ولا استبدال. اللي عندنا هو معاينة فقط قبل الاستلام — تتأكد من اللون والمقاس قبل ما تدفع.',
    ],
];

// MANDATORY NAME CAPTURE + behaviour upgrades
$systemPrompt = <<<'AR'
══ قاعدة #0 — إجبارية ══
مطلقًا لا تأكد أوردر بدون اسم العميل كامل. ممنوع تبعت رسالة "سجلت الأوردر" أو "تمام يا فندم" أو أي شكل من أشكال التأكيد النهائي قبل ما تاخد الاسم.

الترتيب الصحيح لجمع بيانات الأوردر:
  1. المنتج (شال كبير/صغير) + اللون + العدد
  2. العنوان (المحافظة + التفاصيل)
  3. رقم التليفون
  4. **الاسم بالكامل** ← قبل كلمة "سجلت الأوردر" مباشرة

لو العميل أعطاك العنوان والتليفون بس نسي الاسم، اسأله صريح:
  "تمام يا فندم ✅ قبل ما أثبت الأوردر، ممكن اسمك بالكامل؟"

لا تختصر أبدًا هذه الخطوة. الأوردر بدون اسم ما بيتسجلش في النظام.

══ قاعدة #1 — الألوان المتاحة ══
الألوان المتاحة فقط: بيج، أسود، برجندي، بني. ونفسها للشال الكبير والصغير.
لو العميل طلب "زيتي" أو "بيبي" أو أي لون تاني، قوله بصراحة: "آسفة، اللون ده مش متاح. المتاح: بيج، أسود، برجندي، بني. تحب تختار منهم؟"

══ قاعدة #2 — السعر والجملة ══
القطاعي: شال كبير 220 ج.م، شال صغير 200 ج.م.
الجملة: من 12 قطعة فيما فوق — خصم 20 ج.م لكل قطعة. شال كبير 200، شال صغير 180.
لو العميل طلب كمية 12+ ذكّره تلقائيًا بسعر الجملة.

══ قاعدة #3 — الشحن ══
60 ج.م داخل القاهرة والجيزة. +10 ج.م لكل محافظة بعد كده (يعني البحيرة مثلًا 70، أسوان 90، إلخ).
احسب الإجمالي صراحة في التأكيد: سعر المنتج + شحن.

══ قاعدة #4 — الإرجاع ══
مفيش استرجاع ولا استبدال بعد الاستلام. اللي متاح هو **معاينة فقط قبل الاستلام** (يفتح الشنطة قبل ما يدفع). لو سأل عن إرجاع، اشرحله ده بوضوح.

══ قاعدة #5 — الصور والفيديو ══
لو العميل طلب يشوف الشال أو قال "ممكن صور" / "اوريني" / "شكله" — ابعت فورًا الصور باستخدام الماركر [[SEND_MEDIA:product_id=<id>]].
لو طلب فيديو أو حركة أو طريقة اللبس — ابعت الفيديو كمان.

══ قاعدة #6 — صيغة التأكيد النهائي ══
لما تجمع كل الحاجات (الاسم + المنتج + اللون + العدد + العنوان + التليفون)، ابعت رسالة تأكيد بالصيغة دي بالظبط:

تمام يا فندم ✅ سجلت الأوردر:

- الاسم: <الاسم بالكامل>
- <المنتج> <اللون> × <العدد>
- العنوان: <المحافظة + التفاصيل>
- الإجمالي: <السعر + الشحن> جنيه كاش عند الاستلام
- رقم التواصل: <التليفون>

التوصيل خلال 1-3 أيام عمل، والمندوب هيتواصل معاك قبل ما يوصل. 🖤

لو أي حاجة من دول ناقصة، اسأل عليها واحدة واحدة. ما تسجلش الأوردر غير لما كل الحقل متوفر.
AR;

$requiredCaptureFields = [
    ['key' => 'name',    'label' => 'الاسم بالكامل',   'type' => 'text'],
    ['key' => 'product', 'label' => 'المنتج واللون',    'type' => 'text'],
    ['key' => 'address', 'label' => 'العنوان كامل',    'type' => 'address'],
    ['key' => 'phone',   'label' => 'رقم التليفون',    'type' => 'phone'],
];

foreach (LAVIN_CONFIG_IDS as $cfgId) {
    $cfg = AiConfig::find($cfgId);
    $cfg->update([
        'product_catalog'         => $productCatalog,
        'pricing_info'            => $pricingInfo,
        'faq'                     => $faq,
        'system_prompt'           => $systemPrompt,
        'required_capture_fields' => $requiredCaptureFields,
    ]);
    echo "  cfg#{$cfg->id} (page#{$cfg->page_id}) updated.\n";
}

// ──────────────────────────────────────────────────────────────────
//  PART B — Backfill + follow-up
// ──────────────────────────────────────────────────────────────────

echo "\n=== PART B: backfilling sheet + name follow-ups ===\n";

$since = Carbon\Carbon::parse(BACKFILL_SINCE_UTC, 'UTC');

$convs = Conversation::whereHas('page', fn ($q) => $q->where('team_id', LAVIN_TEAM_ID))
    ->where('last_message_at', '>=', $since)
    ->with(['contact', 'page', 'messages'])
    ->get();

echo "  conversations in window: " . $convs->count() . "\n";

$pushed    = 0;
$completed = 0;
$asked     = 0;
$skipped   = [];

foreach ($convs as $c) {
    $captured = (array) ($c->captured_data ?? []);

    // Detection: did the AI confirm an order? Look for "سجلت الأوردر" in any
    // AI outbound message on the conversation.
    $aiConfirmed = $c->messages
        ->where('sender_type', 'ai')
        ->where('direction', 'outbound')
        ->contains(fn ($m) => str_contains((string) $m->content, 'سجلت الأوردر'));

    // Push a row if we have real lead data (product + either address or phone)
    // — this covers both completed and partially-captured conversations.
    $hasProduct = ! empty($captured['product']);
    $hasContact = ! empty($captured['address']) || ! empty($captured['phone']);

    if (! $aiConfirmed && (! $hasProduct || ! $hasContact)) {
        continue; // not a lead
    }

    // Push lead_captured event. The job reads sales_connectors from the
    // conv's page→aiConfig, serialises the row, and POSTs to Apps Script.
    try {
        PushSalesConnectorRow::dispatchSync($c->id, SalesConnectors::EVENT_LEAD_CAPTURED);
        $pushed++;
    } catch (\Throwable $e) {
        $skipped[] = "conv#{$c->id}: push failed — " . $e->getMessage();
        continue;
    }

    // Mark completed if all 4 required fields present (name/product/address/phone)
    $allPresent = ! empty($captured['name']) && $hasProduct
        && ! empty($captured['address']) && ! empty($captured['phone']);
    if ($allPresent && $c->sales_stage !== 'completed') {
        $c->complete('backfill_from_ai_confirmation');
        $completed++;
    }

    // Trigger name follow-up for AI-confirmed orders missing a name.
    // One per conversation — guarded by metadata.name_followup_sent_at.
    $meta = $c->metadata ?? [];
    $alreadyAsked = ! empty($meta['name_followup_sent_at']);
    $needsName = $aiConfirmed && empty($captured['name']);

    if ($needsName && ! $alreadyAsked && $c->platform === 'whatsapp') {
        $instance = $c->page->metadata['gateway_instance'] ?? $c->page->platform_page_id;
        $apiKey   = $c->page->page_access_token;
        $recipient = $c->platform_conversation_id;

        try {
            $messageId = app(EvolutionApiService::class)
                ->sendText($instance, $apiKey, $recipient, NAME_FOLLOWUP_PROMPT);

            if ($messageId) {
                // Persist the outbound Message so it shows in the inbox
                Message::create([
                    'conversation_id'     => $c->id,
                    'direction'           => 'outbound',
                    'sender_type'         => 'ai',
                    'content_type'        => 'text',
                    'content'             => NAME_FOLLOWUP_PROMPT,
                    'platform_message_id' => $messageId,
                    'platform_sent_at'    => now(),
                ]);

                $meta['name_followup_sent_at'] = now()->toIso8601String();
                $c->update(['metadata' => $meta]);
                $asked++;
            } else {
                $skipped[] = "conv#{$c->id}: Wuzapi sendText returned null";
            }
        } catch (\Throwable $e) {
            $skipped[] = "conv#{$c->id}: follow-up failed — " . $e->getMessage();
        }
    }
}

echo "\n=== SUMMARY ===\n";
echo "  pushed to sheet:         {$pushed}\n";
echo "  marked completed:        {$completed}\n";
echo "  name follow-ups sent:    {$asked}\n";
echo "  skipped: " . count($skipped) . "\n";
foreach ($skipped as $s) {
    echo "    - {$s}\n";
}
