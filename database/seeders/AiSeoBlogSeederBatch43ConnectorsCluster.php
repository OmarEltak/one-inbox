<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

/**
 * Batch 43 — CRM Connectors / Google Sheets cluster
 * EN + AR posts targeting "AI closed deals Google Sheets" intent
 */
class AiSeoBlogSeederBatch43ConnectorsCluster extends Seeder
{
    public function run(): void
    {
        $ctaEn = $this->ctaEn();
        $ctaAr = $this->ctaAr();
        foreach ($this->posts() as $post) {
            $isAr = str_ends_with($post['slug'], '-ar');
            $post['content'] = str_replace('{{CTA}}', $isAr ? $ctaAr : $ctaEn, $post['content']);
            Post::updateOrCreate(['slug' => $post['slug']], $post);
        }
    }

    private function ctaEn(): string
    {
        return <<<'HTML'
<h2>Try the AI inbox built for real businesses</h2>
<p>OT1-Pro handles your WhatsApp, Instagram, Messenger, Telegram, and email in one screen — with an AI agent that closes deals and automatically logs every captured lead and closed sale to your Google Sheet. <a href="https://ot1-pro.com/register">Free plan, no credit card →</a></p>
<p><a href="https://ot1-pro.com/pricing">Pricing from $29/mo</a> · <a href="https://ot1-pro.com/vs/wati">Why we beat WATI</a> · <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">The Meta verification guide</a> · <a href="https://wa.me/201026361218">Talk to me on WhatsApp</a></p>
HTML;
    }

    private function ctaAr(): string
    {
        return <<<'HTML'
<h2>جرّب صندوق البريد الذكي المبني للأعمال الحقيقية</h2>
<p>OT1-Pro يُدير واتساب وانستاجرام وماسنجر وتيليجرام والبريد الإلكتروني في شاشة واحدة — مع وكيل ذكاء اصطناعي يُغلق الصفقات ويُسجّل تلقائياً كل عميل محتمل تم التقاطه وكل بيع مُغلق في جوجل شيتس. <a href="https://ot1-pro.com/register">خطة مجانية، بدون بطاقة ائتمان ←</a></p>
<p><a href="https://ot1-pro.com/pricing">الأسعار من $29/شهر</a> · <a href="https://ot1-pro.com/vs/wati">لماذا نتفوق على WATI</a> · <a href="https://wa.me/201026361218">تحدث معي على واتساب</a></p>
HTML;
    }

