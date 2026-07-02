<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by AI providers when the entire fallback chain has been exhausted —
 * every configured model returned an upstream error (typically 5xx). Distinct
 * from AiQuotaExhausted because the remediation is different: quota is
 * "wait for the daily reset or upgrade the plan", chain-exhaustion is "the
 * upstream provider is broken and you probably need to contact support".
 *
 * Handled in SendAiResponse::handle by pausing AI for the team on a short
 * window (15 min — outages usually recover fast) and broadcasting the
 * AiLimitReached event with a distinct reason for the header banner.
 */
class AiAllProvidersUnavailable extends RuntimeException
{
}
