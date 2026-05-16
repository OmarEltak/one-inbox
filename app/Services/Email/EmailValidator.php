<?php

declare(strict_types=1);

namespace App\Services\Email;

class EmailValidator
{
    public static function isValid(?string $email): bool
    {
        if ($email === null || $email === '') {
            return false;
        }

        $email = trim($email);
        if (strlen($email) > 254) {
            return false;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function normalize(string $email): string
    {
        return strtolower(trim($email));
    }
}
