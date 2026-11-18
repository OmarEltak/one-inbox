<?php
/**
 * Round 10 — Rule #10: recognise returning customers.
 *
 * Scenario: customer places order → days later messages asking "where is
 * my order?" + re-sends their name/phone (sometimes a DIFFERENT phone from
 * the one they used first). Previously the AI treated this as a brand-new
 * order and re-confirmed, creating a duplicate row.
 *
 * Shipped layer 1 (SalesConnectors lifetime dedupe) — DB will NEVER push a
 * duplicate row for the same conversation.
 *
 * This script adds layer 2 (AI behaviour) — AI itself recognises the
 * customer and redirects to a status/handoff reply instead of re-confirming.
 */

use App\Models\AiConfig;

$rule10 = <<<'AR'

══ قاعدة #10 — العميل الراجع (ممنوع إعادة تأكيد أوردر مؤكد مسبقًا) ══
لو العميل أكد أوردر قبل كده في نفس المحادثة (موجود في الـ context "سجلت الأوردر" أو فيه تأكيد سابق)، ممنوع تمامًا تعيد تأكيد أوردر جديد عليه، حتى لو:
  × بعت رقم تليفون جديد (مختلف عن اللي باعته أول مرة)
  × بعت اسم تاني أو عنوان بتفصيل مختلف
  × قال "محتاج أوردر" أو "عايز اطلب" مرة تانية

في الحالات دي، العميل بيتابع على أوردره القديم — مش بيطلب أوردر جديد.

الرد الصحيح:
  "حضرتك عندك أوردر سجلناه مسبقًا 🤍 — [المنتج + اللون + المقاس من الأوردر السابق]. أنا هبلغ الإدارة فورًا يتابعوا معاك على حالة الشحن. ولو حابة فعلاً تطلبي أوردر إضافي مختلف، قولّي المنتج وهنعمله منفصل."

الرد الغلط (ممنوع):
  × "تمام يا فندم ✅ سجلت الأوردر" — لأنه هيعمل سطر جديد في سجل الأوردرات ومش هيعرف صاحب المتجر إن ده نفس العميل
  × "رقمك الجديد اتسجل" — تحديث بيانات العميل القديم مش عمل أوردر جديد

لو العميل حرفيًا بيطلب أوردر إضافي (ليس متابعة)، وضح الفرق:
  "تمام يا فندم — ده أوردر جديد منفصل غير اللي سجلته قبل كده صح؟ عشان أأكد إنه ما يتخلطش مع الأول."

السبب: صاحب المتجر بيحسب كل سطر في الجدول كأوردر مستقل. سطر مكرر لنفس العميل بيعمل ارتباك، شحن مجدول مرتين، ومكالمة محرجة من المندوب.

AR;

foreach ([17, 18, 19] as $cfgId) {
    $cfg = AiConfig::find($cfgId);
    if (! str_contains($cfg->system_prompt, 'قاعدة #10 — العميل الراجع')) {
        $cfg->update(['system_prompt' => rtrim($cfg->system_prompt) . "\n" . $rule10]);
        echo "  cfg#{$cfg->id}: Rule #10 appended.\n";
    } else {
        echo "  cfg#{$cfg->id}: Rule #10 already present.\n";
    }
}

echo "\nDONE — Rule #10 live on all 3 Lavin pages.\n";
