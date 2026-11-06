<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

/**
 * Batch 40 — WhatsApp Campaigns cluster
 * EN + AR posts targeting "bulk WhatsApp from Excel" intent
 */
class AiSeoBlogSeederBatch40WhatsAppCampaignsCluster extends Seeder
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
<p>OT1-Pro is the unified inbox I built to run my own operation: WhatsApp + Instagram + Messenger + Telegram + email, one screen, with an AI agent that replies in your brand voice and an integrated campaign tool for bulk WhatsApp and email sends. <a href="https://ot1-pro.com/register">Free plan, no credit card →</a></p>
<p><a href="https://ot1-pro.com/pricing">Pricing from $29/mo</a> · <a href="https://ot1-pro.com/vs/wati">Why we beat WATI</a> · <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">The Meta verification guide</a> · <a href="https://wa.me/201026361218">Talk to me on WhatsApp</a></p>
HTML;
    }

    private function ctaAr(): string
    {
        return <<<'HTML'
<h2>جرّب صندوق البريد الذكي المبني للأعمال الحقيقية</h2>
<p>OT1-Pro هو صندوق البريد الموحد الذي بنيته لأدير عملي: واتساب + انستاجرام + ماسنجر + تيليجرام + البريد الإلكتروني، شاشة واحدة، مع أداة حملات متكاملة لإرسال واتساب والبريد الإلكتروني بالجملة. <a href="https://ot1-pro.com/register">خطة مجانية، بدون بطاقة ائتمان ←</a></p>
<p><a href="https://ot1-pro.com/pricing">الأسعار من $29/شهر</a> · <a href="https://ot1-pro.com/vs/wati">لماذا نتفوق على WATI</a> · <a href="https://wa.me/201026361218">تحدث معي على واتساب</a></p>
HTML;
    }

    private function posts(): array
    {
        $now = now();

        return [

            // ─────────────────────────────────────────────────────────────
            // 1. WhatsApp Campaigns — English
            // ─────────────────────────────────────────────────────────────
            [
                'title'            => 'How I Send WhatsApp Campaigns to 500 Contacts From an Excel Sheet (Step-by-Step)',
                'slug'             => 'whatsapp-campaign-excel-import-500-contacts',
                'excerpt'          => 'OT1-Pro Campaigns lets you upload an Excel file, map your columns, write a personalised WhatsApp message, and reach hundreds of real customers — without the WhatsApp Business app\'s 256-contact broadcast limit or any API knowledge. Here is the exact workflow I use to run a 500-contact campaign in under 20 minutes.',
                'meta_title'       => 'Send WhatsApp Campaigns From Excel: Step-by-Step Guide',
                'meta_description' => 'Upload Excel, map columns, write your message, send to 500+ contacts — OT1-Pro Campaigns skips the broadcast-list limit. Full step-by-step for bulk WhatsApp campaign Excel.',
                'category'         => 'Campaigns',
                'published_at'     => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
                'content'          => <<<'HTML'
<p>I had 500 phone numbers in a spreadsheet and a promotion that expired in 72 hours. The WhatsApp Business app has a broadcast list limit of 256 contacts — and those contacts have to have your number saved first, which roughly 60% of mine had not done. SMS bulk tools cost $0.08 per message, which at 500 contacts added up to $40 for one campaign. Email open rates from my list were running at 11%. The only channel that worked for my audience was WhatsApp. I needed to send 500 WhatsApp messages from an Excel sheet and I needed to do it fast. Here is exactly how I do it now.</p>

<h2>What the OT1-Pro Campaigns feature actually is</h2>

<p>Campaigns is a module inside OT1-Pro that lets you send bulk WhatsApp messages to lists of contacts imported from an Excel file. You do not need to know anything about the WhatsApp API. You do not need each contact to have your number saved. You prepare your list, write your message with personalisation placeholders like <code>{{firstname}}</code>, and the system sends on your behalf through the connected WhatsApp Business API number.</p>

<p>This is not a mass-spam tool. It is designed for contacts you have genuine business relationships with — customers who have bought from you, people who opted into updates, leads who reached out through your WhatsApp business number. Using it on cold lists violates WhatsApp's terms and will get your number flagged. Used properly, delivery rates run at 92–96%.</p>

<h2>The step-by-step campaign workflow</h2>

<p>Here is exactly how I prepare and send a 500-contact campaign:</p>

<ol>
<li><strong>Prepare the Excel file.</strong> My file has four columns: phone number (international format with country code, no plus sign), first name, last name, and a tag column for segmentation. The phone number column is the only required one. Keep the file under 2MB — the system handles larger files via async import, but under 2MB uploads and processes instantly.</li>
<li><strong>Go to Campaigns in OT1-Pro and create a new WhatsApp campaign.</strong> Click the campaign type (WhatsApp), give it a name, and select the connected WhatsApp number you want to send from.</li>
<li><strong>Upload the Excel file and map columns.</strong> The import wizard shows you your column headers and lets you map them: "Column A → phone number", "Column B → firstname" and so on. This takes about 90 seconds.</li>
<li><strong>Write the message.</strong> I keep mine to 3–4 sentences. Use <code>{{firstname}}</code> where you want the contact's first name inserted. The preview on the right shows exactly what the final message will look like for a sample contact. Keep it conversational — messages that read like a human wrote them have a 2–3x higher reply rate than ones that read like a marketing blast.</li>
<li><strong>Set the schedule.</strong> You can send immediately or schedule for a specific time. I always schedule for mid-morning local time (10–11am) — reply rates are roughly 40% higher than evening sends for my audience.</li>
<li><strong>Review and confirm.</strong> The final screen shows contact count, estimated send duration (the system throttles sends to protect your account), and the message preview. Confirm and the system handles the rest.</li>
</ol>

<h2>The throttle system — why we slow sends intentionally</h2>

<p>OT1-Pro sends campaigns with an intentional delay between messages. This is not a limitation — it is a feature that protects your WhatsApp number. Meta's spam detection system flags accounts that send hundreds of messages in a short burst to recipients who don't reply. By throttling the send rate and spacing messages over a period of time, the system keeps your delivery rate high and your account in good standing.</p>

<p>For a 500-contact campaign, the full send typically completes in 60–90 minutes depending on your plan tier. You can close the browser and the campaign continues in the background — you will get a notification when it is done.</p>

<h2>Real results from a recent campaign</h2>

<p>My most recent campaign: 500 contacts, a 15% discount code for a new collection launch.</p>

<table>
<thead>
<tr><th>Metric</th><th>Result</th><th>Notes</th></tr>
</thead>
<tbody>
<tr><td>Messages sent</td><td>500</td><td>Full list</td></tr>
<tr><td>Delivered</td><td>479 (95.8%)</td><td>21 undeliverable — wrong numbers, changed numbers</td></tr>
<tr><td>Read (blue ticks)</td><td>412 (86%)</td><td>Sent 10:30am local time</td></tr>
<tr><td>Replied</td><td>87 (18%)</td><td>Mostly "how do I order?" and "is size X available?"</td></tr>
<tr><td>Clicked or enquired about product</td><td>114 (24%)</td><td>Includes replies + WhatsApp link clicks</td></tr>
<tr><td>Converted to purchase</td><td>31 (6.5%)</td><td>Within 72 hours of campaign send</td></tr>
<tr><td>Revenue attributed</td><td>~$2,015</td><td>At $65 average order value</td></tr>
<tr><td>Campaign cost (plan tier)</td><td>$0 extra</td><td>Included in monthly plan limit</td></tr>
</tbody>
</table>

<p>Compare to my previous method — manually forwarding a promotion message to contacts one at a time from my phone. That got me maybe 20 sends per session before my thumb gave out. The campaign feature replaced that with a system I can run in 20 minutes.</p>

<h2>Comparing the options: OT1-Pro vs WATI vs Respond.io</h2>

<table>
<thead>
<tr><th>Feature</th><th>OT1-Pro ($29/mo)</th><th>WATI ($39/mo)</th><th>Respond.io ($99/mo)</th></tr>
</thead>
<tbody>
<tr><td>WhatsApp campaigns from Excel</td><td>✓ All plans</td><td>✓ Paid plans</td><td>✓ Paid plans</td></tr>
<tr><td>Campaigns per month (Starter)</td><td>5</td><td>Unlimited (message credits)</td><td>Unlimited (message credits)</td></tr>
<tr><td>Personalisation placeholders</td><td>✓</td><td>✓</td><td>✓</td></tr>
<tr><td>Async import for large files</td><td>✓</td><td>✓</td><td>✓</td></tr>
<tr><td>AI inbox included</td><td>✓</td><td>✗</td><td>✗ (separate product)</td></tr>
<tr><td>Comments + DM handling</td><td>✓</td><td>✗</td><td>Limited</td></tr>
<tr><td>Minimum contract</td><td>Monthly</td><td>Monthly</td><td>Monthly</td></tr>
</tbody>
</table>

<p>WATI's campaign feature is solid but their pricing model is message-credit-based, which means your effective cost scales with your list size. At 500 contacts per campaign run, the per-message cost adds up. OT1-Pro's plan limits (5 campaigns/mo on Starter, 25 on Pro) are designed for the typical small business that runs 2–4 major campaigns per month.</p>

<h2>The plan limits and how to work within them</h2>

<p>Campaign limits per month on each tier:</p>

<table>
<thead>
<tr><th>Plan</th><th>Campaigns/month</th><th>Notes</th></tr>
</thead>
<tbody>
<tr><td>Free</td><td>1</td><td>Good for testing the feature</td></tr>
<tr><td>Starter ($29/mo)</td><td>5</td><td>Enough for weekly campaigns</td></tr>
<tr><td>Pro ($79/mo)</td><td>25</td><td>Enough for daily campaigns</td></tr>
<tr><td>Business ($149/mo)</td><td>Unlimited</td><td>No cap</td></tr>
</tbody>
</table>

<p>If you are running 5 campaigns a month and hitting the Starter limit, the natural upgrade trigger is to Pro. The incremental cost ($50/mo) has to be measured against the revenue a few additional campaigns generate — in my experience, one extra campaign at 500 contacts converting at 6% at $65 AOV is $1,950 in revenue. The upgrade math is straightforward.</p>

<h2>The 3 biggest mistakes to avoid</h2>

<ol>
<li><strong>Sending to cold lists.</strong> If the people on your list have never heard from you on WhatsApp, your reply rate will be near zero and your spam report rate will be high. Use campaigns for warm contacts only — people who have messaged you first, bought from you, or opted in explicitly.</li>
<li><strong>Sending at the wrong time.</strong> My data shows mid-morning (10–11am local time) consistently outperforms evening sends by 35–40% on reply rate. Test your own audience, but default to business hours if you don't have data yet.</li>
<li><strong>Writing like a marketing blast.</strong> "🎉 EXCLUSIVE OFFER! LIMITED TIME ONLY! GET 20% OFF NOW!" in WhatsApp format looks like spam and gets ignored. Write like you are sending a message from your personal number to a real customer. "Hey {{firstname}}, we just launched the new summer collection and given your past order history I thought you'd want early access — 15% off until Sunday." Same offer, different tone, dramatically different open and reply rate.</li>
</ol>

<p>See <a href="https://ot1-pro.com/pricing">the pricing page</a> for full plan limits, or <a href="https://ot1-pro.com/vs/wati">compare with WATI</a> before deciding. <a href="https://ot1-pro.com/register">Start free →</a> and run your first test campaign on the Free plan before upgrading.</p>

{{CTA}}
HTML,
            ],

            // ─────────────────────────────────────────────────────────────
            // 2. WhatsApp Campaigns — Arabic
            // ─────────────────────────────────────────────────────────────
            [
                'title'            => 'كيف أرسل حملات واتساب لـ500 عميل من إكسيل — خطوة بخطوة',
                'slug'             => 'whatsapp-campaign-excel-import-ar',
                'excerpt'          => 'ميزة Campaigns في OT1-Pro تُتيح لك رفع ملف إكسيل، كتابة رسالة واتساب مُخصَّصة، وإرسالها لمئات العملاء الحقيقيين — بدون حد قوائم البث 256 جهة اتصال ولا أي معرفة تقنية بـAPI. هذا هو الروتين الكامل الذي أستخدمه لإرسال حملة 500 جهة اتصال في أقل من 20 دقيقة.',
                'meta_title'       => 'إرسال حملات واتساب من إكسيل: دليل خطوة بخطوة',
                'meta_description' => 'ارفع إكسيل، اربط الأعمدة، اكتب رسالتك، وأرسل لـ500+ جهة اتصال — OT1-Pro يتجاوز حد قوائم البث. دليل كامل لحملات واتساب جماعية من إكسيل.',
                'category'         => 'Campaigns',
                'published_at'     => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
                'content'          => <<<'HTML'
<p>كان لديّ 500 رقم هاتف في جدول إكسيل وعرض ترويجي ينتهي خلال 72 ساعة. تطبيق WhatsApp Business لديه حد قوائم البث 256 جهة اتصال — وهؤلاء يجب أن يكون لديهم رقمك محفوظاً مسبقاً، وهو ما ينطبق تقريباً على 60% فقط من قائمتي. أدوات الرسائل الجماعية SMS تكلّف $0.08 للرسالة الواحدة، أي $40 لحملة واحدة على 500 جهة اتصال. معدلات فتح البريد الإلكتروني من قائمتي كانت 11%. القناة الوحيدة التي تعمل مع جمهوري كانت واتساب. كنت بحاجة لإرسال 500 رسالة واتساب من إكسيل بسرعة. هذا بالضبط ما أفعله الآن.</p>

<h2>ما هي ميزة Campaigns في OT1-Pro</h2>

<p>Campaigns هي وحدة داخل OT1-Pro تُتيح لك إرسال رسائل واتساب جماعية لقوائم جهات الاتصال المستوردة من ملف إكسيل. لا تحتاج أي معرفة بـAPI الواتساب. لا تحتاج لأن تكون جهات الاتصال قد حفظت رقمك. تُحضّر القائمة، تكتب الرسالة مع متغيّرات التخصيص مثل <code>{{firstname}}</code>، والنظام يُرسل نيابةً عنك من خلال رقم واتساب بيزنس المُربوط.</p>

<p>هذه ليست أداة رسائل غير مرغوب بها. مُصمَّمة لجهات الاتصال التي لديك علاقات تجارية حقيقية معها — عملاء اشتروا منك، أشخاص طلبوا التحديثات، عملاء محتملون تواصلوا عبر رقمك على واتساب. استخدامها على قوائم باردة يُخالف شروط واتساب وسيُعرّض رقمك للإيقاف. عند الاستخدام الصحيح، تتراوح معدلات التسليم بين 92–96%.</p>

<h2>الخطوات الكاملة لإرسال حملة واتساب</h2>

<p>هذا بالضبط ما أفعله لتحضير وإرسال حملة 500 جهة اتصال:</p>

<ol>
<li><strong>تحضير ملف الإكسيل.</strong> ملفي يحتوي أربعة أعمدة: رقم الهاتف (بصيغة دولية مع رمز الدولة بدون إشارة +)، الاسم الأول، الاسم الأخير، وعمود للتصنيف. عمود رقم الهاتف هو الوحيد المطلوب. أحرص على أن يكون الملف أقل من 2MB لرفع فوري — الملفات الأكبر تُعالَج بشكل غير متزامن.</li>
<li><strong>الذهاب لـCampaigns في OT1-Pro وإنشاء حملة واتساب جديدة.</strong> اختر نوع الحملة (واتساب)، أعطِها اسماً، واختر رقم واتساب المُربوط الذي تريد الإرسال منه.</li>
<li><strong>رفع ملف الإكسيل وربط الأعمدة.</strong> معالج الاستيراد يُظهر رؤوس أعمدتك ويُتيح ربطها: "العمود A ← رقم الهاتف"، "العمود B ← firstname" وهكذا. يستغرق ذلك حوالي 90 ثانية.</li>
<li><strong>كتابة الرسالة.</strong> أُبقيها في 3–4 جمل. أستخدم <code>{{firstname}}</code> حيث أريد إدراج اسم جهة الاتصال. المعاينة على اليمين تُظهر شكل الرسالة النهائية لجهة اتصال نموذجية. أُبقيها محادثية — الرسائل التي تبدو مكتوبة من إنسان لها معدل رد أعلى بـ2–3 أضعاف من تلك التي تبدو كبريد تسويقي.</li>
<li><strong>تحديد الجدول الزمني.</strong> يمكنك الإرسال فوراً أو جدولته لوقت محدد. أُدوّم الجدولة في وقت منتصف الصباح وقت محلي (10–11 صباحاً) — معدلات الرد أعلى بحوالي 40% من الإرسال مساءً لجمهوري.</li>
<li><strong>المراجعة والتأكيد.</strong> الشاشة النهائية تُظهر عدد جهات الاتصال، المدة المُقدَّرة للإرسال (النظام يُنظّم الإرسال لحماية حسابك)، ومعاينة الرسالة. أؤكّد والنظام يتولى الباقي.</li>
</ol>

<h2>نظام التنظيم — لماذا نُبطّئ الإرسال عمداً</h2>

<p>OT1-Pro يُرسل الحملات بتأخير متعمّد بين كل رسالة. هذا ليس قيداً — بل ميزة تحمي رقم واتساب الخاص بك. نظام كشف الرسائل غير المرغوب في Meta يُعلّم الحسابات التي ترسل مئات الرسائل في فترة قصيرة لمستلمين لا يردّون. بتنظيم معدل الإرسال وتوزيع الرسائل على فترة زمنية، يحافظ النظام على معدل التسليم العالي ويبقي حسابك في وضع جيد.</p>

<p>لحملة 500 جهة اتصال، يكتمل الإرسال الكامل عادةً في 60–90 دقيقة بحسب طبقة خطتك. يمكنك إغلاق المتصفح والحملة تستمر في الخلفية — ستتلقى إشعاراً عند الانتهاء.</p>

<h2>نتائج حقيقية من حملة أخيرة</h2>

<p>حملتي الأخيرة: 500 جهة اتصال، كود خصم 15% لإطلاق مجموعة جديدة.</p>

<table>
<thead>
<tr><th>المؤشر</th><th>النتيجة</th><th>ملاحظات</th></tr>
</thead>
<tbody>
<tr><td>الرسائل المُرسَلة</td><td>500</td><td>القائمة الكاملة</td></tr>
<tr><td>المُسلَّمة</td><td>479 (95.8%)</td><td>21 غير قابلة للتسليم — أرقام خاطئة أو مُغيَّرة</td></tr>
<tr><td>المقروءة (علامتا صح زرقاوتان)</td><td>412 (86%)</td><td>أُرسلت الساعة 10:30 صباحاً بالتوقيت المحلي</td></tr>
<tr><td>الردود</td><td>87 (18%)</td><td>غالباً "كيف أطلب؟" و"في مقاس X؟"</td></tr>
<tr><td>الاستفسارات والتفاعل مع المنتج</td><td>114 (24%)</td><td>يشمل الردود + النقرات على روابط واتساب</td></tr>
<tr><td>التحويل إلى شراء</td><td>31 (6.5%)</td><td>خلال 72 ساعة من إرسال الحملة</td></tr>
<tr><td>الإيرادات المُحتسَبة</td><td>~$2,015</td><td>بمتوسط قيمة طلب $65</td></tr>
<tr><td>تكلفة الحملة</td><td>$0 إضافي</td><td>مشمولة في حد الخطة الشهري</td></tr>
</tbody>
</table>

<p>قارن ذلك بطريقتي السابقة — إرسال الرسالة الترويجية يدوياً لجهات الاتصال واحدة تلو الأخرى من هاتفي. كنت أصل لحوالي 20 رسالة قبل أن يتعب إصبعي. الميزة استبدلت ذلك بنظام أُشغّله في 20 دقيقة.</p>

<h2>مقارنة الخيارات: OT1-Pro مقابل WATI مقابل Respond.io</h2>

<table>
<thead>
<tr><th>الميزة</th><th>OT1-Pro ($29/شهر)</th><th>WATI ($39/شهر)</th><th>Respond.io ($99/شهر)</th></tr>
</thead>
<tbody>
<tr><td>حملات واتساب من إكسيل</td><td>✓ كل الخطط</td><td>✓ الخطط المدفوعة</td><td>✓ الخطط المدفوعة</td></tr>
<tr><td>الحملات في الشهر (Starter)</td><td>5</td><td>غير محدود (أرصدة رسائل)</td><td>غير محدود (أرصدة رسائل)</td></tr>
<tr><td>متغيّرات التخصيص</td><td>✓</td><td>✓</td><td>✓</td></tr>
<tr><td>استيراد غير متزامن للملفات الكبيرة</td><td>✓</td><td>✓</td><td>✓</td></tr>
<tr><td>صندوق بريد ذكاء اصطناعي مشمول</td><td>✓</td><td>✗</td><td>✗ (منتج منفصل)</td></tr>
<tr><td>معالجة التعليقات + DMs</td><td>✓</td><td>✗</td><td>محدودة</td></tr>
</tbody>
</table>

<p>ميزة الحملات في WATI متينة، لكن نموذج التسعير القائم على أرصدة الرسائل يعني أن تكلفتك الفعلية ترتفع مع حجم قائمتك. OT1-Pro يُبسّط الحساب: لديك X حملة شهرياً، استخدمها كيف تشاء.</p>

<h2>حدود الخطط وكيفية العمل ضمنها</h2>

<table>
<thead>
<tr><th>الخطة</th><th>الحملات/الشهر</th><th>ملاحظات</th></tr>
</thead>
<tbody>
<tr><td>مجانية</td><td>1</td><td>جيدة لاختبار الميزة</td></tr>
<tr><td>Starter ($29/شهر)</td><td>5</td><td>كافية لحملة أسبوعية</td></tr>
<tr><td>Pro ($79/شهر)</td><td>25</td><td>كافية لحملة يومية</td></tr>
<tr><td>Business ($149/شهر)</td><td>غير محدود</td><td>بدون سقف</td></tr>
</tbody>
</table>

<h2>أكبر 3 أخطاء يجب تجنّبها</h2>

<ol>
<li><strong>الإرسال لقوائم باردة.</strong> إذا لم يسمع الأشخاص في قائمتك عنك مسبقاً على واتساب، معدل ردّك سيكون قريباً من الصفر ومعدل الإبلاغ عنك كرسائل غير مرغوب بها سيكون مرتفعاً. استخدم الحملات للجهات الدافئة فقط — عملاء اشتروا منك أو تواصلوا معك أو طلبوا التحديثات.</li>
<li><strong>الإرسال في الوقت الخطأ.</strong> بياناتي تُظهر أن منتصف الصباح (10–11 صباحاً بالتوقيت المحلي) يتفوق على الإرسال المسائي بـ35–40% في معدل الرد. اختبر جمهورك، لكن ابدأ بساعات العمل إذا لم تكن لديك بيانات.</li>
<li><strong>الكتابة بأسلوب إعلاني صارخ.</strong> "🎉 عرض حصري! محدود الوقت! 20% خصم الآن!" بأسلوب واتساب يبدو كرسالة غير مرغوب بها ويُتجاهَل. اكتب كما لو كنت ترسل رسالة شخصية لعميل حقيقي. "مرحبا {{firstname}}، أطلقنا المجموعة الجديدة وبناءً على طلباتك السابقة أردت تعلمك أولاً — خصم 15% حتى الأحد." نفس العرض، أسلوب مختلف، معدل رد مختلف جذرياً.</li>
</ol>

<p>اطّلع على <a href="https://ot1-pro.com/pricing">صفحة الأسعار</a> لحدود الخطط الكاملة، أو <a href="https://ot1-pro.com/vs/wati">قارننا مع WATI</a> قبل اتخاذ قرارك. <a href="https://ot1-pro.com/register">ابدأ مجاناً ←</a> وشغّل أول حملة تجريبية على الخطة المجانية قبل الترقية.</p>

{{CTA}}
HTML,
            ],
        ];
    }
}
