<?php

declare(strict_types=1);

use App\Services\Email\EmailValidator;

test('accepts valid email addresses', function () {
    expect(EmailValidator::isValid('alice@example.com'))->toBeTrue();
    expect(EmailValidator::isValid('bob.smith+tag@sub.example.co.uk'))->toBeTrue();
});

test('rejects invalid email addresses', function () {
    expect(EmailValidator::isValid(''))->toBeFalse();
    expect(EmailValidator::isValid(null))->toBeFalse();
    expect(EmailValidator::isValid('not-an-email'))->toBeFalse();
    expect(EmailValidator::isValid('a@b'))->toBeFalse();
    expect(EmailValidator::isValid(str_repeat('a', 250).'@x.com'))->toBeFalse();
});

test('normalize lowercases and trims', function () {
    expect(EmailValidator::normalize('  Alice@Example.COM '))->toBe('alice@example.com');
});
