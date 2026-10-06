<?php

declare(strict_types=1);

namespace App\Exceptions\Billing;

use RuntimeException;

/**
 * Thrown by App\Services\Billing\AiCredits::charge() when ALL of:
 *   - action cost > AiCredits::CONFIRMATION_THRESHOLD (5)
 *   - team.auto_deduct_expensive_actions = false
 *   - no `confirmation_token` present in the metadata
 *
 * Callers (AiChat, DispatchDeepAnalysisJob trigger) catch this and surface
 * the confirmation payload to the UI. Once the user confirms, the caller
 * re-dispatches with metadata.confirmation_token set, which bypasses the gate.
 *
 * The modal that renders this is a Phase D concern — Phase A only emits the
 * exception shape. See spec §3.6.
 */
class ExpensiveActionRequiresConfirmationException extends RuntimeException
{
    public function __construct(
        public readonly int $teamId,
        public readonly string $action,
        public readonly int $cost,
        public readonly int $balanceAfter,
        public readonly string $actionToken,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf(
                'Action "%s" costs %d credits and requires confirmation (team %d, balance after=%d).',
                $action,
                $cost,
                $teamId,
                $balanceAfter,
            ),
            0,
            $previous,
        );
    }

    /**
     * Payload the UI needs to render the confirmation modal (spec §3.6).
     *
     * @return array{type: string, cost: int, balance_after: int, action: string, action_token: string}
     */
    public function toPayload(): array
    {
        return [
            'type' => 'confirmation_required',
            'cost' => $this->cost,
            'balance_after' => $this->balanceAfter,
            'action' => $this->action,
            'action_token' => $this->actionToken,
        ];
    }
}
