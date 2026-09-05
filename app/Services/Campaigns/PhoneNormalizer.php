<?php

declare(strict_types=1);

namespace App\Services\Campaigns;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneNormalizer
{
    private PhoneNumberUtil $util;

    public function __construct()
    {
        $this->util = PhoneNumberUtil::getInstance();
    }

    public function normalize(string $raw, string $defaultCountry): NormalizedPhone
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            throw new InvalidPhoneException('Empty phone value.');
        }

        try {
            $parsed = $this->util->parse($trimmed, strtoupper($defaultCountry));
        } catch (NumberParseException $e) {
            throw new InvalidPhoneException("Unparseable phone: {$raw}", 0, $e);
        }

        if (! $this->util->isValidNumber($parsed)) {
            throw new InvalidPhoneException("Invalid phone: {$raw}");
        }

        return new NormalizedPhone(
            e164: $this->util->format($parsed, PhoneNumberFormat::E164),
            countryIso2: $this->util->getRegionCodeForNumber($parsed) ?? strtoupper($defaultCountry),
        );
    }
}
