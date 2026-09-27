<?php

declare(strict_types=1);

return [
    'discount_percent' => (float) env('REFERRAL_DISCOUNT_PERCENT', 25),
    'reciprocal_discount_percent' => (float) env('REFERRAL_RECIPROCAL_DISCOUNT_PERCENT', 25),
    'code_prefix' => env('REFERRAL_CODE_PREFIX', ''), // e.g. "OT1"
    'code_length' => (int) env('REFERRAL_CODE_LENGTH', 8),
];
