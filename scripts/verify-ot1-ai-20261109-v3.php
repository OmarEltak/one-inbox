<?php

declare(strict_types=1);

/**
 * v3 verification — tests the new founder-voice rules against the exact
 * failure-mode scenarios the audit surfaced.
 */

use App\Contracts\AiProviderInterface;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;

$page = Page::find(17);
if (! $page || ! $page->aiConfig) {
    echo "page 17 missing — abort\n";
    exit(1);
}

$tests = [
    // Must trigger the founder opener + 4-benefit block
    'what_do_you_do'        => 'ماذا يفعل الذكاء الاصطناعي لنشاطي التجاري؟',

    // Must trigger the founder opener + 3-plans + free trial mention
    'price_question'        => 'كم السعر؟',
    'how_subscribe'         => 'كيف يمكنني الاشتراك في OT1-Pro؟',

    // Must infer SAR + show 3 plans in SAR, NOT ask "Egypt or Gulf"
    'khaleeji_price'        => 'شلون أقدر أجرب الخدمة؟ وكم السعر؟',

    // Regression: the RULE #2 leak — "كيف الاشتراك" must NOT surface WhatsApp
    'subscribe_no_whatsapp' => 'كيف يمكنني الاشتراك؟',

    // RULE #5 forbidden context: job inquiry must NOT share WhatsApp
    'job_inquiry'           => 'السلام عليكم هل متوفر وظائف خدمة عملاء عن بعد؟',

    // Explicit buying signal — WhatsApp SHOULD surface
    'ready_to_buy'          => 'تمام اشتركني في AI Pilot',

    // Casual thumbs-up — should NOT hallucinate business type
    'thumbs_up'             => '👍',
];

$ids = ['c' => [], 'cv' => [], 'm' => []];

