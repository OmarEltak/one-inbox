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

        // Excel / Google Sheets silently converts long numbers to scientific
        // notation ("2.011E+11") when a column isn't formatted as text. If we
        // let libphonenumber parse this, it will EITHER fail unpredictably OR
        // — worse — round to a valid-looking number and we spam a stranger.
        // Reject before parsing and give the user a diagnosis they can act on.
        if (preg_match('/^\d+(\.\d+)?[eE][+-]?\d+$/', $trimmed) === 1) {
            throw new InvalidPhoneException(
                "Excel corrupted this number into scientific notation ({$raw}). ".
                "Re-export your spreadsheet with the phone column formatted as Text."
            );
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