    private function posts(): array
    {
        $now = now();

        return [

            // ─────────────────────────────────────────────────────────────
            // 1. Connectors / Google Sheets — English
            // ─────────────────────────────────────────────────────────────
            [
                'title'            => 'Every Closed Deal Goes to My Google Sheet Automatically — Zero Human Copy-Paste',
                'slug'             => 'ai-closed-deals-google-sheets-connector',
                'excerpt'          => 'OT1-Pro\'s Connectors tab lets you link your AI agent to a Google Sheet so every captured lead and every closed deal lands there automatically — name, phone, what they ordered, timestamp. I haven\'t manually copied a lead from WhatsApp to a spreadsheet in four months.',
                'meta_title'       => 'AI Closed Deals Auto-Sync to Google Sheets: OT1-Pro',
                'meta_description' => 'Connect your OT1-Pro AI agent to Google Sheets and every captured lead and closed deal syncs automatically — no copy-paste. AI closed deals Google Sheets integration.',
                'category'         => 'Integrations',
                'published_at'     => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
                'content'          => <<<'HTML'
<p>For the first year of running my business, every morning started the same way. I would open WhatsApp, scroll back through the previous day's conversations, copy every name and phone number and product enquiry into a Google Sheet, and try to figure out who had actually placed an order versus who had asked for a price and gone quiet. This took about 45 minutes every morning. I did it because I had to — without that spreadsheet, I had no record of who to follow up with, no data for my monthly review, and no input for my ads targeting. Last month I realised I hadn't done that manual copy-paste in four months. The AI had been doing it automatically the whole time.</p>

<h2>What the Connectors tab does</h2>

<p>In OT1-Pro's AI Config settings, there is a Connectors tab. This is where you define what happens when the AI captures a lead or closes a deal. The connector links your AI agent to an external destination — currently Google Sheets, with more destinations coming — and specifies what data to send when a trigger fires.</p>

<p>Two triggers are available:</p>

<ul>
<li><strong>Lead captured:</strong> When the AI collects key information from a customer — name, phone, what they're interested in — it sends a row to your Google Sheet. This fires when the AI determines it has enough qualification information to log the contact as a lead.</li>
<li><strong>Deal closed:</strong> When the AI determines a customer has committed to a purchase (confirmed an order, agreed to a price, said "yes I'll take it"), it sends a row to your Google Sheet with the deal details.</li>
</ul>

<p>Each row sent to the sheet includes: customer name, phone number, platform (WhatsApp/Instagram/Messenger), product(s) they ordered or enquired about, the conversation summary, and a timestamp. The Sheet populates automatically, in real time, as the AI handles conversations — no human in the loop.</p>

<h2>Setting it up: the actual steps</h2>

<ol>
<li><strong>Create a Google Sheet and get its URL.</strong> The Sheet just needs to exist — OT1-Pro creates the column headers automatically on the first sync.</li>
<li><strong>Go to AI Config → Connectors tab in OT1-Pro.</strong></li>
<li><strong>Click "Add Connector" and select Google Sheets.</strong></li>
<li><strong>Paste your Sheet URL and authenticate.</strong> OT1-Pro will ask you to authorise access to your Google Drive so it can write to the Sheet.</li>
<li><strong>Configure your triggers.</strong> Toggle "Send on lead captured" and/or "Send on deal closed." Choose which fields to include in each row.</li>
<li><strong>Save and test.</strong> You can fire a test event to verify a row appears in the Sheet before going live.</li>
</ol>

<p>Total setup time: about 8 minutes the first time. After that, it runs forever without any maintenance unless you change the Sheet URL.</p>

<h2>What the Sheet looks like in practice</h2>

<p>My Sheet has 7 columns that OT1-Pro writes automatically:</p>

<table>
<thead>
<tr><th>Column</th><th>Example value</th><th>Source</th></tr>
</thead>
<tbody>
<tr><td>Timestamp</td><td>2026-11-04 14:32</td><td>Auto</td></tr>
<tr><td>Name</td><td>Sara Mohamed</td><td>From conversation</td></tr>
<tr><td>Phone</td><td>+20 100 XXXX XXX</td><td>From conversation</td></tr>
<tr><td>Platform</td><td>WhatsApp</td><td>Auto</td></tr>
<tr><td>Product/Interest</td><td>Winter jacket, size M, burgundy</td><td>AI extracted</td></tr>
<tr><td>Status</td><td>Deal closed</td><td>Trigger type</td></tr>
<tr><td>Summary</td><td>Customer confirmed order at $89, paying via bank transfer, ship to Cairo</td><td>AI generated</td></tr>
</tbody>
</table>

<p>Every morning I open this Sheet and I have a complete, accurate record of every lead and sale from the previous day. No copy-paste. No missed entries because I scrolled past a conversation. No inconsistent formatting from manual data entry. The Sheet is the single source of truth for my sales pipeline.</p>

<h2>What I use the Sheet for</h2>

<p>The Sheet has become the backbone of four things I do every week:</p>

<ol>
<li><strong>Follow-up targeting.</strong> Every Monday morning I filter the Sheet for "Lead captured" status from the previous week that hasn't moved to "Deal closed." Those are my follow-up calls for the day — people who showed interest but didn't pull the trigger. I go through them personally, usually about 8–12 people. My follow-up conversion rate on these is around 22%.</li>
<li><strong>Ads retargeting audience.</strong> Once a month, I export the phone numbers from "Deal closed" rows into a CSV and upload it to Meta as a Custom Audience for Lookalike targeting. My current buyers are my best signal for who else will buy. This is the most accurate audience I can build — better than any demographic or interest-based targeting.</li>
<li><strong>Monthly revenue review.</strong> I count the "Deal closed" rows, multiply by average order value, and compare to the previous month. This is my baseline revenue metric. It is not perfect — it misses orders placed directly through the website without an AI conversation — but it covers about 70% of my actual sales volume.</li>
<li><strong>AI training improvements.</strong> Every week I skim the "Summary" column for conversations where the AI identified a lead but the deal did not close. These summaries tell me what objections or gaps caused the stall. I use that information to update the AI's training prompts so the same stall happens less often next month.</li>
</ol>

<h2>The time math</h2>

<p>Before the Connector: 45 minutes per day copying leads from WhatsApp to spreadsheet × 5 days × 4 weeks = 15 hours per month spent on manual data entry. At $30/hour opportunity cost: $450/month in time I was spending on copy-paste.</p>

<p>After the Connector: 0 minutes on data entry. The Sheet is always current, always accurate, always complete. The 15 hours went back to work that actually moves the business forward. The Connector feature is included in OT1-Pro plans from $29/mo — no additional cost, no Zapier required, no code.</p>

<h2>How it compares to Zapier + WhatsApp workarounds</h2>

<p>Some businesses build this manually with Zapier connecting a WhatsApp Business API to a Google Sheet. The problems with that approach:</p>

<ul>
<li>Zapier webhooks capture raw message data, not AI-structured lead information. You get a dump of message text, not "customer name: Sara, product: winter jacket, status: deal closed."</li>
<li>Zapier's free tier limits task runs. At 100+ conversations per day, you will hit the ceiling. The paid tier is $19.99/mo for 750 tasks or $49/mo for 2,000 tasks — you are paying twice for functionality that is built into OT1-Pro.</li>
<li>Maintaining the Zapier flow when Meta changes their API (which happens 2–3 times per year) requires a developer or significant technical effort.</li>
</ul>

<p>OT1-Pro's Connector is native — it understands the AI's conversation model and sends structured, clean data without any middleware. See <a href="https://ot1-pro.com/pricing">pricing here</a> or <a href="https://ot1-pro.com/vs/wati">compare with WATI</a>. <a href="https://ot1-pro.com/register">Start free →</a></p>

{{CTA}}
HTML,
            ],

            // ─────────────────────────────────────────────────────────────
            // 2. Connectors / Google Sheets — Arabic
            // ─────────────────────────────────────────────────────────────
            [
                'title'            => 'كل صفقة مُغلقة تُرسل تلقائياً لجوجل شيتس — صفر نسخ ولصق',
                'slug'             => 'ai-closed-deals-google-sheets-ar',
                'excerpt'          => 'تبويب Connectors في OT1-Pro يُتيح ربط وكيل الذكاء الاصطناعي بجوجل شيتس لأن كل عميل محتمل تم التقاطه وكل صفقة مُغلقة تصل هناك تلقائياً — الاسم، الهاتف، ما طلبوه، الوقت. لم أنسخ عميلاً محتملاً من واتساب لجدول بيانات يدوياً منذ أربعة أشهر.',
                'meta_title'       => 'الصفقات المُغلقة تُزامَن تلقائياً مع جوجل شيتس في OT1-Pro',
                'meta_description' => 'اربط وكيل الذكاء الاصطناعي في OT1-Pro بجوجل شيتس وكل عميل محتمل وصفقة مُغلقة تُزامَن تلقائياً. تكامل الصفقات المُغلقة مع جوجل شيتس بالذكاء الاصطناعي.',
                'category'         => 'Integrations',
                'published_at'     => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
                'content'          => <<<'HTML'
<p>في السنة الأولى من إدارة عملي، بدأ كل صباح بنفس الطريقة. كنت أفتح واتساب، أتمرير للخلف عبر محادثات اليوم السابق، أنسخ كل اسم ورقم هاتف واستفسار عن منتج إلى جوجل شيتس، وأحاول معرفة من أتمّ طلباً فعلاً مقابل من سأل عن السعر ثم اختفى. كان هذا يستغرق 45 دقيقة كل صباح. فعلته لأنني كنت مضطراً — بدون تلك الجداول، لم يكن لديّ سجل بمن يجب المتابعة معه، ولا بيانات للمراجعة الشهرية، ولا إدخال لاستهداف إعلاناتي. الشهر الماضي أدركت أنني لم أقم بتلك العملية اليدوية منذ أربعة أشهر. الذكاء الاصطناعي كان يفعلها تلقائياً طوال الوقت.</p>

<h2>ما يفعله تبويب Connectors</h2>

<p>في إعدادات AI Config في OT1-Pro، يوجد تبويب Connectors. هنا تُحدّد ما يحدث عندما يلتقط الذكاء الاصطناعي عميلاً محتملاً أو يُغلق صفقة. يربط الموصّل وكيل الذكاء الاصطناعي بوجهة خارجية — حالياً جوجل شيتس، مع وجهات أخرى قادمة — ويُحدّد البيانات التي تُرسَل عند تفعيل شرط.</p>

<p>يتوفّر شرطان:</p>

<ul>
<li><strong>التقاط عميل محتمل:</strong> عندما يجمع الذكاء الاصطناعي معلومات مفتاحية من عميل — الاسم، الهاتف، ما يهتم به — يُرسل صفاً لجوجل شيتس. يتفعّل هذا عندما يُقرّر الذكاء الاصطناعي أن لديه معلومات تأهيل كافية لتسجيل جهة الاتصال كعميل محتمل.</li>
<li><strong>إغلاق صفقة:</strong> عندما يُقرّر الذكاء الاصطناعي أن عميلاً التزم بشراء (أكّد طلباً، وافق على سعر، قال "تمام آخذه")، يُرسل صفاً لجوجل شيتس مع تفاصيل الصفقة.</li>
</ul>

<p>كل صف يُرسَل للجدول يتضمن: اسم العميل، رقم الهاتف، المنصة (واتساب/انستاجرام/ماسنجر)، المنتج(ات) التي طلبها أو استفسر عنها، ملخص المحادثة، والتوقيت. يتعبّأ الجدول تلقائياً، في الوقت الفعلي، بينما يتعامل الذكاء الاصطناعي مع المحادثات — لا إنسان في الحلقة.</p>

<h2>الإعداد: الخطوات الفعلية</h2>

<ol>
<li><strong>أنشئ جوجل شيتس واحصل على رابطه.</strong> الجدول يحتاج فقط أن يكون موجوداً — OT1-Pro ينشئ رؤوس الأعمدة تلقائياً عند أول مزامنة.</li>
<li><strong>اذهب لـAI Config ← تبويب Connectors في OT1-Pro.</strong></li>
<li><strong>اضغط "إضافة موصّل" واختر Google Sheets.</strong></li>
<li><strong>الصق رابط جدولك وأعطِ الصلاحية.</strong> سيطلب OT1-Pro إذن الوصول لـGoogle Drive لكتابة الجدول.</li>
<li><strong>هيّئ شروطك.</strong> فعّل "إرسال عند التقاط عميل" و/أو "إرسال عند إغلاق صفقة". اختر الحقول التي تريد تضمينها في كل صف.</li>
<li><strong>احفظ واختبر.</strong> يمكنك إطلاق حدث اختباري للتحقق من ظهور صف في الجدول قبل البدء الفعلي.</li>
</ol>

<p>الوقت الإجمالي للإعداد: حوالي 8 دقائق في المرة الأولى. بعد ذلك، يعمل إلى الأبد بدون أي صيانة ما لم تُغيّر رابط الجدول.</p>

<h2>شكل الجدول في الممارسة</h2>

<p>جدولي يحتوي 7 أعمدة يكتبها OT1-Pro تلقائياً:</p>

<table>
<thead>
<tr><th>العمود</th><th>مثال على القيمة</th><th>المصدر</th></tr>
</thead>
<tbody>
<tr><td>التوقيت</td><td>2026-11-04 14:32</td><td>تلقائي</td></tr>
<tr><td>الاسم</td><td>سارة محمد</td><td>من المحادثة</td></tr>
<tr><td>الهاتف</td><td>+20 100 XXXX XXX</td><td>من المحادثة</td></tr>
<tr><td>المنصة</td><td>واتساب</td><td>تلقائي</td></tr>
<tr><td>المنتج / الاهتمام</td><td>جاكيت شتوي، مقاس M، لون بورجندي</td><td>مستخرَج من الذكاء الاصطناعي</td></tr>
<tr><td>الحالة</td><td>صفقة مُغلقة</td><td>نوع الشرط</td></tr>
<tr><td>الملخص</td><td>العميلة أكّدت الطلب بـ$89، دفع تحويل بنكي، شحن للقاهرة</td><td>الذكاء الاصطناعي ينشئه</td></tr>
</tbody>
</table>

<p>كل صباح أفتح هذا الجدول ولديّ سجل كامل ودقيق بكل عميل محتمل وبيع من اليوم السابق. لا نسخ ولصق. لا إدخالات فائتة بسبب التمرير فوق محادثة. لا تنسيق متضارب من الإدخال اليدوي. الجدول هو المصدر الوحيد للحقيقة في خط مبيعاتي.</p>

<h2>ما أستخدم الجدول من أجله</h2>

<ol>
<li><strong>استهداف المتابعة.</strong> كل صباح اثنين أُصفّي الجدول على حالة "عميل محتمل تم التقاطه" من الأسبوع السابق التي لم تتحوّل لـ"صفقة مُغلقة". هؤلاء مكالمات متابعتي لليوم — أشخاص أبدوا اهتماماً لكن لم يتخذوا قراراً. أتعامل معهم شخصياً، عادةً 8–12 شخصاً. معدل تحويل متابعتي عليهم حوالي 22%.</li>
<li><strong>جمهور إعادة الاستهداف للإعلانات.</strong> مرة شهرياً أُصدّر أرقام الهواتف من صفوف "صفقة مُغلقة" كـCSV وأرفعه لـMeta كجمهور مخصص للاستهداف المشابه. مشترو اليوم هم أفضل إشارة على من سيشتري غداً. هذا أدق جمهور يمكنني بناؤه.</li>
<li><strong>مراجعة الإيرادات الشهرية.</strong> أعدّ صفوف "صفقة مُغلقة"، أضربها في متوسط قيمة الطلب، وأقارن بالشهر السابق. هذا مقياسي الأساسي للإيرادات.</li>
<li><strong>تحسينات تدريب الذكاء الاصطناعي.</strong> كل أسبوع أتصفّح عمود "الملخص" لمحادثات التقط فيها الذكاء الاصطناعي عميلاً لكن الصفقة لم تُغلق. هذه الملخصات تُخبرني بالاعتراضات والفجوات التي تسبّبت في التوقف. أستخدم تلك المعلومة لتحديث موجّهات تدريب الذكاء الاصطناعي لأن نفس التوقف يحدث بشكل أقل الشهر القادم.</li>
</ol>

<h2>حساب الوقت</h2>

<p>قبل Connector: 45 دقيقة يومياً لنسخ العملاء من واتساب للجداول × 5 أيام × 4 أسابيع = 15 ساعة شهرياً على الإدخال اليدوي للبيانات. بتكلفة فرصة $30/ساعة: $450/شهر كنت أُنفقها في النسخ واللصق.</p>

<p>بعد Connector: صفر دقيقة على إدخال البيانات. الجدول دائماً حديث، دقيق، كامل. عادت الـ15 ساعة للعمل الذي يُحرّك العمل فعلاً. ميزة Connector مشمولة في خطط OT1-Pro من $29/شهر — لا تكلفة إضافية، لا Zapier مطلوب، لا كود.</p>

<h2>مقارنة مع حلول Zapier + واتساب</h2>

<p>بعض الأعمال تبني هذا يدوياً بـZapier تربط فيه واتساب Business API بجوجل شيتس. مشاكل هذا النهج:</p>

<ul>
<li>Zapier webhooks تلتقط بيانات الرسائل الخام، لا معلومات العملاء المُنظَّمة. تحصل على تفريغ نص الرسالة، لا "اسم العميل: سارة، المنتج: جاكيت شتوي، الحالة: صفقة مُغلقة".</li>
<li>الطبقة المجانية في Zapier تُقيّد تشغيلات المهام. عند 100+ محادثة يومياً ستصل للحد الأقصى. الطبقة المدفوعة $19.99/شهر لـ750 مهمة أو $49/شهر لـ2,000 مهمة — تدفع مرتين لوظيفة مدمجة في OT1-Pro.</li>
<li>صيانة تدفق Zapier عند تغيير Meta لـAPI (يحدث 2–3 مرات سنوياً) تتطلب مطوراً أو جهداً تقنياً كبيراً.</li>
</ul>

<p>Connector في OT1-Pro أصيل — يفهم نموذج محادثة الذكاء الاصطناعي ويُرسل بيانات منظّمة ونظيفة بدون أي وسيط. اطّلع على <a href="https://ot1-pro.com/pricing">الأسعار هنا</a> أو <a href="https://ot1-pro.com/vs/wati">قارننا مع WATI</a>. <a href="https://ot1-pro.com/register">ابدأ مجاناً ←</a></p>

{{CTA}}
HTML,
            ],
        ];
    }
}
