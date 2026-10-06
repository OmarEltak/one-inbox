<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Exceptions\Billing\ExpensiveActionRequiresConfirmationException;
use App\Exceptions\Billing\InsufficientCreditsException;
use App\Models\AiCreditLedgerEntry;
use App\Models\Team;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Phase A — AI credit economy service.
 *
 * Single entry point for every charge, refund, and grant against a team's
 * credit balance. Writes to the append-only ai_credit_ledger table and
 * invalidates the 60s Redis balance cache atomically.
 *
 * ## Design invariants
 *
 * 1. **Two-balance drain order.** charge() always drains `monthly` before
 *    `wallet`. If an action costs more than monthly remainder, two ledger
 *    rows are written (one per balance_type) in a single DB transaction.
 *
 * 2. **Append-only.** Corrections NEVER update an existing row — write a
 *    compensating row. refund() writes a POSITIVE row with
 *    reason='refund_outage' that references the original Receipt's ledger
 *    entry IDs in metadata.
 *
 * 3. **Idempotency.** When $meta['idempotency_key'] is set, the first call
 *    writes and caches the Receipt for 60s; subsequent calls within that
 *    window return the cached Receipt without a second write. Protects
 *    against double-charge from retried HTTP requests / Livewire re-renders.
 *
 * 4. **Confirmation gate.** Actions costing > CONFIRMATION_THRESHOLD require
 *    either team.auto_deduct_expensive_actions=true OR a
 *    `confirmation_token` in metadata. Otherwise throws
 *    ExpensiveActionRequiresConfirmationException BEFORE touching the ledger.
 *
 * 5. **Post-success charging.** Callers invoke charge() AFTER the AI call
 *    succeeds, not before. This matches spec §9's outage-refund logic — a
 *    failed call never produces a charge, so there's nothing to refund.
 *    The one exception is DeepAnalysis (Phase D) which charges on dispatch
 *    to prevent gaming by closing the tab.
 *
 * See docs/superpowers/specs/2026-10-04-ai-credit-economy-design.md.
 */
class AiCredits
{
    /** Actions costing more than this require explicit confirmation (spec §3.6). */
    public const CONFIRMATION_THRESHOLD = 5;

    /** Default cost when config/ai_costs.php is missing an action key. */
    public const DEFAULT_COST = 1;

    /** Cache TTL for the balance snapshot and idempotency receipts (seconds). */
    public const BALANCE_CACHE_TTL_SECONDS = 60;

    public const IDEMPOTENCY_TTL_SECONDS = 60;

