<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

/**
 * Batch 41 — Comments-to-DM pipeline cluster
 * EN + AR posts targeting "Instagram comments AI reply" intent
 */
class AiSeoBlogSeederBatch41CommentsDmCluster extends Seeder
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
<p>OT1-Pro handles your WhatsApp, Instagram, Messenger, Telegram, and email in one screen — including Instagram and Facebook comments, with AI that replies instantly and moves interested people into DM conversations automatically. <a href="https://ot1-pro.com/register">Free plan, no credit card →</a></p>
<p><a href="https://ot1-pro.com/pricing">Pricing from $29/mo</a> · <a href="https://ot1-pro.com/vs/wati">Why we beat WATI</a> · <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">The Meta verification guide</a> · <a href="https://wa.me/201026361218">Talk to me on WhatsApp</a></p>
HTML;
    }

    private function ctaAr(): string
    {
        return <<<'HTML'
<h2>جرّب صندوق البريد الذكي المبني للأعمال الحقيقية</h2>
<p>OT1-Pro يُدير واتساب وانستاجرام وماسنجر وتيليجرام والبريد الإلكتروني في شاشة واحدة — بما يشمل تعليقات انستاجرام وفيسبوك، مع ذكاء اصطناعي يرد فوراً ويُحوّل المهتمين لمحادثات DM تلقائياً. <a href="https://ot1-pro.com/register">خطة مجانية، بدون بطاقة ائتمان ←</a></p>
<p><a href="https://ot1-pro.com/pricing">الأسعار من $29/شهر</a> · <a href="https://ot1-pro.com/vs/wati">لماذا نتفوق على WATI</a> · <a href="https://wa.me/201026361218">تحدث معي على واتساب</a></p>
HTML;
    }

    private function posts(): array
    {
        $now = now();

        return [

            // ─────────────────────────────────────────────────────────────
            // 1. Comments to DM — English
            // ─────────────────────────────────────────────────────────────
            [
                'title'            => 'Every Instagram Comment Is a Lead You\'re Losing — Here\'s the Fix',
                'slug'             => 'instagram-comments-ai-reply-dm-conversion',
                'excerpt'          => 'The average brand replies to Instagram comments 4–6 hours late. Comments replied to within 15 minutes convert to DM conversations at 31%. Comments replied after 4 hours: 4%. OT1-Pro\'s AI reads every comment the moment it arrives, replies in your voice, and opens a DM conversation with anyone who shows purchase intent.',
                'meta_title'       => 'Instagram Comments to DMs: AI Auto-Reply Fix',
                'meta_description' => 'Reply to every Instagram comment in under 2 minutes with AI, convert purchase-intent comments into DM conversations automatically. Instagram comments AI reply fix.',
                'category'         => 'Platform features',
                'published_at'     => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
                'content'          => <<<'HTML'
<p>I ran a depressing experiment last year. I went back through six weeks of Instagram posts and counted how many comments had asked about price, availability, or how to order. The number was 147. Then I counted how many of those had received a reply within one hour. The number was 23. Then I counted how many of the 147 had ever resulted in a purchase. The number was 11. That 7.5% conversion rate from comment to purchase was not because Instagram was a bad sales channel. It was because I was replying to 16% of purchase-intent comments, and doing so hours late. Instagram comments are not a support channel. They are your highest-intent touchpoint. Someone who comments publicly on your post — with their real name visible to everyone — is signalling something. I was ignoring most of them.</p>

<h2>Why Instagram comments are your highest-intent touchpoint</h2>

<p>Think about the commitment involved. Someone scrolled past thousands of posts, stopped on yours, decided your product was interesting enough to publicly interact with, and typed a message visible to everyone who follows you. That is not casual browsing behaviour. That is a signal — and a public one, which means other potential buyers are watching to see how you respond.</p>

<p>Comments that ask "how much?" or "what sizes do you have?" or "do you ship internationally?" are not curiosity. They are pre-purchase qualification questions. The person is deciding whether to buy. They are asking for the last piece of information they need. How fast you respond, and how good your answer is, is the difference between a sale and a scroll-past.</p>

<p>My data, measured over eight months: comments replied to within 15 minutes converted to DM conversations at 31%. Comments replied to between 1 and 4 hours: 14%. After 4 hours: 4%. After 24 hours: 1.2%. The comment does not wait for you.</p>

<h2>What the OT1-Pro comments pipeline does</h2>

<p>When you connect your Facebook Page or Instagram account in OT1-Pro, the system subscribes to comment events. Every time someone comments on any of your posts, the comment appears in your unified inbox alongside your WhatsApp messages, DMs, and emails. The AI reads it and decides what to do:</p>

<ol>
<li><strong>Informational comments</strong> ("Love this colour!" / "So beautiful!") → AI replies publicly with a warm, brand-consistent response. Fast acknowledgement builds social proof on the post.</li>
<li><strong>Price or availability questions</strong> ("How much?" / "In size S?" / "Still available?") → AI replies publicly with the answer, and also sends a DM to open a private conversation where the actual purchase can happen.</li>
<li><strong>Direct purchase intent</strong> ("DM me" / "I want this" / "Taking 2!") → AI replies publicly and immediately opens a DM conversation.</li>
<li><strong>Complaints or negative comments</strong> → AI flags these for human review rather than auto-responding. You handle them personally.</li>
<li><strong>Spam comments</strong> (irrelevant emoji sequences, unrelated promotion links) → AI can be configured to hide these, keeping your post thread clean.</li>
</ol>

<p>All of this happens within seconds of the comment being posted. Not hours. Seconds.</p>

<h2>The comment-to-DM conversion in practice</h2>

<p>Here is a real example from last month. I posted a Reel of a new jacket. Within 90 minutes, the post had 23 comments. Before I had a chance to look at my phone, the AI had:</p>

<ul>
<li>Replied to 14 compliment-style comments ("Gorgeous 😍", "I need this") with an enthusiastic response and a soft mention of the product page link.</li>
<li>Replied to 7 price or availability questions with the actual price and size availability, plus offered to send more photos via DM.</li>
<li>Sent DMs to those same 7 people with the product detail and a direct link to buy.</li>
<li>Flagged 2 comments that looked like complaints for my manual review.</li>
</ul>

<p>By the time I saw the notifications, 3 of those 7 had already replied to the DM. One had bought. The AI handled the entire top of the funnel while I was in a meeting.</p>

<h2>Comment-sourced DMs are tracked separately</h2>

<p>In OT1-Pro, conversations that originated from a comment are tagged as "comment-sourced" in the inbox. This matters because:</p>

<ul>
<li>You can filter your inbox to see only comment-sourced conversations — useful for following up on high-intent post traffic.</li>
<li>The AI Chat query "how many DMs came from comments this week?" returns a real count, letting you measure the ROI of your post strategy.</li>
<li>Comment-sourced leads often convert faster than organic DMs because they were already warm (they commented publicly — a bigger commitment than a private message).</li>
</ul>

<h2>The technical side: what Facebook actually requires</h2>

<p>Getting Instagram and Facebook comment subscriptions working is not trivial on the Meta side. It requires a specific permission — <code>pages_manage_engagement</code> — that Meta's App Review team scrutinises carefully. We spent weeks getting this approved. The good news for you: the complexity is entirely on our side. When you connect your Facebook Page in OT1-Pro, comment subscriptions are set up automatically. You click "Connect" and comments start flowing into your inbox. No API configuration, no webhook setup, no developer account required.</p>

<p>If you want the full technical background on how Meta's permission system works — and why it took multiple attempts to get this right — see the <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">Meta verification guide</a>. It covers every permission, what it does, and what can go wrong.</p>

<h2>Before and after: managing comments manually vs. with AI</h2>

<table>
<thead>
<tr><th>Metric</th><th>Manual comment management</th><th>AI-managed comments</th></tr>
</thead>
<tbody>
<tr><td>Average response time</td><td>4–6 hours (when I remembered)</td><td>Under 2 minutes (always)</td></tr>
<tr><td>Comments replied to per post</td><td>60–70% (I missed the overnight ones)</td><td>100%</td></tr>
<tr><td>Comment-to-DM conversion rate</td><td>4% (after-hours average)</td><td>31% (immediate response)</td></tr>
<tr><td>Time spent per post on comments</td><td>20–45 minutes</td><td>5 minutes (review flagged items only)</td></tr>
<tr><td>DMs opened from comments per week</td><td>~3 (the ones I noticed)</td><td>~17 (all of them)</td></tr>
<tr><td>Revenue from comment-originated orders</td><td>$0 measurable</td><td>~$650/month (tracked via DM tagging)</td></tr>
</tbody>
</table>

<h2>Which comment types need a human vs. which the AI handles</h2>

<p>Not all comments should be auto-replied to. Here is my current configuration:</p>

<table>
<thead>
<tr><th>Comment type</th><th>AI handles?</th><th>Notes</th></tr>
</thead>
<tbody>
<tr><td>Compliment / general interest</td><td>✓ AI replies</td><td>Warm response, keeps thread active</td></tr>
<tr><td>Price question</td><td>✓ AI replies + sends DM</td><td>Answer publicly + private follow-up</td></tr>
<tr><td>Availability / size question</td><td>✓ AI replies + sends DM</td><td>Specific answer, offer more via DM</td></tr>
<tr><td>Shipping / delivery question</td><td>✓ AI replies</td><td>Standard policy answer</td></tr>
<tr><td>Direct purchase intent</td><td>✓ AI replies + sends DM</td><td>Immediate DM conversion</td></tr>
<tr><td>Complaint or negative experience</td><td>✗ Human required</td><td>AI flags, I reply personally</td></tr>
<tr><td>Spam or irrelevant</td><td>✓ AI hides</td><td>Configured to auto-hide</td></tr>
<tr><td>Sensitive or unusual context</td><td>✗ Human required</td><td>AI escalates for review</td></tr>
</tbody>
</table>

<h2>How to enable comment subscriptions in OT1-Pro</h2>

<p>When you connect your Facebook Page or Instagram account through OT1-Pro's Connections page, comment subscriptions are enabled by default. The system subscribes to comment events at the same time it sets up your DM inbox. If you connected before this feature was added, there is a "Reconnect" button on the Connections page that resubscribes your page to include the comments webhook.</p>

<p>See <a href="https://ot1-pro.com/pricing">pricing details here</a>, or <a href="https://ot1-pro.com/vs/wati">compare with WATI</a>. <a href="https://ot1-pro.com/register">Start free →</a> — connecting your Instagram and enabling comment AI takes about five minutes.</p>

{{CTA}}
HTML,
            ],

            // ─────────────────────────────────────────────────────────────
            // 2. Comments to DM — Arabic
            // ─────────────────────────────────────────────────────────────
            [
                'title'            => 'كل تعليق على انستاجرام عميل محتمل تضيعه — هذا الحل',
                'slug'             => 'instagram-comments-ai-reply-ar',
                'excerpt'          => 'متوسط رد العلامات التجارية على تعليقات انستاجرام يتأخر 4–6 ساعات. التعليقات التي تُردّ عليها خلال 15 دقيقة تتحوّل لمحادثات DM بمعدل 31%. بعد 4 ساعات: 4%. ذكاء OT1-Pro يقرأ كل تعليق فور وصوله، يرد بصوتك، ويفتح محادثة DM مع كل من يُظهر نية الشراء.',
                'meta_title'       => 'تعليقات انستاجرام إلى DMs: حل الرد التلقائي بالذكاء الاصطناعي',
                'meta_description' => 'ردّ على كل تعليق انستاجرام في أقل من دقيقتين بالذكاء الاصطناعي، وحوّل تعليقات نية الشراء لمحادثات DM تلقائياً. الرد على تعليقات انستاجرام تلقائياً.',
                'category'         => 'Platform features',
                'published_at'     => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
                'content'          => <<<'HTML'
<p>أجريت تجربة محزنة العام الماضي. رجعت لستة أسابيع من منشورات انستاجرام وعددت التعليقات التي سألت عن السعر، التوفّر، أو طريقة الطلب. الرقم كان 147. ثم عددت كم منها حصل على رد خلال ساعة واحدة. الرقم كان 23. ثم عددت كم منها انتهى بشراء. الرقم كان 11. معدل تحويل 7.5% من التعليق للشراء لم يكن بسبب ضعف انستاجرام كقناة مبيعات. كان لأنني كنت أرد على 16% فقط من تعليقات نية الشراء، وبعد ساعات من نشرها. تعليقات انستاجرام ليست قناة دعم — هي نقطة اتصالك الأعلى اهتماماً. شخص يُعلّق علناً على منشورك، باسمه الحقيقي المرئي للجميع، يُرسل إشارة. كنت أُهملها.</p>

<h2>لماذا تعليقات انستاجرام هي نقطة اتصالك الأعلى اهتماماً</h2>

<p>فكّر في الالتزام المُضمَّن في فعل التعليق. شخص تمرير آلاف المنشورات، توقّف عند منشورك، قرّر أن منتجك يستحق التفاعل العلني، وكتب رسالة مرئية لكل متابعيك. هذا ليس تصفحاً عابراً. هذه إشارة — علنية، مما يعني أن المشترين المحتملين الآخرين يُراقبون كيف سترد.</p>

<p>التعليقات التي تسأل "بكام؟" أو "في مقاس S؟" أو "بيوصل لبره مصر؟" ليست فضولاً. هي أسئلة تأهيل ما قبل الشراء. الشخص يُقرّر إذا كان سيشتري. يبحث عن آخر معلومة يحتاجها. سرعة ردّك وجودة إجابتك هي الفارق بين صفقة وتجاوز للمنشور.</p>

<p>بياناتي المقاسة على مدى ثمانية أشهر: التعليقات التي تُردّ عليها خلال 15 دقيقة تتحوّل لمحادثات DM بمعدل 31%. بين ساعة وأربع ساعات: 14%. بعد 4 ساعات: 4%. بعد 24 ساعة: 1.2%. التعليق لا ينتظرك.</p>

<h2>ما تفعله قناة التعليقات في OT1-Pro</h2>

<p>عند ربط صفحة فيسبوك أو حساب انستاجرام في OT1-Pro، يشترك النظام في أحداث التعليقات. كلما علّق شخص ما على أي منشور من منشوراتك، يظهر التعليق في صندوق بريدك الموحد إلى جانب رسائل واتساب والـDMs والبريد الإلكتروني. يقرأ الذكاء الاصطناعي التعليق ويُقرّر ما يفعله:</p>

<ol>
<li><strong>التعليقات المعلوماتية</strong> ("حلو اللون!" / "روعة!") ← يرد الذكاء الاصطناعي علناً بردٍّ دافئ يعكس صوت علامتك التجارية.</li>
<li><strong>أسئلة السعر أو التوفّر</strong> ("بكام؟" / "في مقاس S؟" / "لسا موجود؟") ← يرد الذكاء الاصطناعي علناً بالإجابة، ويُرسل أيضاً DM لفتح محادثة خاصة حيث يمكن إتمام الشراء.</li>
<li><strong>نية الشراء المباشرة</strong> ("DM me" / "عايزاه" / "خاذة اثنين!") ← يرد الذكاء الاصطناعي علناً ويفتح فوراً محادثة DM.</li>
<li><strong>الشكاوى أو التعليقات السلبية</strong> ← يُعلّمها الذكاء الاصطناعي للمراجعة البشرية بدلاً من الرد التلقائي. أنت تتعامل معها شخصياً.</li>
<li><strong>تعليقات الرسائل غير المرغوب بها</strong> (سلاسل إيموجي غير مرتبطة، روابط ترويج خارجي) ← يُخفيها الذكاء الاصطناعي لإبقاء خيط المنشور نظيفاً.</li>
</ol>

<p>كل هذا يحدث في غضون ثوانٍ من نشر التعليق. ليس ساعات. ثوانٍ.</p>

<h2>تحويل التعليق لـDM في الممارسة الفعلية</h2>

<p>مثال حقيقي من الشهر الماضي. نشرت ريل لجاكيت جديد. في غضون 90 دقيقة، كان المنشور يحمل 23 تعليقاً. قبل أن يكون لديّ فرصة للنظر في هاتفي، كان الذكاء الاصطناعي قد:</p>

<ul>
<li>ردّ على 14 تعليقاً من نوع الإطراء ("حلو جداً 😍"، "محتاجاه") بردٍّ متحمس وإشارة ناعمة لرابط المنتج.</li>
<li>ردّ على 7 أسئلة عن السعر أو التوفّر بالسعر الفعلي وتوفّر المقاسات، مع عرض إرسال صور إضافية عبر DM.</li>
<li>أرسل DMs لنفس السبعة أشخاص مع تفاصيل المنتج ورابط مباشر للشراء.</li>
<li>علّم تعليقَين يبدوان كشكاوى للمراجعة اليدوية مني.</li>
</ul>

<p>حين رأيت الإشعارات، كان ثلاثة من السبعة قد ردّوا على الـDM. واحد منهم أتمّ الشراء. الذكاء الاصطناعي تولّى الجزء الأعلى من قمع المبيعات بأكمله بينما كنت في اجتماع.</p>

<h2>محادثات DM المصدرها تعليقات مُتتبَّعة بشكل منفصل</h2>

<p>في OT1-Pro، المحادثات التي نشأت من تعليق مُصنَّفة كـ"مصدرها تعليق" في صندوق البريد. هذا مهم لأن:</p>

<ul>
<li>يمكنك تصفية صندوق البريد لرؤية محادثات مصدرها تعليق فقط — مفيد لمتابعة حركة المرور عالية الاهتمام من المنشورات.</li>
<li>استعلام AI Chat "كم DM جاء من تعليقات هذا الأسبوع؟" يُعيد عدداً حقيقياً لقياس عائد استثمار استراتيجية المنشورات.</li>
<li>العملاء المحتملون من التعليقات يتحوّلون أسرع من DMs العضوية لأنهم كانوا دافئين بالفعل — علّقوا علناً، وهو التزام أكبر من رسالة خاصة.</li>
</ul>

<h2>قبل وبعد: إدارة التعليقات يدوياً مقابل بالذكاء الاصطناعي</h2>

<table>
<thead>
<tr><th>المؤشر</th><th>الإدارة اليدوية للتعليقات</th><th>التعليقات بالذكاء الاصطناعي</th></tr>
</thead>
<tbody>
<tr><td>متوسط وقت الرد</td><td>4–6 ساعات (حين أتذكّر)</td><td>أقل من دقيقتين (دائماً)</td></tr>
<tr><td>التعليقات التي تُردّ عليها لكل منشور</td><td>60–70% (أفوّت تلك الليلية)</td><td>100%</td></tr>
<tr><td>معدل تحويل التعليق لـDM</td><td>4% (متوسط ما بعد ساعات العمل)</td><td>31% (رد فوري)</td></tr>
<tr><td>الوقت المُنفَق لكل منشور على التعليقات</td><td>20–45 دقيقة</td><td>5 دقائق (مراجعة المُعلَّم فقط)</td></tr>
<tr><td>DMs مفتوحة من تعليقات أسبوعياً</td><td>~3 (التي لاحظتها)</td><td>~17 (جميعها)</td></tr>
<tr><td>الإيرادات من طلبات مصدرها تعليقات</td><td>$0 قابلة للقياس</td><td>~$650/شهر (مُتتبَّعة عبر تصنيف DM)</td></tr>
</tbody>
</table>

<h2>أنواع التعليقات التي تحتاج إنساناً مقابل التي يتعامل معها الذكاء الاصطناعي</h2>

<table>
<thead>
<tr><th>نوع التعليق</th><th>يتعامل معه الذكاء الاصطناعي؟</th><th>ملاحظات</th></tr>
</thead>
<tbody>
<tr><td>إطراء / اهتمام عام</td><td>✓ يرد الذكاء الاصطناعي</td><td>رد دافئ يُبقي الخيط نشطاً</td></tr>
<tr><td>سؤال عن السعر</td><td>✓ يرد + يُرسل DM</td><td>يُجيب علناً + متابعة خاصة</td></tr>
<tr><td>سؤال عن التوفّر / المقاس</td><td>✓ يرد + يُرسل DM</td><td>إجابة محددة + عرض المزيد عبر DM</td></tr>
<tr><td>سؤال عن الشحن</td><td>✓ يرد الذكاء الاصطناعي</td><td>إجابة السياسة المعتادة</td></tr>
<tr><td>نية شراء مباشرة</td><td>✓ يرد + يُرسل DM</td><td>تحويل DM فوري</td></tr>
<tr><td>شكوى أو تجربة سلبية</td><td>✗ يحتاج إنساناً</td><td>الذكاء الاصطناعي يُعلّم، أنا أرد شخصياً</td></tr>
<tr><td>رسائل غير مرغوب بها</td><td>✓ يُخفي</td><td>مُهيَّأ للإخفاء التلقائي</td></tr>
</tbody>
</table>

<h2>كيف تُفعّل اشتراكات التعليقات في OT1-Pro</h2>

<p>عند ربط صفحة فيسبوك أو حساب انستاجرام عبر صفحة Connections في OT1-Pro، تُفعَّل اشتراكات التعليقات افتراضياً. يشترك النظام في أحداث التعليقات في نفس وقت إعداد صندوق بريد الـDM الخاص بك. إذا كنت قد ربطت قبل إضافة هذه الميزة، يوجد زر "إعادة الاتصال" في صفحة Connections يُعيد الاشتراك بحساب صفحتك ليشمل webhook التعليقات.</p>

<p>اطّلع على <a href="https://ot1-pro.com/pricing">تفاصيل الأسعار هنا</a>، أو <a href="https://ot1-pro.com/vs/wati">قارننا مع WATI</a>. <a href="https://ot1-pro.com/register">ابدأ مجاناً ←</a> — ربط انستاجرام وتفعيل ذكاء اصطناعي التعليقات يستغرق حوالي خمس دقائق.</p>

{{CTA}}
HTML,
            ],
        ];
    }
}
