<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Campaigns;

use App\Services\Campaigns\InvalidPhoneException;
use App\Services\Campaigns\PhoneNormalizer;
use PHPUnit\Framework\TestCase;

class PhoneNormalizerTest extends TestCase
{
    private PhoneNormalizer $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = new PhoneNormalizer();
    }

    public function test_normalizes_egyptian_local_format(): void
    {
        $result = $this->sut->normalize('01026361218', 'EG');
        $this->assertSame('+201026361218', $result->e164);
        $this->assertSame('EG', $result->countryIso2);
    }

    public function test_accepts_e164_input(): void
    {
        $result = $this->sut->normalize('+971501234567', 'EG');
        $this->assertSame('+971501234567', $result->e164);
        $this->assertSame('AE', $result->countryIso2);
    }

    public function test_strips_whitespace_and_dashes(): void
    {
        $result = $this->sut->normalize('+20 102 636 1218', 'EG');
        $this->assertSame('+201026361218', $result->e164);
    }

    public function test_rejects_garbage(): void
    {
        $this->expectException(InvalidPhoneException::class);
        $this->sut->normalize('not-a-phone', 'EG');
    }

    public function test_rejects_too_short(): void
    {
        $this->expectException(InvalidPhoneException::class);
        $this->sut->normalize('123', 'EG');
    }
}
