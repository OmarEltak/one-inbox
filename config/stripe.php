<?php

return [
    'plans' => [
        'free' => [
            'name' => 'Free',
            'price_id' => null,
            'ai_credits' => 50,
            'pages' => 1,
            'price' => 0,
        ],
        'starter' => [
            'name' => 'Starter',
            'price_id' => env('STRIPE_STARTER_PRICE_ID'),
            'ai_credits' => 500,
            'pages' => 3,
            'price' => 29,
        ],
        'pro' => [
            'name' => 'Pro',
            'price_id' => env('STRIPE_PRO_PRICE_ID'),
            'ai_credits' => 5000,
            'pages' => 10,
            'price' => 79,
        ],
        'business' => [
            'name' => 'Business',
            'price_id' => env('STRIPE_BUSINESS_PRICE_ID'),
            'ai_credits' => 10000,
            'pages' => 15,
            'price' => 199,
        ],
        'agency' => [
            'name' => 'Agency',
            'price_id' => env('STRIPE_AGENCY_PRICE_ID'),
            'ai_credits' => 50000,
            'pages' => -1, // unlimited
            'price' => 499,
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price_id' => env('STRIPE_ENTERPRISE_PRICE_ID'),
            'ai_credits' => -1, // unlimited
            'pages' => -1, // unlimited
            'price' => 0, // custom
        ],
    ],
];
