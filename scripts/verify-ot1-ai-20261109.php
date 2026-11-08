<?php
/**
 * 2026-11-09 — VERIFY the new OT1-Pro AI behavior against the 5 scenarios
 * Omar flagged as the ones a real customer would ask, PLUS the Omar-mention
 * regression scenario (the exact bug that caused this rewrite).
 *
 * Nothing persists past this run — temp Contact/Conversation/Message rows
 * are hard-deleted in finally{}. No Meta dispatch. Burns AI credits for
 * the generateResponse calls (expected).
 *
 * Run on prod:
 *   cd /var/www/ot1-pro.com
 *   sudo -u deploy HOME=/home/deploy XDG_CONFIG_HOME=/home/deploy/.config \
 *     php artisan tinker --execute="require 'scripts/verify-ot1-ai-20261109.php';"
 */

use App\Contracts\AiProviderInterface;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;

$page = Page::find(17); // OT1-Pro FB (AiConfig 8)
if (!$page || !$page->aiConfig) {
    echo "page 17 or its AiConfig missing — abort\n";
    exit(1);
}

$tests = [
    // User's 5 scenarios
    'what_does_ai_do_ar'   => 'ماذا يفعل الذكاء الاصطناعي لنشاطي التجاري؟',
    'setup_time_ar'        => 'كم يستغرق الإعداد؟',
    'platforms_en'         => 'Which platforms does it work with?',
    'price_ar'             => 'كم السعر؟',   // MUST not quote without qualifying first
    'try_before_commit_en' => 'Can I try it before I commit?',

    // Regression scenarios (the bugs that drove the rewrite)
    'who_is_omar_ar'       => 'من هو عمر؟',   // MUST NOT auto-escalate; MUST answer naturally
    'where_from_khaleeji'  => 'شلون أقدر أجرب الخدمة؟',  // Gulf dialect — should quote in SAR/AED, NOT ask country
    'first_contact_hi'     => 'اهلا',  // MUST NOT use first name; MUST NOT mention Omar
];

$createdIds = ['contacts' => [], 'conversations' => [], 'messages' => []];

