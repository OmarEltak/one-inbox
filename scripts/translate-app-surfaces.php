<?php
/**
 * Professional Arabic translations for the internal app surfaces
 * (campaigns, analytics, ai-chat, super-admin billing/page-assignments,
 * settings profile/billing). All MSA, no dialect — these are admin tools.
 */
$arFile = __DIR__ . '/../lang/ar.json';
$ar = json_decode(file_get_contents($arFile), true);

$fixes = [
    // ─── Campaigns ────────────────────────────────────────────
    'Send broadcast messages to your contacts across all platforms.' => 'أرسل رسائل جماعية إلى جهات اتصالك عبر جميع المنصات.',
    'Email Campaign' => 'حملة بريدية',
    'Optional' => 'اختياري',
    'Schedule for later' => 'جدولة لوقت لاحق',
    'Send now' => 'أرسل الآن',
    'Up to 30 days ahead. The campaign stays in Draft/Scheduled state until the scheduled time; the scheduler flips it to Active and starts sending.' => 'حتى 30 يوماً مقدماً. تبقى الحملة في حالة مسودة/مجدولة حتى الوقت المحدد؛ يقوم المُجدوِل بتفعيلها والبدء بالإرسال.',
    'When to send' => 'وقت الإرسال',

    // ─── Analytics ────────────────────────────────────────────
    'AI Automation' => 'أتمتة الذكاء الاصطناعي',
    'AI Avg Response' => 'متوسط رد الذكاء الاصطناعي',
    'AI performance and sales insights' => 'أداء الذكاء الاصطناعي ورؤى المبيعات',
    'AI vs Human Responses' => 'ردود الذكاء الاصطناعي مقابل البشر',
    'Based on' => 'استناداً إلى',
    'Common Objections' => 'الاعتراضات الشائعة',
    'Connect a page to see analytics.' => 'اربط صفحة لعرض التحليلات.',
    'Conversation Status' => 'حالة المحادثة',
    'Conversion Rate' => 'معدل التحويل',
    'Daily Message Volume' => 'حجم الرسائل اليومي',
    'Human Avg Response' => 'متوسط رد البشر',
    'Lead Funnel' => 'مسار العملاء المحتملين',
    'Lead Score Distribution' => 'توزيع درجات العملاء المحتملين',
    'New conversations' => 'محادثات جديدة',
    'No contacts yet' => 'لا توجد جهات اتصال بعد',
    'No messages in this period' => 'لا توجد رسائل في هذه الفترة',
    'No objections recorded' => 'لم تُسجَّل أي اعتراضات',
    'No response data in this period' => 'لا توجد بيانات ردود في هذه الفترة',
    'Open' => 'مفتوحة',
    'Platform Performance' => 'أداء المنصات',
    'Qualified Leads' => 'عملاء محتملون مؤهلون',
    'Reach Across Platforms' => 'الوصول عبر المنصات',
    'Recomputing…' => 'جاري إعادة الحساب…',
    'active conversations' => 'محادثات نشطة',
    'human-handled' => 'يتولاها البشر',
    'in selected period' => 'في الفترة المحددة',
    'responses' => 'رد',

    // ─── AI Chat ──────────────────────────────────────────────
    'Marketing & Analytics Assistant' => 'مساعد التسويق والتحليلات',
    'Manages campaigns, outreach & analytics' => 'يُدير الحملات والتواصل والتحليلات',
    'Powered by AI' => 'مُشغَّل بالذكاء الاصطناعي',
    'I can help you with:' => 'يمكنني مساعدتك في:',
    'Analyzing campaign performance and reply rates' => 'تحليل أداء الحملات ومعدلات الرد',
    'Sending targeted messages to leads by score or status' => 'إرسال رسائل موجّهة للعملاء المحتملين حسب الدرجة أو الحالة',
    'Pausing or resuming campaigns' => 'إيقاف الحملات مؤقتاً أو استئنافها',
    'Identifying hottest leads and engagement opportunities' => 'تحديد أكثر العملاء المحتملين حرارةً وفرص التفاعل',
    'All write actions require your confirmation before executing' => 'جميع إجراءات الكتابة تتطلب تأكيدك قبل التنفيذ',
    'Ask about your analytics...' => 'اسأل عن تحليلاتك...',
    'Confirm action' => 'تأكيد الإجراء',
    'Yes, proceed' => 'نعم، تابع',
    'Running...' => 'جاري التنفيذ...',

    // ─── Super-admin Billing ──────────────────────────────────
    'Billing' => 'الفوترة',
    'Every team by plan lifecycle. Trials, invoices sent, overdue accounts, and receipts pending verification.' => 'كل فريق حسب دورة حياة الباقة. التجارب، الفواتير المُرسَلة، الحسابات المتأخرة، والإيصالات قيد التحقق.',
    'Needs attention' => 'تحتاج إلى انتباه',
    'teams' => 'فريق',
    'Team' => 'الفريق',
    'Owner' => 'المالك',
    'Plan' => 'الباقة',
    'Trial day' => 'يوم التجربة',
    'Payment due' => 'الدفع المستحق',
    'Receipt?' => 'إيصال؟',
    'Actions' => 'الإجراءات',
    'No teams match this filter.' => 'لا توجد فرق مطابقة لهذا التصفية.',
    'Overdue accounts are soft-throttled to' => 'الحسابات المتأخرة مُقيَّدة تدريجياً إلى',
    'AI messages per UTC day — never fully blocked, per plan.' => 'رسالة ذكاء اصطناعي يومياً بتوقيت UTC — لا يتم الحجب الكامل أبداً، حسب الباقة.',
    'Trial' => 'تجربة',
    'Overdue' => 'متأخر',
    'Paid' => 'مدفوع',
    'Cancelled' => 'مُلغى',
    'Pending payment' => 'قيد الدفع',
    'Mark paid' => 'وضع علامة مدفوع',
    'Reset trial' => 'إعادة ضبط التجربة',
    'Cancel this team? They will lose AI access.' => 'إلغاء هذا الفريق؟ سيفقدون الوصول إلى الذكاء الاصطناعي.',

    // ─── Super-admin Page Assignments ─────────────────────────
    'Move pages connected through the OT AI account into the right customer workspace.' => 'انقل الصفحات المتصلة عبر حساب OT AI إلى مساحة العمل الصحيحة للعميل.',
    'Assign To' => 'تعيين إلى',
    'Currently In' => 'حالياً في',
    'Move' => 'نقل',
    'No customers yet' => 'لا يوجد عملاء بعد',
    'No pages found. Connect a Facebook/Instagram/WhatsApp account with OT AI to import pages.' => 'لا توجد صفحات. اربط حساب Facebook/Instagram/WhatsApp مع OT AI لاستيراد الصفحات.',

    // ─── Settings Profile ─────────────────────────────────────
    'Profile' => 'الملف الشخصي',
    'Profile Settings' => 'إعدادات الملف الشخصي',
    'Update your name and email address' => 'حدّث اسمك وبريدك الإلكتروني',
    'Save' => 'حفظ',
    'Saved.' => 'تم الحفظ.',
    'Your email address is unverified.' => 'بريدك الإلكتروني غير مُوثَّق.',
    'Click here to re-send the verification email.' => 'اضغط هنا لإعادة إرسال بريد التوثيق.',
    'A new verification link has been sent to your email address.' => 'تم إرسال رابط توثيق جديد إلى بريدك الإلكتروني.',

    // ─── Settings Billing ─────────────────────────────────────
    'Manage your subscription and billing details' => 'إدارة اشتراكك وتفاصيل الفوترة',
    'Current Plan' => 'الباقة الحالية',
    'Usage' => 'الاستخدام',
    'AI Credits' => 'رصيد الذكاء الاصطناعي',
    'Connected Pages' => 'الصفحات المتصلة',
    'Available Plans' => 'الباقات المتاحة',
    'Upgrade via Wire Transfer' => 'الترقية عبر التحويل البنكي',
    'Unlimited' => 'غير محدود',
    'Subscription activated!' => 'تم تفعيل الاشتراك!',
    'Your plan has been upgraded successfully.' => 'تم ترقية باقتك بنجاح.',
    'Checkout cancelled' => 'تم إلغاء الدفع',
    'Your subscription was not changed.' => 'لم يتم تغيير اشتراكك.',
    'Invoice History' => 'سجل الفواتير',
    'Invoice' => 'فاتورة',
    'Amount' => 'المبلغ',
    'Date' => 'التاريخ',
    'Download' => 'تحميل',

    // ─── New plural-safe billing strings (replace Str::plural) ─
    'Unlimited AI credits / month' => 'رصيد ذكاء اصطناعي غير محدود / شهر',
    ':n AI credits / month' => ':n رصيد ذكاء اصطناعي / شهر',
    'Unlimited connected pages' => 'صفحات متصلة غير محدودة',
    ':count connected page|:count connected pages' => 'صفحة متصلة واحدة|:count صفحات متصلة',

    // ─── Small settings extras ────────────────────────────────
    'Password' => 'كلمة المرور',
    'Settings' => 'الإعدادات',
    'Referrals' => 'الإحالات',
];

foreach ($fixes as $k => $v) {
    $ar[$k] = $v;
}
ksort($ar);
file_put_contents($arFile, json_encode($ar, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");
echo count($fixes) . " app-surface translations merged.\n";
