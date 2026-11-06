<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

/**
 * Batch 42 — Chat history import cluster
 * EN + AR posts targeting "import Facebook Messenger chat history" intent
 */
class AiSeoBlogSeederBatch42ChatHistoryCluster extends Seeder
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
<p>OT1-Pro connects your WhatsApp, Instagram, Messenger, Telegram, and email — and imports your existing conversation history automatically at connect time, so your AI starts with real context from day one. <a href="https://ot1-pro.com/register">Free plan, no credit card →</a></p>
<p><a href="https://ot1-pro.com/pricing">Pricing from $29/mo</a> · <a href="https://ot1-pro.com/vs/wati">Why we beat WATI</a> · <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">The Meta verification guide</a> · <a href="https://wa.me/201026361218">Talk to me on WhatsApp</a></p>
HTML;
    }

    private function ctaAr(): string
    {
        return <<<'HTML'
<h2>جرّب صندوق البريد الذكي المبني للأعمال الحقيقية</h2>
<p>OT1-Pro يربط واتساب وانستاجرام وماسنجر وتيليجرام والبريد الإلكتروني — ويستورد تاريخ محادثاتك الموجود تلقائياً عند الربط، لأن ذكاءك الاصطناعي يبدأ بسياق حقيقي من اليوم الأول. <a href="https://ot1-pro.com/register">خطة مجانية، بدون بطاقة ائتمان ←</a></p>
<p><a href="https://ot1-pro.com/pricing">الأسعار من $29/شهر</a> · <a href="https://ot1-pro.com/vs/wati">لماذا نتفوق على WATI</a> · <a href="https://wa.me/201026361218">تحدث معي على واتساب</a></p>
HTML;
    }

    private function posts(): array
    {
        $now = now();

        return [

            // ─────────────────────────────────────────────────────────────
            // 1. Chat History Import — English
            // ─────────────────────────────────────────────────────────────
            [
                'title'            => 'Connect Your Facebook Page and Get 90 Days of Chat History Back Instantly',
                'slug'             => 'facebook-chat-history-import-messenger',
                'excerpt'          => 'Most inbox tools start fresh when you connect a new platform — your previous conversations disappear. OT1-Pro imports the last 90+ days of Messenger and Instagram DM history at connect time, so your AI immediately knows your customers and your conversation history is searchable from day one.',
                'meta_title'       => 'Import Facebook Messenger Chat History on Connect',
                'meta_description' => 'Connect your Facebook Page to OT1-Pro and get 90+ days of Messenger chat history imported automatically. AI starts with real context. Import Facebook Messenger chat history.',
                'category'         => 'Platform features',
                'published_at'     => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
                'content'          => <<<'HTML'
<p>I connected my Facebook Page to the first inbox tool I ever used and was genuinely surprised by what I saw: nothing. Zero previous conversations. The tool started fresh, as if my page had never received a single message. For a page with two years of customer conversations — orders, questions, complaints, follow-ups — this meant my "inbox" was empty on day one and the AI had zero context about who my customers were. Every previous conversation was inaccessible unless I went back to the native Facebook interface and looked it up manually. I accepted this as normal for about eight months before I discovered it did not have to be this way.</p>

<h2>Why conversation history matters more than you might think</h2>

<p>Your inbox is not just a message router. It is your business's institutional memory. The customer who is messaging you right now about a size exchange — have they ordered before? Did they have a complaint last time? What products have they expressed interest in? Without conversation history, the AI and your team are answering cold every single time. You cannot personalise. You cannot see patterns. You cannot have the AI reference past context in its replies ("Last time you ordered the blue dress — are you looking for something similar?"). You are starting at zero every conversation.</p>

<p>Beyond personalisation, history is the raw material for everything the AI does well: Deep Analysis works better with more conversations, AI Chat returns more accurate lead scoring when it has 90 days of data instead of 7, and the monthly business review gives you comparisons instead of snapshots.</p>

<h2>What OT1-Pro does at connect time</h2>

<p>When you connect a Facebook Page or Instagram account to OT1-Pro, the system immediately starts a background backfill job. This job goes back through your conversation history and imports it into the OT1-Pro database, making it available to your inbox, your AI, and all analytics features.</p>

<p>The backfill covers:</p>

<ul>
<li><strong>Facebook Messenger:</strong> All stored conversations with their full message thread. Meta's API provides access to conversations going back as far as the data exists for your page.</li>
<li><strong>Instagram DMs:</strong> All conversations accessible via the Instagram Messaging API, typically covering the last 30–90 days depending on activity volume and Meta's data retention policies.</li>
<li><strong>Contact deduplication:</strong> If the same customer has messaged you on both Facebook and Instagram, the system links the conversations under one contact profile where possible.</li>
</ul>

<p>The backfill runs in the background — your inbox starts receiving new messages immediately. As the backfill completes, historical conversations appear progressively in the inbox. For a typical page with 500–2,000 historical conversations, the full backfill completes in 10–30 minutes.</p>

<h2>The backfill progress bar</h2>

<p>On the Connections page in OT1-Pro, while your backfill is running, you can see a live progress bar showing how many conversations have been imported and the estimated time to completion. When it finishes, you get a persistent "Backfill complete" notification. This is not just for reassurance — it tells you exactly when your Deep Analysis run and AI Chat queries will have full data to work with.</p>

<h2>What you get on day one vs. what competitors give you</h2>

<table>
<thead>
<tr><th>What happens when you connect</th><th>OT1-Pro</th><th>WATI ($39/mo)</th><th>Respond.io ($99/mo)</th></tr>
</thead>
<tbody>
<tr><td>Previous conversations imported</td><td>✓ Full backfill</td><td>✗ Starts fresh</td><td>✗ Starts fresh</td></tr>
<tr><td>AI has context from day one</td><td>✓ Immediately</td><td>✗ Builds over time</td><td>✗ Builds over time</td></tr>
<tr><td>Deep Analysis works immediately</td><td>✓ Full history</td><td>✗ No history to analyse</td><td>✗ No history to analyse</td></tr>
<tr><td>AI Chat can answer "who are my top leads?"</td><td>✓ From day one</td><td>Only after weeks of data</td><td>Only after weeks of data</td></tr>
<tr><td>Customer profiles pre-populated</td><td>✓ From history</td><td>✗ Blank</td><td>✗ Blank</td></tr>
<tr><td>Past conversation searchable</td><td>✓</td><td>✗</td><td>Limited</td></tr>
</tbody>
</table>

<p>The competitors' "start fresh" approach is not negligence — it is an engineering simplification that works for teams whose primary use case is managing new inbound volume. For businesses that want to use AI to understand their existing customer base, starting with an empty database makes the product nearly useless for the first few months.</p>

<h2>A real example: what I found in my first backfill</h2>

<p>When I connected my main Facebook Page and the backfill completed, I ran an AI Chat query immediately: "Who are the customers who have ordered more than twice?" My page had two years of data. The AI returned 34 names. I had no idea I had 34 repeat customers — I had been treating every conversation as if it were with a new person.</p>

<p>I sent a personal message to those 34 people. I mentioned their previous orders by name and offered a loyalty discount. 11 of them placed a new order within 48 hours. Revenue from that single follow-up: ~$715. The only thing that made that possible was having the conversation history available. Without the backfill, those 34 people would have been invisible to me for however long it took to build up new data.</p>

<h2>Backfill for Instagram vs. Facebook: the differences</h2>

<p>Instagram and Facebook have different data availability through Meta's API, and it matters for your expectations:</p>

<table>
<thead>
<tr><th>Platform</th><th>History depth</th><th>Limitation</th></tr>
</thead>
<tbody>
<tr><td>Facebook Messenger</td><td>All accessible history (can be years)</td><td>Meta limits very old data for some page types</td></tr>
<tr><td>Instagram DMs</td><td>Last 30–90 days typically</td><td>Instagram API has shorter data retention windows</td></tr>
<tr><td>Instagram Comments</td><td>Recent posts</td><td>Older post comments may not be available via API</td></tr>
</tbody>
</table>

<p>Instagram's shorter backfill window is a Meta API limitation, not an OT1-Pro one. Once you are connected, all future conversations are stored permanently and your history grows over time.</p>

<h2>The reconnect nudge: when tokens expire</h2>

<p>Facebook and Instagram access tokens expire periodically. When this happens, OT1-Pro detects it and shows a prominent reconnect nudge in your inbox and on the Connections page. Clicking reconnect re-authorises the connection and OT1-Pro runs a new incremental backfill for any conversations that came in since the last successful sync. You do not lose any conversations that were already imported — only the gap since the last sync is filled.</p>

<p>See <a href="https://ot1-pro.com/pricing">our pricing page</a> to understand what history depth each plan supports, or <a href="https://ot1-pro.com/vs/wati">see how we compare to WATI</a> on this feature specifically. <a href="https://ot1-pro.com/register">Start free →</a></p>

{{CTA}}
HTML,
            ],

            // ─────────────────────────────────────────────────────────────
            // 2. Chat History Import — Arabic
            // ─────────────────────────────────────────────────────────────
            [
                'title'            => 'اربط صفحة فيسبوك واستورد تاريخ المحادثات كاملاً فوراً',
                'slug'             => 'facebook-chat-history-import-ar',
                'excerpt'          => 'معظم أدوات صناديق البريد تبدأ من الصفر عند ربط منصة جديدة — محادثاتك السابقة تختفي. OT1-Pro يستورد آخر 90+ يوم من تاريخ ماسنجر وانستاجرام DM تلقائياً عند الربط، لأن ذكاءك الاصطناعي يعرف عملاءك من اليوم الأول.',
                'meta_title'       => 'استيراد تاريخ محادثات فيسبوك ماسنجر عند الربط',
                'meta_description' => 'اربط صفحة فيسبوك بـOT1-Pro واستورد 90+ يوم من تاريخ ماسنجر تلقائياً. الذكاء الاصطناعي يبدأ بسياق حقيقي. استيراد تاريخ محادثات فيسبوك ماسنجر.',
                'category'         => 'Platform features',
                'published_at'     => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
                'content'          => <<<'HTML'
<p>ربطت صفحتي على فيسبوك بأول أداة صندوق بريد جرّبتها وفوجئت حقاً بما رأيته: لا شيء. صفر محادثات سابقة. الأداة بدأت من الصفر، كأن صفحتي لم تتلقَّ رسالة واحدة قط. لصفحة لديها سنتان من محادثات العملاء — طلبات، أسئلة، شكاوى، متابعات — هذا يعني أن "صندوق بريدي" كان فارغاً في اليوم الأول والذكاء الاصطناعي لم يكن لديه أي سياق عن هوية عملائي. كل محادثة سابقة كانت غير قابلة للوصول إلا إذا ذهبت للواجهة الأصلية لفيسبوك وبحثت عنها يدوياً. قبلت هذا كأمر طبيعي لثمانية أشهر قبل أن أكتشف أنه لا ينبغي أن يكون كذلك.</p>

<h2>لماذا تاريخ المحادثات أهم مما تعتقد</h2>

<p>صندوق بريدك ليس مجرد موجّه رسائل. إنه ذاكرة عملك المؤسسية. العميل الذي يراسلك الآن بشأن استبدال مقاس — هل اشترى من قبل؟ هل كانت لديه شكوى في المرة الأخيرة؟ ما المنتجات التي أبدى اهتماماً بها؟ بدون تاريخ المحادثات، الذكاء الاصطناعي وفريقك يُجيبان من الصفر في كل مرة. لا تخصيص. لا أنماط. لا قدرة للذكاء الاصطناعي على الإشارة لسياق سابق ("المرة الماضية طلبت الفستان الأزرق — هل تبحثين عن شيء مشابه؟"). تبدأ من الصفر في كل محادثة.</p>

<p>بعيداً عن التخصيص، التاريخ هو المادة الخام لكل ما يفعله الذكاء الاصطناعي جيداً: Deep Analysis يعمل بشكل أفضل مع محادثات أكثر، AI Chat تُعيد تقييماً أكثر دقة للعملاء المحتملين عند توفّر 90 يوماً من البيانات بدلاً من 7، والمراجعة الشهرية للعمل تُعطيك مقارنات بدلاً من لقطات.</p>

<h2>ما يفعله OT1-Pro عند الربط</h2>

<p>عند ربط صفحة فيسبوك أو حساب انستاجرام بـOT1-Pro، يبدأ النظام فوراً مهمة استرداد خلفية (backfill). تعود هذه المهمة عبر تاريخ محادثاتك وتستورده إلى قاعدة بيانات OT1-Pro، مما يجعله متاحاً لصندوق بريدك وذكاءك الاصطناعي وجميع ميزات التحليل.</p>

<p>يغطي الاسترداد:</p>

<ul>
<li><strong>فيسبوك ماسنجر:</strong> جميع المحادثات المخزّنة مع خيوط الرسائل الكاملة. API Meta يُتيح الوصول للمحادثات بأثر رجعي بقدر ما تتوفّر البيانات لصفحتك.</li>
<li><strong>Instagram DMs:</strong> جميع المحادثات المتاحة عبر Instagram Messaging API، تغطي عادةً آخر 30–90 يوماً حسب حجم النشاط وسياسات احتفاظ Meta بالبيانات.</li>
<li><strong>إزالة تكرار جهات الاتصال:</strong> إذا راسلك نفس العميل على فيسبوك وانستاجرام، يربط النظام المحادثات تحت ملف اتصال واحد حيثما أمكن.</li>
</ul>

<p>يعمل الاسترداد في الخلفية — صندوق بريدك يبدأ باستقبال الرسائل الجديدة فوراً. بينما يكتمل الاسترداد، تظهر المحادثات التاريخية تدريجياً في صندوق البريد. لصفحة عادية بـ500–2,000 محادثة تاريخية، يكتمل الاسترداد الكامل في 10–30 دقيقة.</p>

<h2>شريط تقدم الاسترداد</h2>

<p>في صفحة Connections في OT1-Pro، أثناء تشغيل الاسترداد، يمكنك رؤية شريط تقدم حي يُظهر كم محادثة تم استيرادها والوقت المُقدَّر للاكتمال. عند الانتهاء، تحصل على إشعار "اكتمل الاسترداد" ثابت. هذا ليس للطمأنينة فقط — يُخبرك بالضبط متى ستكون بيانات Deep Analysis وAI Chat كاملة.</p>

<h2>ما تحصل عليه من اليوم الأول مقارنةً بالمنافسين</h2>

<table>
<thead>
<tr><th>ما يحدث عند الربط</th><th>OT1-Pro</th><th>WATI ($39/شهر)</th><th>Respond.io ($99/شهر)</th></tr>
</thead>
<tbody>
<tr><td>استيراد المحادثات السابقة</td><td>✓ استرداد كامل</td><td>✗ يبدأ من الصفر</td><td>✗ يبدأ من الصفر</td></tr>
<tr><td>الذكاء الاصطناعي لديه سياق من اليوم الأول</td><td>✓ فوراً</td><td>✗ يتراكم مع الوقت</td><td>✗ يتراكم مع الوقت</td></tr>
<tr><td>Deep Analysis يعمل فوراً</td><td>✓ تاريخ كامل</td><td>✗ لا تاريخ لتحليله</td><td>✗ لا تاريخ لتحليله</td></tr>
<tr><td>AI Chat تُجيب "من أفضل عملائي المحتملين؟"</td><td>✓ من اليوم الأول</td><td>بعد أسابيع من البيانات فقط</td><td>بعد أسابيع من البيانات فقط</td></tr>
<tr><td>ملفات العملاء مُعبَّأة مسبقاً</td><td>✓ من التاريخ</td><td>✗ فارغة</td><td>✗ فارغة</td></tr>
<tr><td>المحادثات السابقة قابلة للبحث</td><td>✓</td><td>✗</td><td>محدودة</td></tr>
</tbody>
</table>

<h2>مثال حقيقي: ما وجدته في أول استرداد</h2>

<p>عندما ربطت صفحتي الرئيسية على فيسبوك واكتمل الاسترداد، شغّلت استعلام AI Chat فوراً: "من العملاء الذين طلبوا أكثر من مرتين؟" كانت صفحتي تحمل بيانات سنتين. الذكاء الاصطناعي أعاد 34 اسماً. لم أكن أعلم أن لديّ 34 عميلاً متكرراً — كنت أتعامل مع كل محادثة كأنها مع شخص جديد.</p>

<p>أرسلت رسالة شخصية لأولئك الـ34 شخصاً. ذكرت طلباتهم السابقة بالاسم وقدّمت خصم ولاء. 11 منهم قدّموا طلباً جديداً خلال 48 ساعة. الإيرادات من تلك المتابعة الواحدة: ~$715. الشيء الوحيد الذي جعل ذلك ممكناً كان توفّر تاريخ المحادثات. بدون الاسترداد، كان أولئك الـ34 شخصاً سيظلون غير مرئيين لي.</p>

<h2>الاسترداد لانستاجرام مقابل فيسبوك: الفروقات</h2>

<table>
<thead>
<tr><th>المنصة</th><th>عمق التاريخ</th><th>القيود</th></tr>
</thead>
<tbody>
<tr><td>فيسبوك ماسنجر</td><td>كل التاريخ المتاح (قد يكون سنوات)</td><td>Meta تُقيّد البيانات القديمة جداً لبعض أنواع الصفحات</td></tr>
<tr><td>Instagram DMs</td><td>آخر 30–90 يوم عادةً</td><td>Instagram API لديه نوافذ احتفاظ أقصر بالبيانات</td></tr>
<tr><td>تعليقات انستاجرام</td><td>المنشورات الأخيرة</td><td>تعليقات المنشورات الأقدم قد لا تكون متاحة عبر API</td></tr>
</tbody>
</table>

<p>نافذة الاسترداد الأقصر لانستاجرام هي قيد API Meta وليس OT1-Pro. بمجرد ربطك، تُخزَّن جميع المحادثات المستقبلية بشكل دائم ويزداد تاريخك بمرور الوقت.</p>

<h2>تنبيه إعادة الاتصال: عند انتهاء صلاحية الرموز</h2>

<p>رموز وصول فيسبوك وانستاجرام تنتهي صلاحيتها دورياً. عند حدوث ذلك، يكتشف OT1-Pro الأمر ويُظهر تنبيه إعادة اتصال بارزاً في صندوق بريدك وصفحة Connections. النقر على إعادة الاتصال يُعيد الترخيص ويُشغّل OT1-Pro استرداداً تزايدياً جديداً لأي محادثات وصلت منذ آخر مزامنة ناجحة. لا تضيع أي محادثات تم استيرادها بالفعل — تُملأ الفجوة منذ آخر مزامنة فقط.</p>

<p>اطّلع على <a href="https://ot1-pro.com/pricing">صفحة الأسعار</a>، أو <a href="https://ot1-pro.com/vs/wati">قارننا مع WATI</a> على هذه الميزة تحديداً. <a href="https://ot1-pro.com/register">ابدأ مجاناً ←</a></p>

{{CTA}}
HTML,
            ],
        ];
    }
}
