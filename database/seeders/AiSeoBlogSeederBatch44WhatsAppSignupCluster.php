<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

/**
 * Batch 44 — WhatsApp Embedded Signup cluster
 * EN + AR posts targeting "connect WhatsApp Business API easy" intent
 */
class AiSeoBlogSeederBatch44WhatsAppSignupCluster extends Seeder
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
<p>OT1-Pro connects your WhatsApp Business API, Instagram, Messenger, Telegram, and email in one screen — setup takes minutes, not days. Free plan available, no credit card required. <a href="https://ot1-pro.com/register">Start free →</a></p>
<p><a href="https://ot1-pro.com/pricing">Pricing from $29/mo</a> · <a href="https://ot1-pro.com/vs/wati">Why we beat WATI</a> · <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">The Meta verification guide</a> · <a href="https://wa.me/201026361218">Talk to me on WhatsApp</a></p>
HTML;
    }

    private function ctaAr(): string
    {
        return <<<'HTML'
<h2>جرّب صندوق البريد الذكي المبني للأعمال الحقيقية</h2>
<p>OT1-Pro يربط واتساب Business API وانستاجرام وماسنجر وتيليجرام والبريد الإلكتروني في شاشة واحدة — الإعداد يستغرق دقائق لا أياماً. خطة مجانية متاحة، بدون بطاقة ائتمان. <a href="https://ot1-pro.com/register">ابدأ مجاناً ←</a></p>
<p><a href="https://ot1-pro.com/pricing">الأسعار من $29/شهر</a> · <a href="https://ot1-pro.com/vs/wati">لماذا نتفوق على WATI</a> · <a href="https://wa.me/201026361218">تحدث معي على واتساب</a></p>
HTML;
    }

    private function posts(): array
    {
        $now = now();

        return [

            // ─────────────────────────────────────────────────────────────
            // 1. WhatsApp Embedded Signup — English
            // ─────────────────────────────────────────────────────────────
            [
                'title'            => 'Connect WhatsApp Business API in 3 Minutes — No IT Department Needed',
                'slug'             => 'whatsapp-business-api-embedded-signup-no-it',
                'excerpt'          => 'The old way to connect WhatsApp Business API involved a Meta developer account, business verification wait, webhook setup, and usually a developer. OT1-Pro\'s Embedded Signup flow cuts this to a 3-minute in-browser process: pick your phone number, verify with a code, done. No technical skills required.',
                'meta_title'       => 'Connect WhatsApp Business API in 3 Minutes: No IT Needed',
                'meta_description' => 'OT1-Pro Embedded Signup connects your WhatsApp Business API without developer accounts, webhook setup, or IT help. Connect WhatsApp Business API in 3 minutes.',
                'category'         => 'Platform features',
                'published_at'     => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
                'content'          => <<<'HTML'
<p>I tried to connect a WhatsApp Business API number for the first time in 2023. The process involved: creating a Meta developer account, applying for a business portfolio verification (which took 11 days), creating an app in the developer console, setting up a webhook endpoint on a server, configuring the webhook URL in the Meta dashboard, generating a permanent access token, and then wondering why no messages were coming through for three hours before I found a forum post explaining I had also needed to subscribe the app to the correct webhook fields. Total time: about two weeks, one developer, and two nights of my own time debugging. The WhatsApp Business API is not designed for small business owners. OT1-Pro's Embedded Signup is.</p>

<h2>What Embedded Signup actually is</h2>

<p>Embedded Signup is Meta's official flow for connecting a WhatsApp Business number to a third-party platform without going through the developer console. OT1-Pro surfaces this flow directly inside the platform — when you go to Connections and click "Connect WhatsApp," a pop-up window handles the entire process in your browser. No developer account required. No webhook configuration. No separate Meta app.</p>

<p>The flow has three steps:</p>

<ol>
<li><strong>Log in to your Facebook account</strong> (the one associated with your Meta Business Portfolio, or create one if you don't have it).</li>
<li><strong>Select or add the phone number</strong> you want to use as your WhatsApp Business number. This can be your existing WhatsApp Business number or a new one.</li>
<li><strong>Verify the number</strong> with a code sent by SMS or voice call.</li>
</ol>

<p>That is the complete process. The pop-up closes and your WhatsApp number appears as connected in OT1-Pro, ready to receive messages. The whole thing takes 3–5 minutes, including waiting for the verification code.</p>

<h2>The old way vs. the Embedded Signup way</h2>

<table>
<thead>
<tr><th>Step</th><th>Old developer console method</th><th>OT1-Pro Embedded Signup</th></tr>
</thead>
<tbody>
<tr><td>Meta developer account</td><td>Required — setup 30 min</td><td>Not required</td></tr>
<tr><td>Business portfolio verification</td><td>Required — wait 7–14 days</td><td>Not required (OT1-Pro's portfolio is verified)</td></tr>
<tr><td>App Review</td><td>Required for some permissions</td><td>Not required (covered by OT1-Pro)</td></tr>
<tr><td>Webhook endpoint setup</td><td>Required — needs a server + code</td><td>Not required (OT1-Pro handles this)</td></tr>
<tr><td>Access token generation</td><td>Manual — permanent token setup</td><td>Automatic</td></tr>
<tr><td>Test that messages arrive</td><td>Requires webhook debugging tools</td><td>First message arrives in OT1-Pro inbox</td></tr>
<tr><td>Total time for a non-developer</td><td>2–4 weeks (including verification wait)</td><td>3–5 minutes</td></tr>
<tr><td>Can a business owner do it alone?</td><td>Rarely</td><td>Yes, always</td></tr>
</tbody>
</table>

<h2>What about businesses that already have a WhatsApp Business number?</h2>

<p>This is the most common question I get. If you are already using a number on the WhatsApp Business app — the regular small-business app, not the API — you have two options:</p>

<ul>
<li><strong>Migrate your existing number to the API.</strong> This is fully supported in the Embedded Signup flow. You select your existing number and go through the verification step. Important caveat: once a number is migrated to the API, it can no longer be used on the WhatsApp Business app. The two cannot run simultaneously on the same number.</li>
<li><strong>Use a separate number for the API.</strong> Some businesses keep their WhatsApp Business app number for personal customer service and add a dedicated API number (even a cheap SIM, $5–$10/mo) for OT1-Pro's AI handling. This is the approach I took for the first three months before migrating everything to the API number.</li>
</ul>

<p>The migration takes 5 minutes in the Embedded Signup flow. The only disruption is a brief period where the number is being migrated — during this time (usually under a minute), WhatsApp shows "This number is being migrated." After that, everything flows through OT1-Pro.</p>

<h2>What you can do immediately after connecting</h2>

<p>The moment your WhatsApp number is connected through Embedded Signup:</p>

<ul>
<li>All incoming messages appear in your OT1-Pro inbox in real time.</li>
<li>The AI agent starts handling replies immediately — it uses whatever AI training you have configured, or starts with the default sales prompt if you have not customised it yet.</li>
<li>The backfill job starts importing your previous WhatsApp conversations into OT1-Pro's database (see the <a href="https://ot1-pro.com/blog/facebook-chat-history-import-messenger">chat history import article</a> for how this works).</li>
<li>You can send outbound messages, start campaigns, and use all features immediately.</li>
</ul>

<h2>Subscription health check</h2>

<p>After connecting, OT1-Pro runs a subscription health check to verify that the WhatsApp webhook is correctly configured and receiving events. If the health check detects a problem — which can happen if Meta's systems have a temporary issue during setup — the platform shows a warning and an auto-fix button that re-registers the webhook subscription with one click. You do not need to know what a webhook is to fix this.</p>

<h2>Who this is designed for</h2>

<p>Embedded Signup is designed for the owner of a small or medium business who wants to connect their WhatsApp to a professional tool without hiring a developer or waiting two weeks. The technical complexity is entirely abstracted. You click "Connect WhatsApp," follow three browser steps, and your number is live.</p>

<p>If you have a technical team and want to build a custom integration on top of the WhatsApp Business API directly, that path is still available. Embedded Signup is the alternative for everyone who does not want to take that path.</p>

<p>See <a href="https://ot1-pro.com/pricing">pricing details here</a> (WhatsApp connection is available on all plans), or <a href="https://ot1-pro.com/vs/wati">compare with WATI on WhatsApp setup</a>. <a href="https://ot1-pro.com/register">Start free →</a></p>

{{CTA}}
HTML,
            ],

            // ─────────────────────────────────────────────────────────────
            // 2. WhatsApp Embedded Signup — Arabic
            // ─────────────────────────────────────────────────────────────
            [
                'title'            => 'اربط واتساب بيزنس API في 3 دقائق — بدون قسم تقني',
                'slug'             => 'whatsapp-business-api-embedded-signup-ar',
                'excerpt'          => 'الطريقة القديمة لربط واتساب Business API كانت تتطلب حساب مطور Meta، انتظار التحقق من العمل، إعداد webhook، وعادةً مطوّراً. تدفق Embedded Signup في OT1-Pro يُختصر هذا لعملية 3 دقائق في المتصفح: اختر رقمك، تحقق بكود، تم. لا مهارات تقنية مطلوبة.',
                'meta_title'       => 'ربط واتساب Business API في 3 دقائق: بدون تقنيين',
                'meta_description' => 'Embedded Signup في OT1-Pro يربط واتساب Business API بدون حسابات مطورين أو إعداد webhook أو مساعدة تقنية. ربط واتساب Business API في 3 دقائق.',
                'category'         => 'Platform features',
                'published_at'     => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
                'content'          => <<<'HTML'
<p>حاولت ربط رقم واتساب Business API للمرة الأولى في 2023. العملية تضمّنت: إنشاء حساب مطور Meta، التقدم للتحقق من محفظة أعمال (استغرق 11 يوماً)، إنشاء تطبيق في وحدة تحكم المطور، إعداد نقطة نهاية webhook على سيرفر، تكوين رابط webhook في لوحة تحكم Meta، توليد رمز وصول دائم، ثم التساؤل لماذا لا تصل الرسائل لثلاث ساعات قبل أن أجد منشوراً في منتدى يُفسّر أنني كنت بحاجة أيضاً للاشتراك في حقول webhook الصحيحة. إجمالي الوقت: أسبوعان تقريباً، مطوّر واحد، وليلتان من تصحيح الأخطاء. واتساب Business API لم يُصمَّم لأصحاب الأعمال الصغيرة. Embedded Signup في OT1-Pro مُصمَّم لهم.</p>

<h2>ما هو Embedded Signup بالضبط</h2>

<p>Embedded Signup هو التدفق الرسمي لـMeta لربط رقم واتساب بيزنس بمنصة طرف ثالث بدون المرور بوحدة تحكم المطور. OT1-Pro يُقدّم هذا التدفق مباشرةً داخل المنصة — عندما تذهب للاتصالات وتضغط "ربط واتساب"، نافذة منبثقة تتولى العملية بأكملها في متصفحك. لا حساب مطور مطلوب. لا تكوين webhook. لا تطبيق Meta منفصل.</p>

<p>التدفق له ثلاث خطوات:</p>

<ol>
<li><strong>سجّل دخولك لحساب فيسبوك</strong> (المرتبط بمحفظة Meta Business الخاصة بك، أو أنشئ واحدة إذا لم تكن لديك).</li>
<li><strong>اختر أو أضف رقم الهاتف</strong> الذي تريد استخدامه كرقم واتساب بيزنس الخاص بك. يمكن أن يكون رقم واتساب بيزنس الحالي أو رقماً جديداً.</li>
<li><strong>تحقق من الرقم</strong> بكود يُرسَل عبر SMS أو مكالمة صوتية.</li>
</ol>

<p>هذه العملية الكاملة. النافذة المنبثقة تُغلق ويظهر رقم واتساب الخاص بك كمُربوط في OT1-Pro، جاهزاً لاستقبال الرسائل. كل الأمر يستغرق 3–5 دقائق، بما يشمل انتظار كود التحقق.</p>

<h2>الطريقة القديمة مقابل Embedded Signup</h2>

<table>
<thead>
<tr><th>الخطوة</th><th>طريقة وحدة تحكم المطور القديمة</th><th>OT1-Pro Embedded Signup</th></tr>
</thead>
<tbody>
<tr><td>حساب مطور Meta</td><td>مطلوب — إعداد 30 دقيقة</td><td>غير مطلوب</td></tr>
<tr><td>التحقق من محفظة أعمال</td><td>مطلوب — انتظار 7–14 يوماً</td><td>غير مطلوب (محفظة OT1-Pro محقَّقة)</td></tr>
<tr><td>مراجعة التطبيق</td><td>مطلوبة لبعض الصلاحيات</td><td>غير مطلوبة (يتولاها OT1-Pro)</td></tr>
<tr><td>إعداد نقطة نهاية Webhook</td><td>مطلوب — يحتاج سيرفر + كود</td><td>غير مطلوب (يتولاه OT1-Pro)</td></tr>
<tr><td>توليد رمز الوصول</td><td>يدوي — إعداد رمز دائم</td><td>تلقائي</td></tr>
<tr><td>الوقت الكلي لغير المطوّر</td><td>2–4 أسابيع (يشمل انتظار التحقق)</td><td>3–5 دقائق</td></tr>
<tr><td>يستطيع صاحب العمل فعلها بمفرده؟</td><td>نادراً</td><td>نعم، دائماً</td></tr>
</tbody>
</table>

<h2>ماذا عن الأعمال التي لديها رقم واتساب بيزنس بالفعل؟</h2>

<p>هذا السؤال الأكثر شيوعاً. إذا كنت تستخدم رقماً على تطبيق واتساب بيزنس — التطبيق العادي للأعمال الصغيرة، لا API — لديك خياران:</p>

<ul>
<li><strong>نقل رقمك الحالي للـAPI.</strong> هذا مدعوم بالكامل في تدفق Embedded Signup. تختار رقمك الحالي وتمر بخطوة التحقق. تنبيه مهم: بمجرد نقل رقم للـAPI، لا يمكن استخدامه على تطبيق واتساب بيزنس بعد الآن. لا يمكن تشغيل الاثنين في نفس الوقت على نفس الرقم.</li>
<li><strong>استخدام رقم منفصل للـAPI.</strong> بعض الأعمال تحتفظ برقم تطبيق واتساب بيزنس لخدمة العملاء الشخصية وتُضيف رقماً مخصصاً للـAPI (حتى شريحة بسيطة، $5–$10/شهر) لتعامل الذكاء الاصطناعي في OT1-Pro. هذا النهج الذي اتبعته لأول ثلاثة أشهر قبل نقل كل شيء لرقم API.</li>
</ul>

<p>النقل يستغرق 5 دقائق في تدفق Embedded Signup. الاضطراب الوحيد هو فترة قصيرة أثناء النقل — خلالها (عادةً أقل من دقيقة) يُظهر واتساب "هذا الرقم قيد النقل." بعدها، كل شيء يتدفق عبر OT1-Pro.</p>

<h2>ما يمكنك فعله فور الربط</h2>

<ul>
<li>جميع الرسائل الواردة تظهر في صندوق بريد OT1-Pro في الوقت الفعلي.</li>
<li>وكيل الذكاء الاصطناعي يبدأ التعامل مع الردود فوراً — يستخدم أي تدريب ذكاء اصطناعي قمت بتكوينه، أو يبدأ بموجّه المبيعات الافتراضي إذا لم تُخصّصه بعد.</li>
<li>مهمة الاسترداد تبدأ باستيراد محادثات واتساب السابقة لقاعدة بيانات OT1-Pro.</li>
<li>يمكنك إرسال رسائل صادرة وتشغيل حملات واستخدام جميع الميزات فوراً.</li>
</ul>

<h2>فحص صحة الاشتراك</h2>

<p>بعد الربط، يُجري OT1-Pro فحص صحة اشتراك للتحقق من تكوين webhook واتساب بشكل صحيح واستقبال الأحداث. إذا كشف الفحص عن مشكلة — قد تحدث إذا كانت أنظمة Meta تعاني مشكلة مؤقتة أثناء الإعداد — تُظهر المنصة تحذيراً وزر إصلاح تلقائي يُعيد تسجيل اشتراك webhook بنقرة واحدة. لا تحتاج معرفة ما هو webhook لإصلاح هذا.</p>

<h2>لمن هذا مُصمَّم</h2>

<p>Embedded Signup مُصمَّم لصاحب عمل صغير أو متوسط يريد ربط واتساب بأداة احترافية بدون توظيف مطوّر أو انتظار أسبوعين. التعقيد التقني مُجرَّد بالكامل. تضغط "ربط واتساب"، تتبع ثلاث خطوات في المتصفح، ورقمك يعمل.</p>

<p>اطّلع على <a href="https://ot1-pro.com/pricing">تفاصيل الأسعار هنا</a> (ربط واتساب متاح في جميع الخطط)، أو <a href="https://ot1-pro.com/vs/wati">قارننا مع WATI في إعداد واتساب</a>. <a href="https://ot1-pro.com/register">ابدأ مجاناً ←</a></p>

{{CTA}}
HTML,
            ],
        ];
    }
}
