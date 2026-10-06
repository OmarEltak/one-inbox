<?php

declare(strict_types=1);

/**
 * Per-action AI credit cost table (spec §3.5).
 *
 * Keys here are canonical action names used by callers of
 * AiCredits::charge($team, $action, ...). Missing keys fall back to
 * AiCredits::DEFAULT_COST (= 1) — see App\Services\Billing\AiCredits::costFor().
 *
 * Lookup path: `config("ai_costs.{$action}")`.
 *
 * Tunable without code changes: edit the number and `php artisan config:cache`.
 * The scaling helpers (deep_analysis_per_100_contacts, deep_analysis_minimum)
 * are read by DeepAnalysisService::quote(); straight per-action costs are
 * resolved by AiCredits::costFor().
 */
return [
    // --- Chat + reply (single-shot actions) ---
    'ai_chat_turn'      => 1, // operator asks AiChat a question
    'ai_reply_outbound' => 1, // AI responds to a customer message
    'ai_lead_score'     => 0, // free — we eat the per-inbound scoring cost
    'ai_comment_reply'  => 1, // Facebook/Instagram post comment reply

    // --- Deep Analysis scaling knobs (spec §5) ---
    // Final cost = max(deep_analysis_minimum, ceil(cohort_size / 100) * deep_analysis_per_100_contacts).
    'deep_analysis_per_100_contacts' => 5,
    'deep_analysis_minimum'          => 10,

    // --- Agent audit (Phase H) — additive on top of per-100 scaling ---
    'agent_audit' => 3,

    // --- Phase RP (2026-10-06): bulk campaigns + WhatsApp Meta API ---
    // Charged per outgoing recipient in SendCampaignWhatsAppJob /
    // SendCampaignEmailJob after a successful send (idempotency keyed on
    // campaign:{id}:recipient:{id}).
    'bulk_campaign_recipient'     => 1,
    // Reserved for future WhatsApp Meta Cloud API per-conversation charging
    // (one charge per 24h customer-service window). No call site yet — see
    // the Deferred section in docs/superpowers/plans/2026-10-06-pricing-repricing.md.
    'whatsapp_meta_conversation'  => 10,
];