try {
    $ai = app(AiProviderInterface::class);

    foreach ($tests as $label => $text) {
        $contact = Contact::create([
            'team_id'              => $page->team_id,
            'page_id'              => $page->id,
            'platform'             => $page->platform,
            'platform_contact_id'  => 'V3_' . bin2hex(random_bytes(6)),
            'name'                 => "T_{$label}",
        ]);
        $ids['c'][] = $contact->id;

        $conv = Conversation::create([
            'team_id'                  => $page->team_id,
            'page_id'                  => $page->id,
            'contact_id'               => $contact->id,
            'platform'                 => $page->platform,
            'platform_conversation_id' => 'V3_' . bin2hex(random_bytes(6)),
            'last_message_at'          => now(),
            'last_message_preview'     => $text,
        ]);
        $ids['cv'][] = $conv->id;

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'direction'       => 'inbound',
            'sender_type'     => 'customer',
            'content_type'    => 'text',
            'content'         => $text,
        ]);
        $ids['m'][] = $msg->id;

        echo "══════════════════════════════════════════════════════\n";
        echo "TEST [{$label}]  INBOUND: {$text}\n";
        echo "──────────────────────────────────────────────────────\n";

        try {
            $reply = $ai->generateResponse($conv, $msg, $page->aiConfig);
            echo "AI REPLY:\n{$reply}\n\n";

            $flags = [];

            // RULE #1 — founder opener on first substantive reply
            $openerQuestions = ['what_do_you_do', 'price_question', 'how_subscribe', 'khaleeji_price', 'subscribe_no_whatsapp'];
            if (in_array($label, $openerQuestions, true)) {
                $hasFounderOpener = preg_match('/أنا عمر|I\'m Omar|من OT1-Pro|OT1-Pro founder|مؤسس OT1-Pro/iu', $reply);
                if (! $hasFounderOpener) {
                    $flags[] = 'NO founder opener — violates RULE #1 (must open with "أنا عمر من OT1-Pro")';
                }
            }

            // RULE #2 — 4-benefit block on "what do you do"
            if ($label === 'what_do_you_do') {
                $emojiCount = preg_match_all('/💰|🎯|💬|📊/u', $reply);
                if ($emojiCount < 3) {
                    $flags[] = 'MISSING 4-benefit emoji block — expected 💰🎯💬📊 (got ' . $emojiCount . ')';
                }
            }

            // RULE #3 — price questions show ALL 3 plans + free trial
            if (in_array($label, ['price_question', 'khaleeji_price'], true)) {
                $hasPilot   = preg_match('/Pilot|٣،?٥٠٠|3,?500|149|٤٩٩|499/iu', $reply);
                $hasPartner = preg_match('/Founding Partner|Partner|٧،?٥٠٠|7,?500|499/iu', $reply);
                $hasWG      = preg_match('/White.?Glove|٢٥،?٠٠٠|25,?000|1,?499|1499/iu', $reply);
                $tiers = (int) $hasPilot + (int) $hasPartner + (int) $hasWG;
                if ($tiers < 3) {
                    $flags[] = 'MISSING plans — only ' . $tiers . ' of 3 tiers mentioned (RULE #3 requires all 3)';
                }
                $hasFreeTrial = preg_match('/تجربة مجانية|يومين مجاناً?|2.?day free|free trial|مجاناً|مجاناً يومين/iu', $reply);
                if (! $hasFreeTrial) {
                    $flags[] = 'MISSING free-trial fallback (RULE #3 requires it after the 3 plans)';
                }
            }

            // RULE #4 — must NEVER ask "Egypt or Gulf"
            if (preg_match('/مصر\s*(ولا|or|أو)\s*الخليج|Egypt\s+or\s+Gulf|مصر\s+أو\s+الإمارات/iu', $reply)) {
                $flags[] = 'ASKED "Egypt or Gulf" — violates RULE #6';
            }

            // RULE #4 (Khaleeji) — Gulf dialect → should include SAR, not EGP-only
            if ($label === 'khaleeji_price') {
                if (! preg_match('/ريال|SAR|AED|درهم/iu', $reply)) {
                    $flags[] = 'KHALEEJI — missing SAR/AED (RULE #6 dialect→currency)';
                }
            }

            // RULE #5 — no WhatsApp number except on explicit buying signal
            if (! in_array($label, ['ready_to_buy'], true)) {
                if (preg_match('/\+?201026361218|\+?20\s*10\s*2636\s*1218/', $reply)) {
                    $flags[] = 'SHARED WhatsApp +201026361218 without buying signal — violates RULE #5';
                }
            }
            // ready_to_buy MUST include the WhatsApp
            if ($label === 'ready_to_buy') {
                if (! preg_match('/\+?201026361218/', $reply)) {
                    $flags[] = 'READY-TO-BUY — did not share WhatsApp number';
                }
            }

            // RULE #12 — job inquiry must redirect to info@ot1-pro.com, not WhatsApp
            if ($label === 'job_inquiry') {
                if (preg_match('/\+?201026361218/', $reply)) {
                    $flags[] = 'JOB INQUIRY leaked WhatsApp — violates RULE #12';
                }
                $hasEmailRedirect = preg_match('/info@ot1-pro\.com/i', $reply);
                if (! $hasEmailRedirect) {
                    $flags[] = 'JOB INQUIRY did not redirect to info@ot1-pro.com';
                }
            }

            // RULE #7 — no first-name usage
            if (preg_match('/T_(what|price|how|khaleeji|subscribe|job|ready|thumbs)/i', $reply)) {
                $flags[] = 'USED customer first name — violates RULE #7';
            }

            // Sanity on sales_stage
            $conv->refresh();
            if ($conv->sales_stage !== 'active') {
                $flags[] = "sales_stage={$conv->sales_stage} after single inbound — auto-escalation leaked";
            }

            echo empty($flags) ? "✅ PASS\n\n" : "⚠️  " . implode(' | ', $flags) . "\n\n";
        } catch (\Throwable $e) {
            echo "AI ERROR: {$e->getMessage()}\n\n";
        }
    }
} finally {
    echo "──── CLEANUP ────\n";
    if ($ids['m']) {
        Message::whereIn('id', $ids['m'])->delete();
    }
    if ($ids['cv']) {
        Conversation::whereIn('id', $ids['cv'])->delete();
    }
    if ($ids['c']) {
        Contact::whereIn('id', $ids['c'])->delete();
    }
    echo 'cleaned ' . count($ids['m']) . ' messages, ' . count($ids['cv']) . ' convs, ' . count($ids['c']) . " contacts\n";
}
