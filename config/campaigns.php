<?php

return [
    'backpressure_threshold'    => (int) env('CAMPAIGNS_BACKPRESSURE_THRESHOLD', 500),
    'dispatch_ceiling_per_tick' => (int) env('CAMPAIGNS_DISPATCH_CEILING', 50),
    'default_country'           => env('CAMPAIGNS_DEFAULT_COUNTRY', 'EG'),

    /*
    |---------------------------------------------------------------------------
    | Monthly campaign limits per plan (Phase 2 of 5-phase load-management plan
    | in docs/OT1_LIMITS.md §11).
    |---------------------------------------------------------------------------
    |
    | Rolling 30-day window (NOT calendar month) — fairer for new signups.
    | Enforced by Team::canCreateCampaign() at every campaign-create site.
    |
    | Free tier low on purpose:
    |   - Kills the load problem (1 campaigns worker, no per-team throttle)
    |   - Creates a revenue lever (upgrade to run more)
    |
    | Legacy/null plans grandfathered as `free`.
    */
    'monthly_limits' => [
        'free'       => (int) env('CAMPAIGNS_LIMIT_FREE', 1),
        'starter'    => (int) env('CAMPAIGNS_LIMIT_STARTER', 5),
        'pro'        => (int) env('CAMPAIGNS_LIMIT_PRO', 25),
        'enterprise' => (int) env('CAMPAIGNS_LIMIT_ENTERPRISE', PHP_INT_MAX),
    ],
];