    /**
     * Charge the team for an action, enforcing the confirmation gate and
     * idempotency. Writes 1-2 ledger rows depending on balance split.
     *
     * @param  array<string, mixed>  $meta  Supports keys:
     *   - idempotency_key: string      → dedup within 60s
     *   - confirmation_token: string   → bypasses the expensive-action gate
     *   - cost_source_type: string     → morph target (e.g. Message)
     *   - cost_source_id: int          → id on the morph target
     *   - actor_user_id: int           → who triggered this (defaults to auth()->id())
     *   - * (any additional)           → stored in metadata JSON
     *
     * @throws InsufficientCreditsException              when total balance < cost
     * @throws ExpensiveActionRequiresConfirmationException  when cost > 5 and not pre-confirmed
     */
    public function charge(Team $team, string $action, array $meta = []): Receipt
    {
        $cost = $this->costFor($action);

        // Free actions still return a Receipt so callers have uniform handling.
        if ($cost <= 0) {
            return new Receipt(
                teamId: $team->id,
                action: $action,
                cost: 0,
                ledgerEntryIds: [],
                breakdown: [],
                idempotencyKey: $meta['idempotency_key'] ?? null,
                metadata: $meta,
            );
        }

        // Idempotency — return cached receipt if the same key hit us recently.
        $idempotencyKey = $meta['idempotency_key'] ?? null;
        if ($idempotencyKey !== null) {
            $cacheKey = $this->idempotencyCacheKey($team, $action, $idempotencyKey);
            $cached = Cache::get($cacheKey);
            if ($cached instanceof Receipt) {
                return $cached;
            }
        }

        // Confirmation gate — expensive actions need either the team opt-in
        // or an explicit confirmation_token on this call.
        $hasConfirmation = ! empty($meta['confirmation_token']);
        if ($cost > self::CONFIRMATION_THRESHOLD
            && ! $team->auto_deduct_expensive_actions
            && ! $hasConfirmation
        ) {
            $balance = $this->balance($team);
            if ($balance->total() < $cost) {
                throw new InsufficientCreditsException(
                    teamId: $team->id,
                    action: $action,
                    cost: $cost,
                    balance: $balance->total(),
                );
            }
            throw new ExpensiveActionRequiresConfirmationException(
                teamId: $team->id,
                action: $action,
                cost: $cost,
                balanceAfter: $balance->total() - $cost,
                actionToken: $this->mintActionToken($team, $action),
            );
        }

        // Balance check — hard reject if we can't afford it.
        $balance = $this->balance($team);
        if ($balance->total() < $cost) {
            throw new InsufficientCreditsException(
                teamId: $team->id,
                action: $action,
                cost: $cost,
                balance: $balance->total(),
            );
        }

        // Two-balance drain: monthly first, then wallet. Split into 1-2 rows.
        $monthlyToDrain = min($balance->monthly, $cost);
        $walletToDrain  = $cost - $monthlyToDrain;

        $ledgerIds = [];
        $breakdown = [];

        DB::transaction(function () use ($team, $action, $meta, $monthlyToDrain, $walletToDrain, &$ledgerIds, &$breakdown) {
            if ($monthlyToDrain > 0) {
                $entry = $this->writeLedgerRow(
                    team: $team,
                    delta: -$monthlyToDrain,
                    balanceType: AiCreditLedgerEntry::BALANCE_MONTHLY,
                    reason: $action,
                    meta: $meta,
                );
                $ledgerIds[] = $entry->id;
                $breakdown[AiCreditLedgerEntry::BALANCE_MONTHLY] = $monthlyToDrain;
            }

            if ($walletToDrain > 0) {
                $entry = $this->writeLedgerRow(
                    team: $team,
                    delta: -$walletToDrain,
                    balanceType: AiCreditLedgerEntry::BALANCE_WALLET,
                    reason: $action,
                    meta: $meta,
                );
                $ledgerIds[] = $entry->id;
                $breakdown[AiCreditLedgerEntry::BALANCE_WALLET] = $walletToDrain;
            }
        });

        $this->invalidateBalanceCache($team);

        $receipt = new Receipt(
            teamId: $team->id,
            action: $action,
            cost: $cost,
            ledgerEntryIds: $ledgerIds,
            breakdown: $breakdown,
            idempotencyKey: $idempotencyKey,
            metadata: $meta,
        );

        if ($idempotencyKey !== null) {
            Cache::put(
                $this->idempotencyCacheKey($team, $action, $idempotencyKey),
                $receipt,
                self::IDEMPOTENCY_TTL_SECONDS,
            );
        }

        return $receipt;
    }

    /**
     * Reverse a prior charge. Writes a POSITIVE ledger row (or two, matching
     * the original split) referencing the original Receipt's ledger entries
     * in metadata so finance can audit the reversal.
     *
     * Refunds always land in the original balance_type (so a monthly charge
     * refunds to monthly, not wallet — otherwise a user could game outages
     * to accumulate never-expiring wallet credits).
     */
    public function refund(Receipt $receipt, string $reason): void
    {
        if ($receipt->cost <= 0 || empty($receipt->breakdown)) {
            return;
        }

        $team = Team::findOrFail($receipt->teamId);

        DB::transaction(function () use ($team, $receipt, $reason) {
            foreach ($receipt->breakdown as $balanceType => $amount) {
                $this->writeLedgerRow(
                    team: $team,
                    delta: (int) $amount, // positive → refund
                    balanceType: $balanceType,
                    reason: AiCreditLedgerEntry::REASON_REFUND_OUTAGE,
                    meta: [
                        'refund_reason' => $reason,
                        'original_action' => $receipt->action,
                        'original_ledger_ids' => $receipt->ledgerEntryIds,
                    ],
                );
            }
        });

        $this->invalidateBalanceCache($team);
    }

    /**
     * Grant credits to a team. Used by:
     *   - BackfillAiCreditLedgerSeeder (reason=backfill_go_live)
     *   - ResetMonthlyAiCredits command (reason=monthly_grant)
     *   - Super-admin manual grant screen in Phase E (reason=manual_grant)
     *   - Lemon Squeezy webhook in Phase D+ (reason=pack_purchase)
     *
     * @param  array<string, mixed>  $meta
     */
    public function grant(
        Team $team,
        int $amount,
        string $balanceType,
        string $reason,
        ?int $actorUserId = null,
        array $meta = [],
    ): AiCreditLedgerEntry {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Grant amount must be positive.');
        }

        if (! in_array($balanceType, [AiCreditLedgerEntry::BALANCE_MONTHLY, AiCreditLedgerEntry::BALANCE_WALLET], true)) {
            throw new \InvalidArgumentException("Invalid balance_type: {$balanceType}");
        }

        $meta['__actor_user_id'] = $actorUserId;

        $entry = $this->writeLedgerRow(
            team: $team,
            delta: $amount,
            balanceType: $balanceType,
            reason: $reason,
            meta: $meta,
        );

