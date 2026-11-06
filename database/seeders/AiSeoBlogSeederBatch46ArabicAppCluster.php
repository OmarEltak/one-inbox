<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

/**
 * Batch 46 — Full Arabic-first app cluster
 * EN + AR posts targeting "Arabic customer inbox MENA" intent
 */
class AiSeoBlogSeederBatch46ArabicAppCluster extends Seeder
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
<h2>Try the AI inbox built for real businesses — in Arabic and English</h2>
<p>OT1-Pro is the unified inbox built for MENA businesses from the ground up: full Arabic RTL UI, AI that replies in Arabic or English based on the customer's language, and every feature described in this post already live. <a href="https://ot1-pro.com/register">Free plan, no credit card →</a></p>
<p><a href="https://ot1-pro.com/pricing">Pricing from $29/mo</a> · <a href="https://ot1-pro.com/vs/wati">Why we beat WATI</a> · <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">The Meta verification guide</a> · <a href="https://wa.me/201026361218">Talk to me on WhatsApp</a></p>
HTML;
    }

    private function ctaAr(): string
    {
        return <<<'HTML'
<h2>جرّب صندوق البريد المبني للشركات الحقيقية — بالعربية والإنجليزية</h2>
<p>OT1-Pro هو صندوق البريد الموحد المبني للشركات في الشرق الأوسط من الأساس: واجهة عربية RTL كاملة، ذكاء اصطناعي يرد بالعربية أو الإنجليزية حسب لغة العميل، وكل ميزة في هذا المقال موجودة بالفعل. <a href="https://ot1-pro.com/register">خطة مجانية، بدون بطاقة ائتمان ←</a></p>
<p><a href="https://ot1-pro.com/pricing">الأسعار من $29/شهر</a> · <a href="https://ot1-pro.com/vs/wati">لماذا نتفوق على WATI</a> · <a href="https://wa.me/201026361218">تحدث معي على واتساب</a></p>
HTML;
    }

    private function posts(): array
    {
        $now = now();

        return [

            // ─────────────────────────────────────────────────────────────
            // 1. Arabic-first App — English
            // ─────────────────────────────────────────────────────────────
            [
                'title'            => 'We Built the Only Customer Inbox That Is Actually Arabic-First — Here Is Why It Matters',
                'slug'             => 'arabic-rtl-customer-inbox-mena-business',
                'excerpt'          => 'Most inbox tools are built in English and Arabic is a painful afterthought — broken RTL layout, AI that replies in formal English to customers writing in Egyptian or Gulf dialect, and error messages no one can read. OT1-Pro was built Arabic-first from the start: full RTL UI, AI that matches the customer\'s dialect, and every string in the app translated.',
                'meta_title'       => 'Arabic-First Customer Inbox for MENA Business',
                'meta_description' => 'OT1-Pro is the unified inbox built for Arabic-speaking businesses: full RTL UI, AI replies in Arabic dialect, every string translated. Arabic customer inbox MENA.',
                'category'         => 'Product news',
                'published_at'     => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
                'content'          => <<<'HTML'
<p>I spent two years using English-language inbox tools to manage a business whose customers are almost entirely Arabic-speaking. The tools were not designed for me. The dashboards were left-to-right in an application where my customers were writing right-to-left Arabic. The AI replied to messages that opened with "السلام عليكم" with "Hello, how can I help you today?" — which is technically not wrong but is immediately recognisable as an automated response in a region where "Hello" is a signal that you are talking to a robot. The error messages when something broke were in English. The documentation was in English. The support was in English. I was running an Arabic business through an English lens, and my customers could feel it.</p>

<h2>What "Arabic-first" actually means in practice</h2>

<p>Building an Arabic-first application is not the same as adding an Arabic translation. Translation is the last step. Arabic-first means the application was designed with Arabic as a first-class language from the beginning. Here is what that looks like in OT1-Pro:</p>

<ol>
<li><strong>Full RTL layout.</strong> The entire UI mirrors for Arabic — navigation on the right, text aligned right, content flowing right-to-left. This is not a CSS toggle applied after the fact. It is a layout system built with logical properties (start/end instead of left/right) so every component works correctly in both directions.</li>
<li><strong>AI replies match the customer's language and register.</strong> When a customer writes "السلام عليكم، عايز أعرف بكام الجاكيت؟", the AI reads Egyptian Arabic and responds in Egyptian Arabic — not formal Modern Standard Arabic, not English. When a customer writes in formal Gulf Arabic, the AI matches that register. When someone switches to English mid-conversation, the AI switches too.</li>
<li><strong>Every user-visible string is translated.</strong> Every button label, every flash message, every validation error, every empty state, every notification — in Arabic. No English strings leaking through the Arabic UI. This matters because a single English error message in an otherwise Arabic interface signals "this tool was not really built for you."</li>
<li><strong>Date formatting uses Arabic numerals and calendar conventions.</strong> Dates displayed to Arabic-language users use the Hijri calendar format where appropriate and Arabic numeral conventions.</li>
<li><strong>Free-text inputs use dir="auto".</strong> When a user types in a text field, the direction adjusts automatically based on whether they are typing Arabic or English. No manual direction switching.</li>
</ol>

<h2>Why most tools fail at this</h2>

<p>Building a truly Arabic-first application is expensive. It requires:</p>

<ul>
<li>A developer who understands RTL layout challenges (most CSS frameworks have RTL support but it requires deliberate implementation — a component built left-to-right does not automatically work in a mirrored layout).</li>
<li>A translator with native Arabic proficiency who understands software UI context — not just someone who can translate words, but someone who knows that "Submit" in Arabic should be "إرسال" not "خضع" and that "Dashboard" is "لوحة التحكم" not a transliteration.</li>
<li>An AI prompt engineering strategy for Arabic — most LLMs are trained heavily on English and their Arabic responses tend toward formal Modern Standard Arabic even when the customer is clearly writing informal dialect. Getting the AI to match Egyptian "ya3ni" or Khaleeji "يعني" or Levantine conversational style requires deliberate training and prompt engineering.</li>
<li>Ongoing maintenance — every new feature needs to ship in both languages simultaneously. This is a discipline of prioritisation, not just a one-time project.</li>
</ul>

<p>Respond.io ($99/mo) is primarily built for English-speaking enterprise teams. WATI ($39/mo) has an Arabic translation but it is inconsistent — several screens remain untranslated in the current version, and the AI replies are exclusively in English. Intercom's Arabic support has been in "coming soon" status for three years.</p>

<h2>The AI language matching in detail</h2>

<p>This is the feature my customers notice most. When someone writes "بكام الجاكيت الأسود؟" in WhatsApp, the AI replies with "الجاكيت الأسود بـ$89، ومتوفر بمقاسات S, M, L, XL. تحب أحجزلك واحد؟" — conversational Egyptian Arabic, same register as the customer. It does not reply "The black jacket is priced at $89 and is available in sizes S, M, L, and XL. Would you like to place an order?"</p>

<p>This distinction matters enormously in MENA markets. The formal English reply signals that you are talking to a system, and systems get ignored. The conversational Arabic reply signals that you are talking to someone who understands you, and that is what builds trust and drives sales.</p>

<p>The AI was trained on real MENA business conversation patterns, including:</p>

<ul>
<li>Price negotiation phrases ("مافيش خصم؟" / "بكام بالجملة؟")</li>
<li>Shipping questions specific to the region ("بيوصل السعودية؟" / "كمبيوتر توصيل لبره مصر؟")</li>
<li>Trust-building exchanges common in Arab commerce ("أنا زبون قديم" / "صاحبي جاب منكم")</li>
<li>Payment method questions specific to MENA ("فين تقدر تدفع فودافون كاش؟" / "في إمكانية STC Pay؟")</li>
</ul>

<h2>The business case for Arabic-first</h2>

<p>Egypt has 104 million people. Saudi Arabia has 36 million. The UAE, Jordan, Kuwait, Bahrain, Qatar — the MENA region represents over 400 million Arabic speakers, the majority of whom conduct business conversations in Arabic. WhatsApp penetration in this region is above 85%. Instagram usage for commerce is among the highest in the world in UAE and Saudi Arabia.</p>

<p>Yet the inbox tools serving this market were built for English-speaking enterprise teams in London and New York. The mismatch is not a subtle difference — it is the difference between a tool that feels native and a tool that feels foreign. Customers can tell.</p>

<h2>What the Arabic app means for your team</h2>

<p>If your team operates in Arabic, the Arabic-first UI means:</p>

<ul>
<li>Onboarding time decreases — your team does not need to interpret an English interface to do their job.</li>
<li>Errors are caught faster — Arabic error messages are read and acted on immediately rather than being skimmed or ignored.</li>
<li>AI training is easier — when you are customising the AI's responses, you can write them in Arabic natively rather than translating English training data into Arabic after the fact.</li>
<li>Customer escalations are smoother — when the AI flags a conversation for human review and a team member picks it up, the entire context is in Arabic and the handover is seamless.</li>
</ul>

<p>See <a href="https://ot1-pro.com/pricing">pricing details</a> (the Arabic UI is on all plans, not an add-on), or <a href="https://ot1-pro.com/vs/wati">compare with WATI on Arabic support</a>. <a href="https://ot1-pro.com/register">Start free →</a></p>

{{CTA}}
HTML,
            ],

            // ─────────────────────────────────────────────────────────────
            // 2. Arabic-first App — Arabic
            // ─────────────────────────────────────────────────────────────
            [
                'title'            => 'بنينا أول صندوق بريد عملاء عربي حقيقي — لماذا يهم ذلك',
                'slug'             => 'arabic-rtl-customer-inbox-ar',
                'excerpt'          => 'معظم أدوات صناديق البريد مبنية بالإنجليزية والعربية فكرة لاحقة مؤلمة — واجهة RTL مكسورة، ذكاء اصطناعي يرد باللغة الإنجليزية الرسمية على عملاء يكتبون باللهجة المصرية أو الخليجية، ورسائل خطأ لا أحد يفهمها. OT1-Pro مبني بالعربية أولاً من البداية.',
                'meta_title'       => 'صندوق بريد عملاء عربي للشركات في الشرق الأوسط',
                'meta_description' => 'OT1-Pro هو صندوق البريد الموحد المبني للأعمال الناطقة بالعربية: واجهة RTL كاملة، ردود الذكاء الاصطناعي باللهجة العربية، كل سطر في التطبيق مُترجَم. صندوق بريد عملاء عربي.',
                'category'         => 'Product news',
                'published_at'     => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
                'content'          => <<<'HTML'
<p>قضيت سنتين أستخدم أدوات صناديق بريد إنجليزية لإدارة عمل عملاؤه عرب بالكامل تقريباً. لم تُصمَّم تلك الأدوات لي. لوحات التحكم كانت من اليسار لليمين في تطبيق يكتب فيه عملاؤي العربية من اليمين لليسار. الذكاء الاصطناعي كان يرد على رسائل تبدأ بـ"السلام عليكم" بـ"Hello, how can I help you today?" — وهو ليس خاطئاً تقنياً، لكنه معروف فوراً كرد آلي في منطقة يعني فيها "Hello" أنك تتحدث مع روبوت. رسائل الخطأ عند حدوث مشكلة كانت بالإنجليزية. التوثيق بالإنجليزية. الدعم بالإنجليزية. كنت أُدير عملاً عربياً عبر عدسة إنجليزية، وعملاؤي كانوا يشعرون بذلك.</p>

<h2>ما الذي تعنيه "العربية أولاً" في الممارسة الفعلية</h2>

<p>بناء تطبيق عربي أولاً ليس إضافة ترجمة عربية. الترجمة هي آخر خطوة. العربية أولاً تعني تصميم التطبيق مع العربية كلغة درجة أولى من البداية. هذا ما يبدو عليه في OT1-Pro:</p>

<ol>
<li><strong>واجهة RTL كاملة.</strong> الواجهة بأكملها تنعكس للعربية — التنقل على اليمين، النص محاذٍ لليمين، المحتوى يتدفق من اليمين لليسار. هذا ليس تبديلاً لـCSS مُضافاً بعد الحقيقة — بل نظام تخطيط مبني بخصائص منطقية.</li>
<li><strong>ردود الذكاء الاصطناعي تُطابق لغة العميل ومستواها.</strong> عندما يكتب عميل "السلام عليكم، عايز أعرف بكام الجاكيت؟"، الذكاء الاصطناعي يقرأ العربية المصرية ويرد بالعربية المصرية — لا بالعربية الفصحى الرسمية، لا بالإنجليزية. عندما يكتب بالخليجية، الذكاء الاصطناعي يُطابق ذاك المستوى.</li>
<li><strong>كل سطر مرئي للمستخدم مُترجَم.</strong> كل زر، كل رسالة وامضة، كل خطأ تحقق، كل حالة فارغة، كل إشعار — بالعربية. لا سطور إنجليزية تتسرّب عبر الواجهة العربية.</li>
<li><strong>تنسيق التاريخ يستخدم الأرقام العربية والأعراف الزمنية.</strong> التواريخ المعروضة للمستخدمين الناطقين بالعربية تستخدم أعراف التقويم والأرقام العربية.</li>
<li><strong>حقول النص الحر تستخدم dir="auto".</strong> عند كتابة المستخدم في حقل نصي، تتكيّف الاتجاه تلقائياً حسب كتابته بالعربية أم الإنجليزية.</li>
</ol>

<h2>لماذا تفشل معظم الأدوات في هذا</h2>

<p>بناء تطبيق عربي حقيقي مكلف. يتطلب:</p>

<ul>
<li>مطوّراً يفهم تحديات تخطيط RTL — معظم أُطر CSS لديها دعم RTL لكنه يتطلب تنفيذاً متعمداً.</li>
<li>مُترجماً ذا كفاءة عربية أصلية يفهم سياق واجهة البرامج — ليس مجرد مترجم كلمات، بل من يعرف أن "Submit" بالعربية هي "إرسال" لا "خضع".</li>
<li>استراتيجية هندسة موجّهات الذكاء الاصطناعي للعربية — معظم نماذج اللغة مدرَّبة بكثافة على الإنجليزية وردودها العربية تميل للفصحى الرسمية حتى عندما يكتب العميل بوضوح باللهجة.</li>
<li>صيانة مستمرة — كل ميزة جديدة تحتاج الشحن بكلتا اللغتين في آنٍ واحد.</li>
</ul>

<p>Respond.io (بـ$99/شهر) مبني أساساً لفرق المؤسسات الناطقة بالإنجليزية. WATI (بـ$39/شهر) لديه ترجمة عربية لكنها غير متسقة — عدة شاشات تظل غير مُترجَمة، وردود الذكاء الاصطناعي إنجليزية حصراً. دعم Intercom العربي في وضع "قريباً" منذ ثلاث سنوات.</p>

<h2>مطابقة لغة الذكاء الاصطناعي بالتفصيل</h2>

<p>هذه الميزة التي يلاحظها عملاؤي أكثر من غيرها. عندما يكتب شخص ما "بكام الجاكيت الأسود؟" على واتساب، يرد الذكاء الاصطناعي: "الجاكيت الأسود بـ$89، ومتوفر بمقاسات S وM وL وXL. تحب أحجزلك واحد؟" — عربية مصرية محادثية، نفس مستوى العميل. لا يرد "The black jacket is priced at $89..."</p>

<p>هذا الفارق جوهري في أسواق الشرق الأوسط. الرد الإنجليزي الرسمي يُشير لأنك تتحدث مع نظام، والأنظمة تُتجاهَل. الرد العربي المحادثي يُشير لأنك تتحدث مع شخص يفهمك، وهذا ما يبني الثقة ويدفع المبيعات.</p>

<p>الذكاء الاصطناعي مُدرَّب على أنماط محادثات حقيقية في أعمال منطقة الشرق الأوسط، تشمل:</p>

<ul>
<li>عبارات تفاوض الأسعار: "مافيش خصم؟" / "بكام بالجملة؟"</li>
<li>أسئلة الشحن الخاصة بالمنطقة: "بيوصل السعودية؟" / "بيتبعث لبره مصر؟"</li>
<li>تبادلات بناء الثقة الشائعة في التجارة العربية: "أنا زبون قديم" / "صاحبي جاب منكم"</li>
<li>أسئلة طرق الدفع الخاصة بالمنطقة: "فيه فودافون كاش؟" / "في إمكانية STC Pay؟"</li>
</ul>

<h2>حالة العمل لصالح العربية أولاً</h2>

<p>مصر 104 مليون نسمة. السعودية 36 مليوناً. الإمارات، الأردن، الكويت، البحرين، قطر — منطقة الشرق الأوسط وشمال أفريقيا تمثّل أكثر من 400 مليون ناطق بالعربية، غالبيتهم يُجرون محادثات أعمال بالعربية. انتشار واتساب في هذه المنطقة يتجاوز 85%. استخدام انستاجرام للتجارة بين الأعلى في العالم في الإمارات والسعودية.</p>

<p>ومع ذلك، أدوات صناديق البريد التي تخدم هذا السوق مبنية لفرق مؤسسات ناطقة بالإنجليزية في لندن ونيويورك. عدم التطابق ليس فارقاً بسيطاً — بل الفارق بين أداة تشعر بأنها محلية وأخرى تشعر بأنها غريبة. العملاء يُحسّون بذلك.</p>

<h2>ما الواجهة العربية تعنيه لفريقك</h2>

<ul>
<li>وقت الإعداد يتقلّص — فريقك لا يحتاج تفسير واجهة إنجليزية لأداء عمله.</li>
<li>الأخطاء تُكتشف بسرعة أكبر — رسائل الخطأ العربية تُقرأ ويُتصرف عليها فوراً.</li>
<li>تدريب الذكاء الاصطناعي أسهل — عند تخصيص ردود الذكاء الاصطناعي، تكتبها بالعربية أصلاً.</li>
<li>تصعيد العملاء أكثر سلاسة — عندما يُعلّم الذكاء الاصطناعي محادثة للمراجعة البشرية، السياق بالكامل بالعربية والتسليم سلس.</li>
</ul>

<p>اطّلع على <a href="https://ot1-pro.com/pricing">تفاصيل الأسعار</a> (الواجهة العربية في جميع الخطط، ليست إضافة)، أو <a href="https://ot1-pro.com/vs/wati">قارننا مع WATI في دعم اللغة العربية</a>. <a href="https://ot1-pro.com/register">ابدأ مجاناً ←</a></p>

{{CTA}}
HTML,
            ],
        ];
    }
}
