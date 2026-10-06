<?php

declare(strict_types=1);

/**
 * Plan + credit-economy configuration.
 *
 * Phase RP (2026-10-06): collapsed 6-tier → 4-tier ladder (Free / Starter /
 * Pro / Business). Each plan carries a `limits` sub-array consumed by:
 *   - Team::monthlyCampaignLimit() → bulk_campaigns_monthly
 *   - (future) page connect flow   → pages
 *   - (future) WhatsApp Cloud API  → whatsapp_numbers
 *   - (future) contact storage gate → contacts_stored
 *
 * The old `business` ($199) / `agency` / `enterprise` tiers are REMOVED;
 * the new `business` ($99) replaces the top tier. If any code still refers
 * to those slugs, it must fall back gracefully to Free or Business.
 *
 * `use_legacy_message_counter` controls which quota accounting path
 * EnforcePlanLimits::hasAiCredits uses:
 *   true  → the pre-Phase-A ai_credits_used column (one row per team).
 *   false → the Phase-A AiCredits ledger service (append-only ai_credit_ledger).
 */
return [
    'use_legacy_message_counter' => env('LEGACY_MESSAGE_COUNTER', false),

    /*
    |--------------------------------------------------------------------------
    | Legacy plan aliases
    |--------------------------------------------------------------------------
    |
    | Phase RP collapsed the ladder from 6 → 4 tiers. Any team whose
    | subscription_plan column still holds an old slug (agency, enterprise,
    | legacy business at $199) must be routed to a current tier — otherwise
    | Team::canCreateCampaign() etc silently downgrade them to Free.
    |
    | Lookup helper lives in Team::resolvePlanSlug(). Anywhere that reads
    | `config('plans.plans.' . $team->subscription_plan)` should route through
    | that helper first.
    */
    'legacy_aliases' => [
        'agency'     => 'business',
        'enterprise' => 'business', // old enterprise users get the new top tier
    ],

    'plans' => [
        'free' => [
            'name'       => 'Free',
            'price_id'   => null,
            'price'      => 0,
            'ai_credits' => 100,
            // Mirrored at the top level for backwards-compat with code that
            // still reads `plans.plans.{plan}.pages`. New code should read
            // from the nested `limits` map.
            'pages'      => 1,
            'limits' => [
                'pages'                  => 1,
                'whatsapp_numbers'       => 0,
                'bulk_campaigns_monthly' => 0,
                'contacts_stored'        => 1_000,
            ],
        ],
        'starter' => [
            'name'       => 'Starter',
            'price_id'   => env('STRIPE_STARTER_PRICE_ID'),
            'price'      => 8,
            'ai_credits' => 500,
            'pages'      => 3,
            'limits' => [
                'pages'                  => 3,
                'whatsapp_numbers'       => 1,
                'bulk_campaigns_monthly' => 3,
                'contacts_stored'        => 5_000,
            ],
        ],
        'pro' => [
            'name'       => 'Pro',
            'price_id'   => env('STRIPE_PRO_PRICE_ID'),
            'price'      => 29,
            'ai_credits' => 3_000,
            'pages'      => 8,
            'limits' => [
                'pages'                  => 8,
                'whatsapp_numbers'       => 3,
                'bulk_campaigns_monthly' => 15,
                'contacts_stored'        => 25_000,
            ],
        ],
        'business' => [
            'name'       => 'Business',
            'price_id'   => env('STRIPE_BUSINESS_PRICE_ID'),
            'price'      => 99,
            'ai_credits' => 12_000,
            'pages'      => 20,
            'limits' => [
                'pages'                  => 20,
                'whatsapp_numbers'       => 10,
                'bulk_campaigns_monthly' => 100,
                'contacts_stored'        => 500_000,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Credit Packs (one-off top-ups)
    |--------------------------------------------------------------------------
    |
    | Phase F — surfaced on /settings/billing/top-up. Price in USD, credits are
    | the integer amount granted to the wallet balance after Omar confirms the
    | manual payment via the super-admin grant screen (Phase E).
    |
    | Phase RP (2026-10-06) — Small/Medium/Large = $10/$25/$100 for
    | 1k/3k/15k credits.
    */
    'packs' => [
        'small'  => ['name' => 'Small',  'price' => 10,  'credits' => 1_000],
        'medium' => ['name' => 'Medium', 'price' => 25,  'credits' => 3_000],
        'large'  => ['name' => 'Large',  'price' => 100, 'credits' => 15_000],
    ],

    /*
    |--------------------------------------------------------------------------
    | Manual payment channels
    |--------------------------------------------------------------------------
    |
    | Rendered on /settings/billing/top-up as informational instructions. No
    | card capture — Omar reconciles payments off-platform and credits teams
    | manually via /super-admin/billing.
    */
    'manual_payment' => [
        'paypal_link'      => env('OWNER_PAYPAL_LINK', 'https://paypal.me/omareltak'),
        'bank_iban'        => env('OWNER_BANK_IBAN'),
        'bank_beneficiary' => env('OWNER_BANK_BENEFICIARY'),
        'whatsapp'         => env('OWNER_WHATSAPP', '201026361218'),
    ],
];