        $this->invalidateBalanceCache($team);

        return $entry;
    }

    /**
     * Zero out a team's monthly balance (used by the monthly reset command).
     * Writes a single compensating row equal to -current_monthly.
     */
    public function zeroMonthly(Team $team, string $reason = AiCreditLedgerEntry::REASON_MONTHLY_ZEROING): ?AiCreditLedgerEntry
    {
        $balance = $this->balance($team);
        if ($balance->monthly <= 0) {
            return null;
        }

        $entry = $this->writeLedgerRow(
            team: $team,
            delta: -$balance->monthly,
            balanceType: AiCreditLedgerEntry::BALANCE_MONTHLY,
            reason: $reason,
            meta: [],
        );

        $this->invalidateBalanceCache($team);

        return $entry;
    }

    /**
     * Current snapshot. Cached 60s in Redis (CACHE_STORE configured per-env).
     * On miss, computes SUM(delta) GROUP BY balance_type for the team.
     */
    public function balance(Team $team): Balance
    {
        return Cache::remember(
            $this->balanceCacheKey($team),
            self::BALANCE_CACHE_TTL_SECONDS,
            fn () => $this->computeBalance($team),
        );
    }

    /**
     * Cost of an action from config/ai_costs.php. Missing key → DEFAULT_COST.
     * Config file is created in Phase D; this gracefully handles its absence.
     */
    public function costFor(string $action): int
    {
        $raw = config('ai_costs.' . $action);
        if ($raw === null) {
            return self::DEFAULT_COST;
        }
        return max(0, (int) $raw);
    }

    public function balanceCacheKey(Team $team): string
    {
        return "team:credits:{$team->id}";
    }

    public function invalidateBalanceCache(Team $team): void
    {
        Cache::forget($this->balanceCacheKey($team));
    }

    /**
     * Compute balance from the ledger. SUM(delta) partitioned by balance_type.
     * Called by balance() on cache miss.
     */
    protected function computeBalance(Team $team): Balance
    {
        $rows = AiCreditLedgerEntry::query()
            ->where('team_id', $team->id)
            ->selectRaw('balance_type, COALESCE(SUM(delta), 0) as total')
            ->groupBy('balance_type')
            ->pluck('total', 'balance_type')
            ->all();

        return new Balance(
            monthly: max(0, (int) ($rows[AiCreditLedgerEntry::BALANCE_MONTHLY] ?? 0)),
            wallet:  max(0, (int) ($rows[AiCreditLedgerEntry::BALANCE_WALLET]  ?? 0)),
        );
    }

    /**
     * Low-level writer. Shared by charge/refund/grant/zeroMonthly so all paths
     * respect the same metadata flattening and actor-resolution rules.
     *
     * @param  array<string, mixed>  $meta
     */
    protected function writeLedgerRow(
        Team $team,
        int $delta,
        string $balanceType,
        string $reason,
        array $meta,
    ): AiCreditLedgerEntry {
        $actorUserId = $meta['__actor_user_id'] ?? ($meta['actor_user_id'] ?? null);
        unset($meta['__actor_user_id'], $meta['actor_user_id']);

        if ($actorUserId === null && auth()->check()) {
            $actorUserId = auth()->id();
        }

        $costSourceType = $meta['cost_source_type'] ?? null;
        $costSourceId   = $meta['cost_source_id'] ?? null;
        unset($meta['cost_source_type'], $meta['cost_source_id']);

        return AiCreditLedgerEntry::create([
            'team_id' => $team->id,
            'delta' => $delta,
            'balance_type' => $balanceType,
            'reason' => $reason,
            'cost_source_type' => $costSourceType,
            'cost_source_id' => $costSourceId !== null ? (int) $costSourceId : null,
            'actor_user_id' => $actorUserId !== null ? (int) $actorUserId : null,
            'metadata' => $meta !== [] ? $meta : null,
            'created_at' => now(),
        ]);
    }

    protected function idempotencyCacheKey(Team $team, string $action, string $idempotencyKey): string
    {
        $hash = hash('sha256', $team->id . '|' . $action . '|' . $idempotencyKey);
        return "credits:idem:{$hash}";
    }

    /**
     * Short-lived signed-ish token for the confirmation gate. Phase D's modal
     * flow re-dispatches with this token (via metadata.confirmation_token) to
     * bypass the gate. Signature verification can be added when we trust the
     * client less — for now the mere presence of SOME token is enough since
     * the AiChat Livewire action is server-authoritative.
     */
    protected function mintActionToken(Team $team, string $action): string
    {
        return 'act_' . $team->id . '_' . hash('sha256', $action . '|' . Str::uuid()->toString());
    }
}
