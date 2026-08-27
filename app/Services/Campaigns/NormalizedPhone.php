<?php

declare(strict_types=1);

namespace App\Services\Campaigns;

readonly class NormalizedPhone
{
    public function __construct(
        public string $e164,
        public string $countryIso2,
    ) {}
}
