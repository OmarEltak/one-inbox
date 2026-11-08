<?php
/**
 * v2 verification — tests the exact scenarios Omar flagged live.
 * Checks: (a) replies include a value-pitch sentence, NOT just a qualifier,
 * (b) website widget gets pitched, (c) all v1 invariants still hold.
 */

use App\Contracts\AiProviderInterface;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;

$page = Page::find(17);
if (!$page || !$page->aiConfig) { echo "page 17 missing — abort\n"; exit(1); }

$tests = [
    'cost_question_live_1'  => 'ما هي تكلفة استخدام OT1-Pro؟',   // Image #6 exact
    'how_subscribe_live_2'  => 'كيف يمكنني الاشتراك في OT1-Pro؟',  // Image #7 exact
    'widget_probe'          => 'هل عندكم widget لموقعي؟',
    'khaleeji_how_to_try'   => 'شلون أقدر أجرب الخدمة؟',
    'ready_to_buy_signal'   => 'تمام اشتركني في AI Pilot',  // MUST surface Omar/WhatsApp
];

$ids = ['c' => [], 'cv' => [], 'm' => []];

try {
    $ai = app(AiProviderInterface::class);

    foreach ($tests as $label => $text) {
        $contact = Contact::create([
            'team_id' => $page->team_id, 'page_id' => $page->id, 'platform' => $page->platform,
            'platform_contact_id' => 'V2_' . bin2hex(random_bytes(6)), 'name' => "T_{$label}",
        ]);
        $ids['c'][] = $contact->id;

        $conv = Conversation::create([
            'team_id' => $page->team_id, 'page_id' => $page->id, 'contact_id' => $contact->id,
            'platform' => $page->platform, 'platform_conversation_id' => 'V2_' . bin2hex(random_bytes(6)),
            'last_message_at' => now(), 'last_message_preview' => $text,
        ]);
        $ids['cv'][] = $conv->id;

        $msg = Message::create([
            'conversation_id' => $conv->id, 'direction' => 'inbound',
            'sender_type' => 'customer', 'content_type' => 'text', 'content' => $text,
        ]);
        $ids['m'][] = $msg->id;

        echo "══════════════════════════════════════════════════════\n";
        echo "TEST [{$label}]  INBOUND: {$text}\n";
        echo "──────────────────────────────────────────────────────\n";

        try {
            $reply = $ai->generateResponse($conv, $msg, $page->aiConfig);
            echo "AI REPLY:\n{$reply}\n\n";

            $flags = [];
            $pitchy = preg_match('/مساعد مبيعات|بيحوّل|بيصنّف|بيرد فوراً|Hot\s*\/\s*Warm\s*\/\s*Cold|٢٤ ساعة|24.?hour/iu', $reply);

            // RULE #1b: price/subscribe questions MUST have a pitch sentence
            if (in_array($label, ['cost_question_live_1', 'how_subscribe_live_2'], true)) {
                if (!$pitchy) {
                    $flags[] = 'NO PITCH SENTENCE — violates RULE #1b (must be value-first, not qualifier-only)';
                }
            }

            // Widget probe MUST mention widget / script / موقعك
            if ($label === 'widget_probe') {
                if (!preg_match('/widget|script|موقعك|موقعك إنت|Shopify|WordPress/iu', $reply)) {
                    $flags[] = 'WIDGET QUESTION NOT ANSWERED — AI must pitch the widget';
                }
            }

            // Ready-to-buy MUST now surface +201026361218 and Omar
            if ($label === 'ready_to_buy_signal') {
                if (!preg_match('/\+?201026361218/', $reply)) {
                    $flags[] = 'READY-TO-BUY — did not share WhatsApp number';
                }
            }

            // All non-buying scenarios: Omar/WhatsApp must NOT appear
            if (!in_array($label, ['ready_to_buy_signal'], true)) {
                if (preg_match('/عمر|Omar|\+?201026361218|201026361218/i', $reply)) {
                    $flags[] = 'MENTIONED OMAR/WHATSAPP WITHOUT BUYING SIGNAL — violates RULE #2';
                }
            }

            // No first-name usage ever
            if (preg_match('/T_(cost|how|widget|khaleeji|ready)/i', $reply)) {
                $flags[] = 'USED CUSTOMER NAME — violates RULE #3';
            }

            // Khaleeji dialect → SAR or AED if price appears
            if ($label === 'khaleeji_how_to_try') {
                if (preg_match('/EGP|جنيه|٣،?٥٠٠|3,?500/i', $reply) && !preg_match('/ريال|SAR|AED|درهم|٤٩٩/i', $reply)) {
                    $flags[] = 'QUOTED EGP TO GULF DIALECT — violates RULE #4';
                }
            }

            $conv->refresh();
            if ($conv->sales_stage !== 'active') {
                $flags[] = "sales_stage={$conv->sales_stage} after 1 msg — auto-escalation leaked";
            }

            echo empty($flags) ? "✅ PASS\n\n" : "⚠️  " . implode(' | ', $flags) . "\n\n";
        } catch (\Throwable $e) {
            echo "AI ERROR: {$e->getMessage()}\n\n";
        }
    }
} finally {
    echo "──── CLEANUP ────\n";
    if ($ids['m']) Message::whereIn('id', $ids['m'])->delete();
    if ($ids['cv']) Conversation::whereIn('id', $ids['cv'])->delete();
    if ($ids['c']) Contact::whereIn('id', $ids['c'])->delete();
    echo "cleaned " . count($ids['m']) . " messages, " . count($ids['cv']) . " convs, " . count($ids['c']) . " contacts\n";
}
