<?php

use App\Models\AiConfig;
use App\Models\Page;

$marker = 'RULE — NO CLOSE WITHOUT PRICE EXPOSURE';

$rule = "\n\n════ {$marker} ════\n"
    . "NEVER close a lead, NEVER push to the final call-to-action (register link, Omar WhatsApp handoff, trial signup, payment link), NEVER tag a conversation as converted — UNLESS the customer has clearly seen at least one concrete pricing tier AND engaged with it (asked follow-up, picked a plan, accepted the price, or confirmed the figure fits their budget).\n"
    . "If pricing has not been surfaced yet in this conversation — surface it FIRST. Quote at least one tier with its monthly cost in the customer's currency. Only after the customer has processed the price can you close.\n"
    . "A lead who has not seen the price is NOT a closable lead. Treat them as still in discovery, keep building value, and introduce the price naturally before any close attempt.\n";

foreach ([8, 10] as $cfgId) {
    $c = AiConfig::find($cfgId);
    $p = Page::find($c->page_id);

    if (str_contains((string) $c->system_prompt, $marker)) {
        echo "cfg#{$c->id} ({$p->name} / {$p->platform}): already has the rule — skipping.\n";
        continue;
    }

    $old = $c->system_prompt ?? '';
    $c->update(['system_prompt' => rtrim($old) . $rule]);
    echo "cfg#{$c->id} ({$p->name} / {$p->platform}): appended. old=" . strlen($old) . " → new=" . strlen($c->fresh()->system_prompt) . " chars.\n";
}