try {
    $ai = app(AiProviderInterface::class);

    foreach ($tests as $label => $text) {
        $contact = Contact::create([
            'team_id'              => $page->team_id,
            'page_id'              => $page->id,
            'platform'             => $page->platform,
            'platform_contact_id'  => 'TEST_VERIFY_' . bin2hex(random_bytes(6)),
            'name'                 => "TestContact_{$label}",
        ]);
        $createdIds['contacts'][] = $contact->id;

        $conv = Conversation::create([
            'team_id'                  => $page->team_id,
            'page_id'                  => $page->id,
            'contact_id'               => $contact->id,
            'platform'                 => $page->platform,
            'platform_conversation_id' => 'TEST_VERIFY_' . bin2hex(random_bytes(6)),
            'last_message_at'          => now(),
            'last_message_preview'     => $text,
        ]);
        $createdIds['conversations'][] = $conv->id;

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'direction'       => 'inbound',
            'sender_type'     => 'customer',
            'content_type'    => 'text',
            'content'         => $text,
        ]);
        $createdIds['messages'][] = $msg->id;

        echo "════════════════════════════════════════════════\n";
        echo "TEST [{$label}]  INBOUND: {$text}\n";
        echo "────────────────────────────────────────────────\n";

        try {
            $reply = $ai->generateResponse($conv, $msg, $page->aiConfig);
            echo "AI REPLY:\n{$reply}\n\n";

            // Heuristic checks — flag violations of the new rules
            $flags = [];

            // RULE #1 — price without pain-reflection + question
            $priceMentioned = preg_match('/\b3,?500\b|\b٣،?٥٠٠\b|\bPilot\b|\bFounding Partner\b|\bWhite[- ]Glove\b|\b499\b|\b٤٩٩\b|\$149\b/i', $reply);
            if ($priceMentioned) {
                $hasQuestion = str_contains($reply, '؟') || str_contains($reply, '?');
                if (!$hasQuestion) {
                    $flags[] = 'PRICE WITHOUT QUESTION — violates RULE #1';
                }
                $tiersMentioned = 0;
                if (preg_match('/Pilot|٣،?٥٠٠|3,?500|\$149|499/i', $reply)) $tiersMentioned++;
                if (preg_match('/Founding Partner|7,?500|٧،?٥٠٠|\$499|1,?499/i', $reply)) $tiersMentioned++;
                if (preg_match('/White.?Glove|25,?000|٢٥،?٠٠٠|\$1,?499/i', $reply)) $tiersMentioned++;
                if ($tiersMentioned > 1) {
                    $flags[] = "LISTED {$tiersMentioned} TIERS AT ONCE — violates 'quote ONE only'";
                }
            }

            // RULE #2 — Omar / WhatsApp mentioned when no buying signal
            $noBuyingSignalScenarios = ['what_does_ai_do_ar', 'setup_time_ar', 'platforms_en', 'price_ar', 'try_before_commit_en', 'first_contact_hi', 'where_from_khaleeji'];
            if (in_array($label, $noBuyingSignalScenarios, true)) {
                if (preg_match('/عمر|Omar|founder|مؤسس|\+?201026361218|201026361218/i', $reply)) {
                    $flags[] = 'MENTIONED OMAR / WHATSAPP WITHOUT BUYING SIGNAL — violates RULE #2';
                }
            }
            // For 'who_is_omar' it's OK to mention Omar in the ANSWER (explaining who he is),
            // but it MUST NOT be the primary CTA / handoff.

            // RULE #3 — using customer's first name
            if ($label === 'first_contact_hi') {
                // The contact name in our fixture is "TestContact_first_contact_hi"
                if (preg_match('/TestContact_/i', $reply)) {
                    $flags[] = 'USED CUSTOMER NAME IN REPLY — violates RULE #3';
                }
            }

            // RULE #4 — asking "Egypt or Gulf"
            if (preg_match('/مصر\s*(ولا|or|أو)\s*الخليج|Egypt\s+or\s+Gulf|Egypt\s+or\s+UAE|مصر\s+أو\s+الإمارات/i', $reply)) {
                $flags[] = 'ASKED "EGYPT OR GULF" EXPLICITLY — violates RULE #4';
            }

            // RULE #5 — 1-2 sentences max (soft check, >800 chars = certain violation)
            if (mb_strlen($reply) > 800) {
                $flags[] = 'LONG REPLY (' . mb_strlen($reply) . ' chars) — probably violates "1-2 short sentences"';
            }

            // Scenario-specific: Khaleeji dialect → should quote in SAR or AED if price appears
            if ($label === 'where_from_khaleeji' && $priceMentioned) {
                if (!preg_match('/SAR|ريال|AED|درهم|٤٩٩/i', $reply) && preg_match('/EGP|جنيه|٣،?٥٠٠|3,?500/i', $reply)) {
                    $flags[] = 'QUOTED EGP TO GULF-DIALECT CUSTOMER — should be SAR/AED';
                }
            }

            // Scenario-specific: conversation did NOT auto-escalate (would require DB check)
            $conv->refresh();
            if ($conv->sales_stage !== 'active') {
                $flags[] = "CONVERSATION SALES_STAGE = {$conv->sales_stage} after single message (expected 'active' — auto-escalation leaked)";
            }

            if (!empty($flags)) {
                echo "⚠️  RULE VIOLATIONS: " . implode(' | ', $flags) . "\n\n";
            } else {
                echo "✅ No obvious rule violations\n\n";
            }
        } catch (\Throwable $e) {
            echo "AI ERROR: " . $e->getMessage() . "\n\n";
        }
    }
} finally {
    echo "──── CLEANUP ────\n";
    if ($createdIds['messages']) {
        Message::whereIn('id', $createdIds['messages'])->delete();
        echo "deleted " . count($createdIds['messages']) . " test messages\n";
    }
    if ($createdIds['conversations']) {
        Conversation::whereIn('id', $createdIds['conversations'])->delete();
        echo "deleted " . count($createdIds['conversations']) . " test conversations\n";
    }
    if ($createdIds['contacts']) {
        Contact::whereIn('id', $createdIds['contacts'])->delete();
        echo "deleted " . count($createdIds['contacts']) . " test contacts\n";
    }
    echo "DONE. No Meta dispatch triggered; nothing persisted.\n";
}
