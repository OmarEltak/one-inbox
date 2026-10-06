<?php

declare(strict_types=1);

namespace App\Exceptions\Billing;

use RuntimeException;

/**
 * Thrown by App\Services\Billing\AiCredits::charge() when the team's total
 * balance (monthly + wallet) is strictly less than the action cost.
 *
 * Callers that can gracefully degrade (e.g. SendAiResponse) should catch
 * this and skip the AI call — the single AI dispatch gate on
 * Team::canDispatchAi() already guards most sites, but a race between the
 * balance check and the charge can occur under load.
 */
class InsufficientCreditsException extends RuntimeException
{
    public function __construct(
        public readonly int $teamId,
        public readonly string $action,
        public readonly int $cost,
        public readonly int $balance,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf(
                'Team %d has insufficient AI credits for action "%s" (cost=%d, balance=%d).',
                $teamId,
                $action,
                $cost,
                $balance,
            ),
            0,
            $previous,
        );
    }
}
