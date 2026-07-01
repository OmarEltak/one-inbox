<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by AI providers when the upstream API refuses a call for quota /
 * rate-limit reasons (typically HTTP 429). SendAiResponse catches this
 * specifically to short-circuit the job, pause AI on the team, and broadcast
 * the AiLimitReached event so the header banner surfaces to the customer.
 *
 * Any other provider failure returns an empty string instead — silent skip.
 */
class AiQuotaExhausted extends RuntimeException
{
}
