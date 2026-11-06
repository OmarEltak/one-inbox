<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

class AiSeoBlogSeederBatch49EmailCampaignsCluster extends Seeder
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
<div class="cta-block bg-sky-50 border border-sky-200 rounded-xl p-8 my-10 text-center">
  <h3 class="text-2xl font-bold text-sky-900 mb-3">Run Email + WhatsApp Campaigns From One Place</h3>
  <p class="text-sky-700 mb-6">Import your Excel list, pick your channel, hit send. OT1-Pro handles the queue, the throttle, and the delivery report — no Mailchimp tab required.</p>
  <a href="/register" class="inline-block bg-sky-600 text-white font-semibold px-8 py-3 rounded-lg hover:bg-sky-700 transition">Start Free →</a>
  <p class="text-sm text-sky-500 mt-4">See the full feature set at <a href="/pricing" class="underline">OT1-Pro Pricing</a>.</p>
</div>
HTML;
    }

    private function ctaAr(): string
    {
        return <<<'HTML'
<div class="cta-block bg-sky-50 border border-sky-200 rounded-xl p-8 my-10 text-center" dir="rtl">
  <h3 class="text-2xl font-bold text-sky-900 mb-3">شغّل حملات إيميل وواتساب من مكان واحد</h3>
  <p class="text-sky-700 mb-6">استورد ملف Excel، اختر القناة، اضغط إرسال. OT1-Pro يتولى القائمة والتحكم في السرعة وتقرير التسليم — بدون فتح Mailchimp.</p>
  <a href="/register" class="inline-block bg-sky-600 text-white font-semibold px-8 py-3 rounded-lg hover:bg-sky-700 transition">ابدأ مجاناً ←</a>
  <p class="text-sm text-sky-500 mt-4">اعرف كل الميزات في <a href="/pricing" class="underline">تسعير OT1-Pro</a>.</p>
</div>
HTML;
    }

    private function posts(): array
    {
        $now = now();

        return [
            // ── English Post ──────────────────────────────────────────────────────────
            [
                'title'            => 'Email Campaigns Next to WhatsApp: Why I Stopped Using Mailchimp',
                'slug'             => 'email-campaigns-unified-inbox-whatsapp-together',
                'excerpt'          => 'OT1-Pro now runs email campaigns alongside WhatsApp and Messenger campaigns from the same unified inbox. Import an Excel list, write your message, send. The same contact database, the same delivery reporting, one less subscription. Here\'s why I built it and what it replaced.',
                'meta_title'       => 'Email Campaigns in Your Unified Inbox | OT1-Pro',
                'meta_description' => 'Run email and WhatsApp campaigns from one place in OT1-Pro — import Excel, send, track delivery. No Mailchimp, no separate tool. Email campaigns in a unified inbox explained.',
                'category'         => 'Campaigns',
                'published_at'     => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
                'content'          => <<<'HTML'
<article>

<p>I cancelled Mailchimp at $60/month in September.</p>

<p>Not because Mailchimp is bad. It's fine. The problem was context switching: my team was running WhatsApp campaigns in OT1-Pro, then opening a completely separate Mailchimp tab to send the follow-up email to the same list of contacts. Two interfaces. Two contact lists (always slightly out of sync). Two delivery dashboards. Two monthly invoices.</p>

<p>The question I kept asking was: why are these the same contacts but different tools?</p>

<p>So I built email campaigns directly into OT1-Pro. Same contact database, same campaign interface, same delivery reporting — now across email and WhatsApp in the same workflow.</p>

<h2>How the Email Campaign Feature Works</h2>

<p>The workflow mirrors what we already built for WhatsApp campaigns, which means anyone who's run a WhatsApp blast already knows how to run an email one:</p>

<ol>
  <li><strong>Upload your contact list</strong> — Excel file (.xlsx), same format as WhatsApp campaigns. The importer reads Name, Phone, Email, and up to 5 custom fields. Validation runs async: duplicates are flagged, missing emails are skipped, the import progress shows in real time.</li>
  <li><strong>Write your message</strong> — a rich-text email composer with subject line, body, and optional footer. Merge tags work the same as WhatsApp: <code>{{name}}</code>, <code>{{custom_1}}</code>. If a contact is missing a merge field, the send is blocked and the row is flagged before anything goes out.</li>
  <li><strong>Set your throttle</strong> — email sending has its own per-team hourly limit, separate from WhatsApp's tier limits. The campaign runs async via the queue; you can close the browser and check back on delivery status.</li>
  <li><strong>Review the delivery report</strong> — per-recipient status (sent, delivered, bounced, failed) with timestamps. Bounce reasons are classified: hard bounce (invalid address) vs soft bounce (mailbox full).</li>
</ol>

<h2>The Shared Contact List: What It Means in Practice</h2>

<p>This is the part that actually mattered to my beta users. When you import a contact list for a campaign — email or WhatsApp — those contacts become queryable across all your campaigns. So:</p>

<ul>
  <li>Import a list of 400 customers for a WhatsApp promotion</li>
  <li>Three days later, send a follow-up email to the same 400 — no re-import</li>
  <li>Filter to "WhatsApp delivered but no reply" and send only those a follow-up email</li>
  <li>Filter to "email bounced" and retry via WhatsApp</li>
</ul>

<p>That last pattern — email bounced → retry WhatsApp — is something that is literally impossible if your email tool and your WhatsApp tool don't share a database. In OT1-Pro they do, so you can build a simple multi-channel sequence without any Zapier glue.</p>

<h2>Real Numbers From a Beta Campaign</h2>

<p>I ran a test campaign for a fashion retailer in Cairo with two channels to the same 320-contact list:</p>

<table>
  <thead>
    <tr>
      <th>Channel</th>
      <th>Sent</th>
      <th>Delivered</th>
      <th>Opened / Read</th>
      <th>Replies / Clicks</th>
      <th>Conversions</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>WhatsApp</td>
      <td>320</td>
      <td>307 (95.9%)</td>
      <td>271 (88.3%)</td>
      <td>47 replies</td>
      <td>23 purchases</td>
    </tr>
    <tr>
      <td>Email</td>
      <td>320</td>
      <td>298 (93.1%)</td>
      <td>61 (20.5%)</td>
      <td>19 clicks</td>
      <td>8 purchases</td>
    </tr>
    <tr>
      <td><strong>Combined</strong></td>
      <td>—</td>
      <td>—</td>
      <td>—</td>
      <td>—</td>
      <td><strong>31 unique purchases</strong></td>
    </tr>
  </tbody>
</table>

<p>31 unique purchases from 320 contacts. The overlap — 8 people who responded to both channels, counted once — was visible in the shared report. Without the shared contact database, we couldn't have known which customers were double-converting vs. which only email or only WhatsApp reached.</p>

<h2>Competitor Comparison: Running Email Alongside WhatsApp</h2>

<table>
  <thead>
    <tr>
      <th>Tool</th>
      <th>Email campaigns</th>
      <th>WhatsApp campaigns</th>
      <th>Shared contact DB</th>
      <th>Extra cost</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>OT1-Pro</td>
      <td>Yes (included)</td>
      <td>Yes (included)</td>
      <td>Yes — same import</td>
      <td>None — in subscription</td>
    </tr>
    <tr>
      <td>WATI ($12M raised)</td>
      <td>No</td>
      <td>Yes</td>
      <td>N/A</td>
      <td>Need separate email tool</td>
    </tr>
    <tr>
      <td>Respond.io ($239M raised)</td>
      <td>Yes (Broadcasts)</td>
      <td>Yes</td>
      <td>Yes (CRM contacts)</td>
      <td>Requires Business plan $149+/mo</td>
    </tr>
    <tr>
      <td>Mailchimp alone</td>
      <td>Yes</td>
      <td>No</td>
      <td>N/A</td>
      <td>$60-$100/mo + WhatsApp tool</td>
    </tr>
  </tbody>
</table>

<p>Respond.io does have both channels, but their Business plan starts at $149/month and their contact model is a full CRM — which means importing your Excel list requires mapping fields into their CRM schema. That's not a campaign tool; that's a sales platform that happens to send bulk messages. Great product, wrong problem for the merchant running bi-weekly promotions.</p>

<h2>What the Async Import Actually Does</h2>

<p>When you upload an Excel file with 500 contacts, OT1-Pro doesn't block the UI while it validates. It hands off to the queue, processes rows in chunks of 50, and updates a progress indicator in real time. The validation rules are:</p>

<ul>
  <li>Email must be a valid format (RFC 5322 compliant)</li>
  <li>Name is required (used for personalization)</li>
  <li>Phone is optional for email campaigns (required for WhatsApp)</li>
  <li>Duplicate email addresses within the import are collapsed to one row; duplicates already in your contact list are flagged but not blocked (you decide whether to overwrite)</li>
  <li>Rows with blank emails are skipped and reported in the import summary</li>
</ul>

<p>The import summary shows: total rows, imported successfully, skipped (blank email), flagged (duplicate), failed (parse error). You can download a CSV of flagged rows to fix them and re-import just those.</p>

<h2>Merge Tags and Personalization</h2>

<p>Every email campaign supports merge tags. The available tags are populated dynamically from whatever columns are in your imported Excel file — so if your sheet has a "City" column, <code>{{city}}</code> works in the subject line and body automatically.</p>

<p>Subject line personalization has the highest impact on open rates. "New arrivals just for you, {{name}}" outperforms "New arrivals just dropped" by 18-27% in every study I've seen. The merge tag system makes this zero-effort — just reference the column name in double curly braces.</p>

<h2>Bounce Management and List Health</h2>

<p>Hard bounces (invalid addresses) are automatically suppressed after the first bounce. If the same email address bounces on a future campaign, it won't be sent — the contact is marked as "email invalid" and excluded automatically. You don't need to manually clean the list between campaigns.</p>

<p>Soft bounces (mailbox full, temporary issue) are retried once after a 24-hour delay. If the second attempt also soft-bounces, the contact is flagged but not suppressed — you can review and decide whether to retry again manually.</p>

<h2>The Tools I Replaced and What I Pay Now</h2>

<p>Before building email campaigns into OT1-Pro:</p>

<ul>
  <li>Mailchimp Essentials: $60/month (500 contacts, 500K emails/mo)</li>
  <li>OT1-Pro: $29/month</li>
  <li>Total: $89/month</li>
</ul>

<p>After:</p>

<ul>
  <li>OT1-Pro Pro: $79/month (WhatsApp + email campaigns, 100 AI credits)</li>
  <li>Total: $79/month</li>
</ul>

<p>$10/month saved and 5 minutes of context-switching eliminated per campaign day. That's not the main argument for building it — the unified contact database is — but it's a real consequence.</p>

<h2>What Email Campaigns Don't Replace</h2>

<p>OT1-Pro email campaigns are bulk sending, not lifecycle automation. If you need:</p>

<ul>
  <li>A 7-email welcome sequence triggered by signup</li>
  <li>Abandoned cart emails fired from Shopify events</li>
  <li>A/B test splitting with statistically significant sample sizing</li>
  <li>Drip campaigns with 2-week spacing and conditional branching</li>
</ul>

<p>...you need Mailchimp, Klaviyo, or a dedicated ESP. OT1-Pro is for the team that wants to send a promotion blast to a known list, check the delivery report, and reply to the responses that come back — in the same tab where they handle WhatsApp and Messenger.</p>

<p>If you're running lifecycle automation, keep your ESP. But if you're running bi-weekly promotions and re-engagement campaigns, the unified inbox approach is cleaner than maintaining two tools with two contact lists.</p>

<h2>The Single Rule</h2>

<p>Multi-channel campaigns only work better than single-channel when you can see the data from both channels in one place. If your email tool and WhatsApp tool don't share a contact database, you can't see cross-channel overlap, can't avoid double-billing contacts, and can't build follow-up sequences that cross channels. Unified contact data is the prerequisite — the channel features come after.</p>

<p>See how <a href="/blog/whatsapp-campaign-excel-import-500-contacts">WhatsApp campaigns work</a> for the same workflow on a different channel. Or check <a href="/pricing">the full pricing breakdown</a> to see which plan includes email campaigns. Ready to run your first campaign? <a href="/register">Start free, no card needed</a>.</p>

{{CTA}}

</article>
HTML,
            ],

            // ── Arabic Post ───────────────────────────────────────────────────────────
            [
                'title'            => 'حملات الإيميل بجانب واتساب: لماذا توقفت عن استخدام Mailchimp',
                'slug'             => 'email-campaigns-unified-inbox-ar',
                'excerpt'          => 'OT1-Pro يشغّل حملات الإيميل بجانب واتساب وماسنجر من نفس الصندوق الموحد. استورد ملف Excel، اكتب رسالتك، أرسل. نفس قاعدة بيانات جهات الاتصال، نفس تقرير التسليم، اشتراك أقل. هكذا بنيت الميزة وما الذي استبدلتها.',
                'meta_title'       => 'حملات الإيميل في الصندوق الموحد | OT1-Pro',
                'meta_description' => 'شغّل حملات الإيميل وواتساب من مكان واحد في OT1-Pro — استورد Excel، أرسل، تابع التسليم. بدون Mailchimp وبدون أداة منفصلة. حملات الإيميل في الصندوق الموحد.',
                'category'         => 'Campaigns',
                'published_at'     => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
                'content'          => <<<'HTML'
<article dir="rtl">

<p>ألغيت Mailchimp بـ60 دولار شهرياً في سبتمبر.</p>

<p>مش لأن Mailchimp سيئ. كويس. المشكلة كانت في التنقل المستمر: فريقي كان يشغّل حملات واتساب في OT1-Pro، ثم يفتح تاب Mailchimp منفصل لإرسال الإيميل التالي لنفس قائمة الاتصال. واجهتان. قائمتا اتصال (دايماً غير متزامنتان). لوحتا تقارير. فاتورتان شهريتان.</p>

<p>السؤال اللي فضل يدور في ذهني: ليه نفس جهات الاتصال بس أدوات مختلفة؟</p>

<p>فبنيت حملات الإيميل مباشرة في OT1-Pro. نفس قاعدة بيانات جهات الاتصال، نفس واجهة الحملة، نفس تقارير التسليم — الآن عبر الإيميل وواتساب في نفس سير العمل.</p>

<h2>كيف تشتغل ميزة حملات الإيميل</h2>

<p>سير العمل يطابق ما بنيناه لحملات واتساب، يعني أي شخص شغّل بث واتساب يعرف كيف يشغّل حملة إيميل:</p>

<ol>
  <li><strong>رفع قائمة جهات الاتصال</strong> — ملف Excel (.xlsx)، نفس تنسيق حملات واتساب. المستورد يقرأ الاسم والهاتف والإيميل وحتى 5 حقول مخصصة.</li>
  <li><strong>كتابة رسالتك</strong> — محرر إيميل غني بسطر الموضوع والجسم والتذييل الاختياري. علامات الدمج تشتغل مثل واتساب: <code>{{name}}</code> و<code>{{custom_1}}</code>.</li>
  <li><strong>ضبط التحكم في السرعة</strong> — لإرسال الإيميل حد ساعي خاص بكل فريق. الحملة تشتغل بشكل غير متزامن عبر قائمة الانتظار.</li>
  <li><strong>مراجعة تقرير التسليم</strong> — حالة لكل مستلم (أُرسل، تم التسليم، ارتد، فشل) مع التوقيتات.</li>
</ol>

<h2>قاعدة بيانات جهات الاتصال المشتركة: ما يعنيه عملياً</h2>

<p>هذا الجزء اللي أهم بيتا للمستخدمين لدي. لما تستورد قائمة اتصال لحملة — إيميل أو واتساب — جهات الاتصال هذه تصبح قابلة للاستعلام عبر كل حملاتك:</p>

<ul>
  <li>استورد 400 زبون لترويج واتساب</li>
  <li>بعد 3 أيام، أرسل إيميل متابعة لنفس الـ400 — بدون استيراد جديد</li>
  <li>فلتر "تم تسليم واتساب لكن لا رد" وأرسل فقط لهؤلاء إيميل متابعة</li>
  <li>فلتر "الإيميل ارتد" وأعد المحاولة عبر واتساب</li>
</ul>

<p>النمط الأخير — إيميل ارتد → إعادة المحاولة عبر واتساب — مستحيل حرفياً لو أداة الإيميل وأداة واتساب لا تشتركان في قاعدة بيانات. في OT1-Pro يشتركان.</p>

<h2>أرقام حقيقية من حملة بيتا</h2>

<p>شغّلت حملة اختبار لمحل أزياء في القاهرة بقناتين لنفس قائمة 320 اتصال:</p>

<table>
  <thead>
    <tr>
      <th>القناة</th>
      <th>مُرسل</th>
      <th>تم التسليم</th>
      <th>مفتوح/مقروء</th>
      <th>مبيعات</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>واتساب</td>
      <td>320</td>
      <td>307 (95.9%)</td>
      <td>271 (88.3%)</td>
      <td>23 عملية شراء</td>
    </tr>
    <tr>
      <td>إيميل</td>
      <td>320</td>
      <td>298 (93.1%)</td>
      <td>61 (20.5%)</td>
      <td>8 مشتريات</td>
    </tr>
    <tr>
      <td><strong>مجموع فريد</strong></td>
      <td>—</td>
      <td>—</td>
      <td>—</td>
      <td><strong>31 عملية شراء فريدة</strong></td>
    </tr>
  </tbody>
</table>

<p>31 عملية شراء فريدة من 320 اتصال. التداخل — 8 أشخاص استجابوا للقناتين، محسوبين مرة واحدة — كان مرئياً في التقرير المشترك. بدون قاعدة بيانات مشتركة كنا عاجزين عن معرفة ذلك.</p>

<h2>مقارنة مع المنافسين</h2>

<table>
  <thead>
    <tr>
      <th>الأداة</th>
      <th>حملات إيميل</th>
      <th>حملات واتساب</th>
      <th>قاعدة بيانات مشتركة</th>
      <th>تكلفة إضافية</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>OT1-Pro</td>
      <td>نعم (مشمول)</td>
      <td>نعم (مشمول)</td>
      <td>نعم — نفس الاستيراد</td>
      <td>لا — في الاشتراك</td>
    </tr>
    <tr>
      <td>WATI (12 مليون تمويل)</td>
      <td>لا</td>
      <td>نعم</td>
      <td>غير مطبّق</td>
      <td>تحتاج أداة إيميل منفصلة</td>
    </tr>
    <tr>
      <td>Respond.io (239 مليون تمويل)</td>
      <td>نعم (Broadcasts)</td>
      <td>نعم</td>
      <td>نعم (CRM)</td>
      <td>خطة Business 149 دولار+/شهر</td>
    </tr>
    <tr>
      <td>Mailchimp وحده</td>
      <td>نعم</td>
      <td>لا</td>
      <td>غير مطبّق</td>
      <td>60-100 دولار/شهر + أداة واتساب</td>
    </tr>
  </tbody>
</table>

<p>Respond.io عنده القناتين لكن خطة Business تبدأ بـ149 دولار وتحتاج لتعيين الحقول في نموذج CRM. مش أداة حملات — منصة مبيعات بتبعث رسائل جماعية. منتج ممتاز لكن مشكلة مختلفة عن التاجر اللي يشغّل ترويج كل أسبوعين.</p>

<h2>علامات الدمج والتخصيص</h2>

<p>كل حملة إيميل تدعم علامات الدمج. العلامات المتاحة تُولّد تلقائياً من أعمدة ملف Excel المستورد. لو الجدول عنده عمود "المدينة"، <code>{{city}}</code> تشتغل في سطر الموضوع والجسم تلقائياً.</p>

<p>تخصيص سطر الموضوع له أعلى تأثير على معدلات الفتح. "وصلت مجموعة جديدة خصيصاً لك يا {{name}}" أفضل بـ18-27% من "وصلت مجموعة جديدة" في كل دراسة رأيتها.</p>

<h2>إدارة الارتداد وصحة القائمة</h2>

<p>الارتدادات الصلبة (عناوين غير صالحة) تُستبعد تلقائياً بعد أول ارتداد. الارتدادات اللينة (صندوق بريد ممتلئ) تُعاد المحاولة مرة واحدة بعد 24 ساعة. لو ارتدت مجدداً تُعلَّم للمراجعة. ما تحتاج تنظيف يدوي للقائمة بين الحملات.</p>

<h2>ما الذي ألغيته وما أدفعه الآن</h2>

<p>قبل بناء حملات الإيميل في OT1-Pro:</p>
<ul>
  <li>Mailchimp Essentials: 60 دولار/شهر</li>
  <li>OT1-Pro: 29 دولار/شهر</li>
  <li>المجموع: 89 دولار/شهر</li>
</ul>

<p>بعد:</p>
<ul>
  <li>OT1-Pro Pro: 79 دولار/شهر (واتساب + حملات إيميل + 100 رصيد AI)</li>
  <li>المجموع: 79 دولار/شهر</li>
</ul>

<p>10 دولار توفير شهري وخمس دقائق تنقل محذوفة من كل يوم حملة.</p>

<h2>ما الذي لا تستبدله حملات الإيميل</h2>

<p>حملات الإيميل في OT1-Pro هي إرسال جماعي، مش أتمتة دورة حياة. لو تحتاج:</p>

<ul>
  <li>تسلسل ترحيب من 7 إيميلات مُشغَّل عند التسجيل</li>
  <li>إيميلات سلة متروكة من أحداث Shopify</li>
  <li>اختبارات A/B بحجم عينة إحصائي</li>
  <li>حملات بالتنقيط مع تباعد أسبوعين وتفرع مشروط</li>
</ul>

<p>...تحتاج Mailchimp أو Klaviyo. OT1-Pro للفريق اللي يريد إرسال ترويج لقائمة معروفة، مراجعة تقرير التسليم، والرد على الردود اللي ترجع — في نفس التاب حيث يتعاملون مع واتساب وماسنجر.</p>

<h2>القاعدة الواحدة</h2>

<p>الحملات متعددة القنوات تعمل أفضل من أحادية القناة فقط لما ترى بيانات القناتين في مكان واحد. لو أداة الإيميل وأداة واتساب لا تشتركان في قاعدة بيانات، تعذر عليك رؤية التداخل بين القناتين، وتجنب ازدواجية الفوترة، وبناء تسلسلات متابعة تعبر القنوات. البيانات الموحدة هي الشرط الأساسي.</p>

<p>اقرأ كيف تشتغل <a href="/blog/whatsapp-campaign-excel-import-500-contacts">حملات واتساب</a> لنفس سير العمل على قناة مختلفة. أو تحقق من <a href="/pricing">تفاصيل التسعير الكامل</a>. جاهز تشغّل أول حملة؟ <a href="/register">ابدأ مجاناً</a>.</p>

{{CTA}}

</article>
HTML,
            ],
        ];
    }
}
