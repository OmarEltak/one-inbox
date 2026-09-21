<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Campaigns;

use App\Services\Campaigns\InvalidPhoneException;
use App\Services\Campaigns\PhoneNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Regression: Excel/Sheets converts long phone columns to scientific notation
 * on CSV export. If normalize() forwards that to libphonenumber it may round
 * to a valid-looking foreign number and we blast a stranger. Must be rejected
 * with an actionable error message BEFORE the libphonenumber call.
 */
class PhoneNormalizerScientificNotationTest extends TestCase
{
    private PhoneNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new PhoneNormalizer();
    }

    public function test_rejects_uppercase_e_scientific_notation(): void
    {
        $this->expectException(InvalidPhoneException::class);
        $this->expectExceptionMessageMatches('/scientific notation/i');
        $this->normalizer->normalize('2.011E+11', 'EG');
    }

    public function test_rejects_lowercase_e_scientific_notation(): void
    {
        $this->expectException(InvalidPhoneException::class);
        $this->expectExceptionMessageMatches('/scientific notation/i');
        $this->normalizer->normalize('9.71501e+11', 'AE');
    }

    public function test_rejects_scientific_notation_with_leading_dot(): void
    {
        $this->expectException(InvalidPhoneException::class);
        $this->normalizer->normalize('2.01235E+11', 'EG');
    }

    public function test_accepts_valid_e164_that_starts_with_a_leading_digit_before_e_like_zero(): void
    {
        // Sanity: real numbers containing an 'e'-adjacent digit pattern must still parse.
        // "+201099887766" is a real Egyptian mobile — no 'E' in it, must not trip the guard.
        $result = $this->normalizer->normalize('+201099887766', 'EG');
        $this->assertSame('+201099887766', $result->e164);
        $this->assertSame('EG', $result->countryIso2);
    }

    public function test_accepts_local_egyptian_mobile_with_default_country(): void
    {
        $result = $this->normalizer->normalize('01099887766', 'EG');
        $this->assertSame('+201099887766', $result->e164);
    }
}
