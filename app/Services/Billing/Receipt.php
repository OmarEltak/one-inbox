<?php

declare(strict_types=1);

namespace App\Services\Billing;

/**
 * Immutable record of a successful AiCredits::charge() call.
 *
 * Returned to the caller so they can:
 *   - log the charge against their business entity (Message, DeepAnalysis, ...)
 *   - pass to AiCredits::refund($receipt, 'outage') when the paid-for action
 *     subsequently fails under a provider outage.
 *
 * `ledgerEntryIds` is an array because a split-balance charge (e.g. 7 credits
 * with 3 monthly left + 10 wallet) writes TWO ledger rows — one per balance_type.
 */
final readonly class Receipt
{
    /**
     * @param  array<int, int>  $ledgerEntryIds
     * @param  array<string, int>  $breakdown  balance_type => amount drained
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public int $teamId,
        public string $action,
        public int $cost,
        public array $ledgerEntryIds,
        public array $breakdown,
        public ?string $idempotencyKey,
        public array $metadata = [],
    ) {
    }
}
