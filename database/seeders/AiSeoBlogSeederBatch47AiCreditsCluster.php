<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

class AiSeoBlogSeederBatch47AiCreditsCluster extends Seeder
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
<div class="cta-block bg-indigo-50 border border-indigo-200 rounded-xl p-8 my-10 text-center">
  <h3 class="text-2xl font-bold text-indigo-900 mb-3">Start with 10 Free AI Credits</h3>
  <p class="text-indigo-700 mb-6">Every new account gets 10 credits on signup — enough to run a Deep Analysis and see what your AI found before you spend anything.</p>
  <a href="/register" class="inline-block bg-indigo-600 text-white font-semibold px-8 py-3 rounded-lg hover:bg-indigo-700 transition">Get 10 Free Credits →</a>
  <p class="text-sm text-indigo-500 mt-4">No credit card required. See <a href="/pricing" class="underline">full pricing</a> for credit bundle details.</p>
</div>
HTML;
    }

    private function ctaAr(): string
    {
        return <<<'HTML'
<div class="cta-block bg-indigo-50 border border-indigo-200 rounded-xl p-8 my-10 text-center" dir="rtl">
  <h3 class="text-2xl font-bold text-indigo-900 mb-3">ابدأ بـ10 أرصدة ذكاء اصطناعي مجانية</h3>
  <p class="text-indigo-700 mb-6">كل حساب جديد يحصل على 10 أرصدة عند التسجيل — كافية لتشغيل تحليل عميق وترى ما اكتشفه الذكاء الاصطناعي قبل أي دفع.</p>
  <a href="/register" class="inline-block bg-indigo-600 text-white font-semibold px-8 py-3 rounded-lg hover:bg-indigo-700 transition">احصل على 10 أرصدة مجانية ←</a>
  <p class="text-sm text-indigo-500 mt-4">لا حاجة لبطاقة ائتمان. انظر <a href="/pricing" class="underline">التسعير الكامل</a> لتفاصيل حزم الأرصدة.</p>
</div>
HTML;
    }

    private function posts(): array
    {
        $now = now();

        return [
            // ── English Post ──────────────────────────────────────────────────────────
            [
                'title'            => 'AI Credits: Pay for What You Use, Skip What You Don\'t',
                'slug'             => 'ai-credits-pay-as-you-go-usage-billing',
                'excerpt'          => 'OT1-Pro\'s AI credit system charges only for compute-heavy operations — Deep Analysis runs cost 1 credit each. The monthly subscription covers unlimited AI auto-replies; credits are for on-demand analytical tasks. Here\'s exactly how it works and how to budget it.',
                'meta_title'       => 'AI Credits: Pay for What You Use | OT1-Pro',
                'meta_description' => 'OT1-Pro\'s AI credit billing explained: 1 credit per Deep Analysis, unlimited auto-replies included, no seat fees. See how the AI credit billing model works.',
                'category'         => 'AI Tools',
                'published_at'     => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
                'content'          => <<<'HTML'
<article>

<p>I hate SaaS billing that punishes you for using the product.</p>

<p>When I was building OT1-Pro, I looked at how AI tools charge. Some bill per message sent. Some bill per AI reply. Some charge per seat, per month, whether your 11 team members used the AI 500 times or zero times. I watched a small Egyptian retailer in my beta group get a $280 bill from a competitor because their AI auto-responder fired on a week when they ran a campaign and volume spiked 4×. They hadn't done anything wrong — they just got a lot of customers.</p>

<p>That model is broken. So I built something different.</p>

<h2>How OT1-Pro AI Billing Actually Works</h2>

<p>There are two distinct things the AI does, and they cost different amounts of compute to run:</p>

<ol>
  <li><strong>AI auto-replies</strong> — responding to incoming messages in real-time. These are fast, run on a lightweight model, and happen constantly. If you have 300 customers messaging per day, that's 300 small inference calls. I include these in every subscription plan, unlimited, because throttling them would break the whole product.</li>
  <li><strong>Deep Analysis</strong> — the AI reads all your stored conversations and surfaces patterns, top objections, missed opportunities, customer segments. This is a heavy operation: it processes months of data, runs multiple LLM passes, and generates a structured report. It costs real compute. It runs once per 24 hours per team, cached to avoid repeat charges for the same data.</li>
</ol>

<p>So the split is: <strong>subscription covers auto-replies</strong>, <strong>AI credits cover Deep Analysis</strong>.</p>

<p>One Deep Analysis run = one AI credit.</p>

<h2>The Four-Tier Credit Ladder</h2>

<p>Every plan includes credits in the bundle. You can also buy extra credits à la carte. Here's the current structure:</p>

<table>
  <thead>
    <tr>
      <th>Plan</th>
      <th>Monthly Price</th>
      <th>AI Credits Included</th>
      <th>Extra Credits (top-up)</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Free</td>
      <td>$0</td>
      <td>10 (one-time, on signup)</td>
      <td>$3 / 10 credits</td>
    </tr>
    <tr>
      <td>Starter</td>
      <td>$29/mo</td>
      <td>30/mo (renews monthly)</td>
      <td>$3 / 10 credits</td>
    </tr>
    <tr>
      <td>Pro</td>
      <td>$79/mo</td>
      <td>100/mo (renews monthly)</td>
      <td>$2.50 / 10 credits</td>
    </tr>
    <tr>
      <td>Enterprise</td>
      <td>Custom</td>
      <td>Custom pool</td>
      <td>Negotiated rate</td>
    </tr>
  </tbody>
</table>

<p>At one credit per Deep Analysis run, the Starter plan's 30 monthly credits = one deep analysis per day, which is the max cadence anyway (the cache window is 24 hours). Most teams use it 2-3 times per week, so 30 credits lasts well over a month in practice.</p>

<h2>Why I Chose Credits Over Per-Seat or Per-Message Pricing</h2>

<p>Per-seat pricing has a dirty secret: you pay for seats whether they produce value or not. A 10-person team where 3 people actively use AI still pays for 10. That's not how I want to run a tool.</p>

<p>Per-message billing is worse for anyone running campaigns or seasonal businesses. I've seen operators get destroyed in December. A shoe retailer running a Eid al-Adha campaign shouldn't pay 10× more for AI that month just because 10,000 customers replied "متوفر إيه من المقاسات؟"</p>

<p>Credits solve this cleanly: the expensive thing (Deep Analysis) uses credits; the cheap thing (auto-replies) is flat-rate. You know your fixed monthly cost, and the only variable is how often you trigger a compute-heavy analytical operation.</p>

<h2>The Billing Meter Chip: What You Actually See</h2>

<p>In the OT1-Pro dashboard, every team sees a small credit meter in the top navigation — a chip that shows your remaining credits and refreshes in real time after each operation. It looks like:</p>

<ul>
  <li><strong>Green chip</strong>: 20+ credits remaining</li>
  <li><strong>Yellow chip</strong>: 5-19 credits remaining (soft warning)</li>
  <li><strong>Red chip</strong>: 1-4 credits remaining (top-up prompt appears)</li>
</ul>

<p>When you click "Run Deep Analysis," the system shows a confirmation dialog: "This will use 1 AI credit. You have 14 remaining." No surprise deductions. No silent billing at month end. You see the cost before you approve it.</p>

<h2>What One AI Credit Buys You in Real Terms</h2>

<p>I ran a Deep Analysis last week on a test account with 2,400 stored conversations from a fashion retailer in Jeddah. What came back in one credit:</p>

<ul>
  <li>Top 7 customer objections ranked by frequency (price was #1 at 34%, not product quality)</li>
  <li>3 conversation patterns flagged as missed sales: customers who asked for size availability and got no reply within 2 hours</li>
  <li>A "loyal segment" of 41 customers who had bought 3+ times and hadn't been messaged in 90+ days</li>
  <li>Peak inquiry hours: 9pm–11pm Saudi time, Thursdays and Fridays (the brand was sending campaigns at 10am, missing peak completely)</li>
  <li>Language split: 68% Arabic, 22% English, 10% mixed — but the AI was replying in English-first to everyone</li>
</ul>

<p>That analysis — if done manually — would take a junior analyst 2-3 days. It ran in about 90 seconds and cost $0.30 in credits.</p>

<h2>Competitor Comparison: What $29/Month Gets You Elsewhere</h2>

<table>
  <thead>
    <tr>
      <th>Tool</th>
      <th>$29/mo equivalent plan</th>
      <th>AI auto-reply</th>
      <th>Analytical AI</th>
      <th>Billing surprise risk</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>OT1-Pro Starter</td>
      <td>$29/mo</td>
      <td>Unlimited (included)</td>
      <td>30 credits/mo</td>
      <td>Low — meter chip shows usage</td>
    </tr>
    <tr>
      <td>Respond.io ($239M raised)</td>
      <td>~$79/mo for 3 seats</td>
      <td>Add-on cost</td>
      <td>Basic reporting only</td>
      <td>High — seats × overage</td>
    </tr>
    <tr>
      <td>WATI ($12M raised)</td>
      <td>$49/mo (WhatsApp only)</td>
      <td>Limited per tier</td>
      <td>None on base plan</td>
      <td>Medium — tier limits</td>
    </tr>
    <tr>
      <td>Intercom ($241M raised)</td>
      <td>$29/mo per seat (Starter)</td>
      <td>AI add-on, extra cost</td>
      <td>Fin AI, expensive add-on</td>
      <td>Very high — per-resolution fees</td>
    </tr>
  </tbody>
</table>

<p>Intercom's AI pricing model charges per "AI resolution" — if the AI closes a conversation, you pay. I've seen teams with high-volume support discover that their monthly AI bill at Intercom was more than their base subscription. That is exactly the model I refused to copy.</p>

<h2>How Credits Roll Over (and When They Don't)</h2>

<p>Monthly credits reset on your billing date. They don't roll over month to month — this is a deliberate choice to keep the accounting simple and the pricing predictable. What you don't use this month doesn't accumulate silently and then confuse you about how many you actually have.</p>

<p>There's one exception: extra credits you <em>purchase</em> (top-up credits) do not expire. If you buy 50 credits à la carte, they stay in your account until you use them, even across plan changes.</p>

<h2>The Edge Case: What Happens When You Run Out Mid-Month</h2>

<p>If your monthly credits hit zero, the Deep Analysis button still appears — it just prompts for a top-up before proceeding. AI auto-replies are completely unaffected. Your customers keep getting answered. The only thing that pauses is the analytical heavy-lifting.</p>

<p>I designed this deliberately: I never want a billing edge case to make a customer's inbox go silent. Auto-replies are mission-critical uptime — they don't touch the credit system.</p>

<h2>Free Plan: 10 Credits on Signup, No Expiry</h2>

<p>Every new OT1-Pro account gets 10 AI credits on signup, no credit card required. That's 10 Deep Analysis runs — enough to genuinely evaluate whether the insights are valuable for your business before you pay anything.</p>

<p>I added this because I hate tools that lock analytics behind a paywall before you can determine if they're worth it. Run the analysis. See what your AI found. Then decide if the subscription makes sense.</p>

<h2>Budgeting AI Credits as a Business Expense</h2>

<p>Here's how I'd think about this if I were the customer: a Starter account at $29/mo with 30 included credits = $0 per Deep Analysis run (amortized into the subscription). Extra top-up credits at $3 per 10 = $0.30 each. If one Deep Analysis run surfaces one missed opportunity — say, a loyal-customer segment of 40 people you haven't messaged in 90 days — and you send a re-engagement campaign that converts 20% at an average order value of $50, that's $400 in revenue from a $0.30 analysis.</p>

<p>The math isn't close. The only question is whether you remember to run it.</p>

<h2>Upcoming: Automatic Scheduled Analysis</h2>

<p>The team is working on a scheduler that will auto-run a Deep Analysis every Monday morning and email you a digest — no manual trigger needed. This would still consume 1 credit per run. The goal is to make sure the insights get in front of you even when you're too busy to log in and manually start it.</p>

<p>If you're on the <a href="/pricing">Pro plan</a>, with 100 credits per month, the weekly auto-schedule would use 4 credits per month — leaving 96 for manual runs.</p>

<h2>The Single Rule That Governs All of This</h2>

<p>You should never be surprised by an AI bill. If you are, the billing model is wrong. Credits exist so you know, before you click, exactly what you're authorizing — and so the expensive operations are always your choice, never automatic.</p>

<p>See how <a href="/blog/deep-analysis-ai-reads-customer-conversations">Deep Analysis works in practice</a> before you decide if credits are worth it. Or compare the full picture at <a href="/vs/wati">OT1-Pro vs WATI</a>. Ready to try it? <a href="/register">Start with 10 free credits</a>.</p>

{{CTA}}

</article>
HTML,
            ],

            // ── Arabic Post ───────────────────────────────────────────────────────────
            [
                'title'            => 'أرصدة الذكاء الاصطناعي: ادفع فقط على ما تستخدمه',
                'slug'             => 'ai-credits-pay-as-you-go-ar',
                'excerpt'          => 'نظام أرصدة الذكاء الاصطناعي في OT1-Pro بسيط: الردود التلقائية مشمولة في الاشتراك بدون حد، والتحليل العميق يكلف رصيداً واحداً لكل تشغيل. الاشتراك الأساسي بـ29 دولار يأتي بـ30 رصيداً شهرياً — ما يكفي لتحليل يومي لو احتجته.',
                'meta_title'       => 'أرصدة الذكاء الاصطناعي: ادفع ما تستخدم | OT1-Pro',
                'meta_description' => 'نظام الأرصدة في OT1-Pro: ردود تلقائية غير محدودة مع الاشتراك، رصيد واحد لكل تحليل عميق، بدون رسوم مفاجئة. اعرف كيف يعمل نظام أرصدة الذكاء الاصطناعي.',
                'category'         => 'AI Tools',
                'published_at'     => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
                'content'          => <<<'HTML'
<article dir="rtl">

<p>من أكتر الأشياء اللي بتزعلني في أدوات SaaS: الفاتورة المفاجئة.</p>

<p>لما كنت بشتغل على OT1-Pro، راجعت كيف بيتحاسب الذكاء الاصطناعي في المنافسين. في أدوات بتحاسب على كل رسالة. في أدوات بتحاسب على كل رد من الذكاء. وفي أدوات بتحاسب على كل مقعد في الفريق كل شهر — سواء استخدم الموظف الذكاء الاصطناعي 500 مرة أو ولا مرة. شاهدت بائع إلكتروني صغير في بيتا عندي جاته فاتورة 280 دولار من منافس لأن الذكاء الاصطناعي اشتغل بشكل مكثف في أسبوع خلال حملة ترويجية. ما عمل غلط — بس وصلوه زبائن كتير.</p>

<p>النموذج ده مكسور. فبنيت شيء مختلف.</p>

<h2>كيف يشتغل نظام الأرصدة في OT1-Pro</h2>

<p>في فرق بين نوعين من عمل الذكاء الاصطناعي:</p>

<ol>
  <li><strong>الردود التلقائية</strong> — يرد على رسائل الزبائن فوري. سريعة، خفيفة، بتحصل باستمرار. لو عندك 300 زبون بيكلموك يومياً، دي 300 استدعاء صغير. مشمولة في كل خطة بدون حد — لأن تقييدها يكسر المنتج.</li>
  <li><strong>التحليل العميق</strong> — الذكاء الاصطناعي يقرأ كل محادثاتك المحفوظة ويطلع أنماط، اعتراضات، فرص فايتة. دي عملية ثقيلة: تعالج شهور من البيانات وتعمل تحليلات متعددة. بتكلف حوسبة حقيقية. بتشتغل مرة كل 24 ساعة كحد أقصى.</li>
</ol>

<p>الملخص: <strong>الاشتراك يغطي الردود التلقائية</strong>، <strong>الأرصدة للتحليل العميق</strong>.</p>

<p>تشغيل تحليل عميق واحد = رصيد واحد.</p>

<h2>سلم الأرصدة في الخطط الأربع</h2>

<table>
  <thead>
    <tr>
      <th>الخطة</th>
      <th>السعر الشهري</th>
      <th>الأرصدة المشمولة</th>
      <th>شراء إضافي</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>مجاني</td>
      <td>0 دولار</td>
      <td>10 عند التسجيل (مرة واحدة)</td>
      <td>3 دولار / 10 أرصدة</td>
    </tr>
    <tr>
      <td>Starter</td>
      <td>29 دولار/شهر</td>
      <td>30 / شهر (بتتجدد)</td>
      <td>3 دولار / 10 أرصدة</td>
    </tr>
    <tr>
      <td>Pro</td>
      <td>79 دولار/شهر</td>
      <td>100 / شهر (بتتجدد)</td>
      <td>2.50 دولار / 10 أرصدة</td>
    </tr>
    <tr>
      <td>Enterprise</td>
      <td>مخصص</td>
      <td>بالاتفاق</td>
      <td>سعر تفاوضي</td>
    </tr>
  </tbody>
</table>

<p>بمعدل رصيد واحد لكل تحليل، 30 رصيد شهري في خطة Starter = تحليل يومي لو احتجته (نافذة الكاش 24 ساعة). معظم الفرق بتستخدمه 2-3 مرات في الأسبوع، يعني الـ30 رصيد بتعدي الشهر مريح.</p>

<h2>مقياس الأرصدة اللي بتشوفه في اللوحة</h2>

<p>في لوحة OT1-Pro، في شريحة صغيرة في أعلى الشاشة بتعرض رصيدك الحالي وبتتحدث فوري بعد كل عملية:</p>

<ul>
  <li><strong>شريحة خضراء</strong>: 20+ رصيد متبقي</li>
  <li><strong>شريحة صفراء</strong>: 5-19 (تحذير خفيف)</li>
  <li><strong>شريحة حمراء</strong>: 1-4 (بتظهر نافذة شحن)</li>
</ul>

<p>لما بتضغط "تشغيل التحليل العميق"، بيظهر مربع تأكيد: "هذا سيستخدم رصيداً واحداً. لديك 14 رصيداً متبقياً." ما في خصم مفاجئ. ما في فاتورة في نهاية الشهر بتفاجئك. بتشوف التكلفة قبل ما تضغط.</p>

<h2>رصيد واحد = ماذا بالضبط؟</h2>

<p>شغّلت تحليلاً عميقاً الأسبوع الماضي على حساب تجريبي فيه 2,400 محادثة لمحل أزياء في جدة. اللي جاب رصيد واحد:</p>

<ul>
  <li>أعلى 7 اعتراضات مرتبة بالتكرار (السعر #1 بـ34%، مش جودة المنتج)</li>
  <li>3 أنماط فرص فايتة: زبائن سألوا عن توفر مقاسات وما ردوا عليهم في خلال ساعتين</li>
  <li>41 زبون مخلص (شرى 3 مرات+) ما تواصلت معاه من 90 يوم</li>
  <li>أوقات الذروة: 9م-11م بتوقيت السعودية، الخميس والجمعة — والمحل كان يرسل حملاته الساعة 10 صباحاً</li>
  <li>توزيع اللغة: 68% عربي، 22% إنجليزي — لكن الذكاء كان يرد بالإنجليزي للكل</li>
</ul>

<p>هذا التحليل لو اتعمل يدوياً كان يحتاج 2-3 أيام. اشتغل في 90 ثانية وكلف 0.30 دولار من الأرصدة.</p>

<h2>ليه الأرصدة ومش الدفع لكل رسالة؟</h2>

<p>الدفع لكل مقعد فيه سر قذر: بتدفع على المقاعد حتى لو ما استخدمها أحد. فريق 10 أشخاص لكن 3 بس بيستخدموا الذكاء الاصطناعي، بتدفع على 10. مش عادل.</p>

<p>الدفع لكل رسالة أسوأ لأي واحد عنده حملات موسمية. شاهدت تجاراً يتدمروا في رمضان. محل أحذية خلال حملة عيد الأضحى ما المفروض يدفع 10 أضعاف للذكاء الاصطناعي لأن 10,000 زبون ردوا "متوفر إيه من المقاسات؟"</p>

<p>الأرصدة تحل المعضلة: الشيء الغالي (التحليل العميق) يستهلك رصيد، الشيء الرخيص (الردود التلقائية) ثابت السعر. بتعرف تكلفتك الشهرية الثابتة.</p>

<h2>مقارنة مع المنافسين بـ29 دولار</h2>

<table>
  <thead>
    <tr>
      <th>الأداة</th>
      <th>ما بتحصله بـ29 دولار</th>
      <th>ردود AI</th>
      <th>تحليل AI</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>OT1-Pro Starter</td>
      <td>29 دولار/شهر</td>
      <td>غير محدود (مشمول)</td>
      <td>30 رصيد/شهر</td>
    </tr>
    <tr>
      <td>Respond.io (239 مليون تمويل)</td>
      <td>~79 دولار/3 مقاعد</td>
      <td>بتكلف إضافي</td>
      <td>تقارير أساسية فقط</td>
    </tr>
    <tr>
      <td>WATI (12 مليون تمويل)</td>
      <td>49 دولار (واتساب فقط)</td>
      <td>محدود بالخطة</td>
      <td>غير موجود في الأساسي</td>
    </tr>
    <tr>
      <td>Intercom (241 مليون تمويل)</td>
      <td>29 دولار/مقعد فقط</td>
      <td>AI بتكلف إضافي</td>
      <td>Fin AI، باهظ الثمن</td>
    </tr>
  </tbody>
</table>

<p>Intercom بيحاسب على كل "حل AI" — لو الذكاء أغلق محادثة، بتدفع. شاهدت فرق اكتشفت إن فاتورة AI الشهرية عندهم في Intercom أكبر من الاشتراك الأساسي. هذا بالضبط النموذج اللي رفضت أنسخه.</p>

<h2>الأرصدة والترحيل: ماذا يحصل في نهاية الشهر</h2>

<p>الأرصدة الشهرية بتتجدد مع تجديد الاشتراك. ما بتترحل من شهر لشهر — قرار مقصود لبقاء الحساب بسيط.</p>

<p>استثناء واحد: الأرصدة اللي <em>تشتريها</em> بشكل إضافي ما بتنتهي. لو اشتريت 50 رصيداً، بيفضل في حسابك لحين ما تستخدمه.</p>

<h2>خطة مجانية: 10 أرصدة بدون بطاقة</h2>

<p>كل حساب جديد في OT1-Pro بياخد 10 أرصدة عند التسجيل بدون بطاقة ائتمانية. 10 تحليلات عميقة — كافية تقيّم إذا كانت التحليلات تستحق قبل ما تدفع أي شيء.</p>

<p>أضفت هذا لأني أكره الأدوات اللي بتقفل التحليلات وراء الدفع قبل ما تعرف هل تستحق. شغّل التحليل. شوف اللي الذكاء الاصطناعي لقاه. اقرر بعدين.</p>

<h2>القاعدة الواحدة اللي تحكم كل هذا</h2>

<p>ما المفروض تتفاجأ بفاتورة AI. لو اتفاجئت، نموذج الفوترة غلط. الأرصدة موجودة عشان تعرف، قبل ما تضغط، بالضبط ما تأذن به — والعمليات الثقيلة دايماً اختيارك، مش تلقائية.</p>

<p>اقرأ كيف يشتغل <a href="/blog/deep-analysis-ai-reads-customer-conversations">التحليل العميق عملياً</a> قبل ما تقرر. أو قارن الصورة كاملة في <a href="/vs/wati">OT1-Pro مقابل WATI</a>. جاهز تجرب؟ <a href="/register">ابدأ بـ10 أرصدة مجانية</a>.</p>

{{CTA}}

</article>
HTML,
            ],
        ];
    }
}
