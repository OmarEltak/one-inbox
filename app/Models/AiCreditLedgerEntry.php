<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Append-only ledger entry for the AI credit economy.
 *
 * Entries represent a monetary-style change to a team's AI credit balance:
 *   - delta < 0 → charge   (reason e.g. 'ai_reply_outbound', 'deep_analysis')
 *   - delta > 0 → grant    (reason e.g. 'monthly_grant', 'manual_grant',
 *                           'backfill_go_live', 'refund_outage', 'pack_purchase')
 *
 * ## Append-only invariant
 *
 * This model intentionally overrides `update()` and `delete()` to throw. The
 * ledger is an audit trail — mutating a historical row would destroy
 * reconciliation. Corrections are made by writing a NEW compensating row
 * (e.g. a refund entry reverses a charge; a monthly_reset_zeroing entry
 * zeros out a leftover before a grant).
 *
 * See docs/superpowers/specs/2026-10-04-ai-credit-economy-design.md §3.4.
 */
class AiCreditLedgerEntry extends Model
{
    protected $table = 'ai_credit_ledger';

    public const UPDATED_AT = null; // append-only; no updated_at column

    public const BALANCE_MONTHLY = 'monthly';
    public const BALANCE_WALLET  = 'wallet';

    public const REASON_BACKFILL        = 'backfill_go_live';
    public const REASON_MONTHLY_GRANT   = 'monthly_grant';
    public const REASON_MONTHLY_ZEROING = 'monthly_reset_zeroing';
    public const REASON_REFUND_OUTAGE   = 'refund_outage';
    public const REASON_MANUAL_GRANT    = 'manual_grant';
    public const REASON_PACK_PURCHASE   = 'pack_purchase';

    protected $fillable = [
        'team_id',
        'delta',
        'balance_type',
        'reason',
        'cost_source_type',
        'cost_source_id',
        'actor_user_id',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'delta' => 'integer',
            'cost_source_id' => 'integer',
            'actor_user_id' => 'integer',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * Append-only guard. Writing a correction must create a new row.
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new RuntimeException(
            'AiCreditLedgerEntry is append-only. Create a compensating entry instead of updating.'
        );
    }

    /**
     * Append-only guard. Deleting history destroys the audit trail.
     */
    public function delete(): bool
    {
        throw new RuntimeException(
            'AiCreditLedgerEntry is append-only. Create a compensating entry instead of deleting.'
        );
    }
}
