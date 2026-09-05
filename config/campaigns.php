<?php

return [
    'backpressure_threshold'    => (int) env('CAMPAIGNS_BACKPRESSURE_THRESHOLD', 500),
    'dispatch_ceiling_per_tick' => (int) env('CAMPAIGNS_DISPATCH_CEILING', 50),
    'default_country'           => env('CAMPAIGNS_DEFAULT_COUNTRY', 'EG'),
];
