<?php

return [
    'backpressure_threshold'    => (int) env('CAMPAIGNS_BACKPRESSURE_THRESHOLD', 500),
    'dispatch_ceiling_per_tick' => (int) env('CAMPAIGNS_DISPATCH_CEILING', 50),
    'default_country'           => env('CAMPAIGNS_DEFAULT_COUNTRY', 'EG'),

    /*
    |---------------------------------------------------------------------------
    | DEPRECATED — Phase RP (2026-10-06).
    |---------------------------------------------------------------------------
    |
    | Team::monthlyCampaignLimit() now reads from
    | `config('plans.plans.{plan}.limits.bulk_campaigns_monthly')`. This key is
    | retained for any external code / scripts that may still read it, but it
    | is no longer the source of truth. Do not add new readers.
    */
    'monthly_limits' => [
        'free'       => (int) env('CAMPAIGNS_LIMIT_FREE', 1),
        'starter'    => (int) env('CAMPAIGNS_LIMIT_STARTER', 5),
        'pro'        => (int) env('CAMPAIGNS_LIMIT_PRO', 25),
        'enterprise' => (int) env('CAMPAIGNS_LIMIT_ENTERPRISE', PHP_INT_MAX),
    ],
];
