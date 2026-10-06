<?php

declare(strict_types=1);

namespace App\Services\Billing;

/**
 * Immutable snapshot of a team's AI credit balance at a point in time.
 *
 * Two balances per spec §3.1:
 *   - monthly: plan allowance; drained first; use-it-or-lose-it on reset.
 *   - wallet:  purchased packs / manual grants; drained after monthly; never expires.
 */
final readonly class Balance
{
    public function __construct(
        public int $monthly,
        public int $wallet,
    ) {
    }

    public function total(): int
    {
        return $this->monthly + $this->wallet;
    }

    /**
     * @return array{monthly: int, wallet: int, total: int}
     */
    public function toArray(): array
    {
        return [
            'monthly' => $this->monthly,
            'wallet' => $this->wallet,
            'total' => $this->total(),
        ];
    }
}
