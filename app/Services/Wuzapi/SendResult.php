<?php

declare(strict_types=1);

namespace App\Services\Wuzapi;

readonly class SendResult
{
    public function __construct(
        public bool $sent,
        public bool $transient,
        public ?string $providerMessageId,
        public ?string $error,
    ) {}

    public static function ok(?string $id): self
    {
        return new self(true, false, $id, null);
    }

    public static function transient(string $err): self
    {
        return new self(false, true, null, $err);
    }

    public static function permanent(string $err): self
    {
        return new self(false, false, null, $err);
    }
}
