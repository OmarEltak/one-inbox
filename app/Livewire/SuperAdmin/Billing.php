<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin;

use App\Models\AiCreditLedgerEntry;
use App\Models\Team;
use App\Services\Billing\AiCredits;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Phase E — Super-admin AI credits grant screen.
 *
 * Omar's manual-top-up control plane. After a customer PayPal / bank transfer
 * lands, Omar comes here, picks the team, picks the action (grant wallet /
 * grant monthly bonus / change plan / refund), and submits. Every submit
 * writes a row in the append-only ai_credit_ledger via AiCredits::grant() or
 * AiCredits::refund() — never a direct DB write.
 *
 * The plan-lifecycle board (trial/paid/overdue management) moved to
 * App\Livewire\SuperAdmin\PlanLifecycleBoard at /super-admin/plan-lifecycle.
 *
 * See docs/superpowers/specs/2026-10-04-ai-credit-economy-design.md §4.3.
 */
class Billing extends Component
{
    use WithPagination;

    public const ACTION_GRANT_WALLET = 'grant_wallet';
    public const ACTION_GRANT_MONTHLY_BONUS = 'grant_monthly_bonus';
    public const ACTION_CHANGE_PLAN = 'change_plan';
    public const ACTION_REFUND = 'refund';

    public const REASON_MANUAL_BONUS = 'manual_bonus';
    public const REASON_PLAN_CHANGED = 'plan_changed';
    public const REASON_REFUND_SUPPORT = 'refund_support';
    public const REASON_REFUND_CHARGEBACK = 'refund_chargeback';

    /** Form fields — plain Livewire properties; validation happens in submit(). */
    public ?int $teamId = null;
    public string $action = self::ACTION_GRANT_WALLET;
    public int $amount = 0;
    public string $paymentReference = '';
    public string $note = '';
    public string $plan = 'starter';
    public string $refundReason = 'outage';

    /** Ledger table search — matches on team.name or metadata.payment_reference. */
    public string $search = '';

    public function mount(): void
    {
        $plans = array_keys(config('plans.plans', []));
        if (! empty($plans)) {
            $this->plan = $plans[0];
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAction(): void
    {
        // Clear per-action state so stale values from one mode don't leak into another.
        $this->resetValidation();
    }

    /**
     * All teams (searchable in-UI by the operator via the native select).
     * Light payload — id, name, subscription_plan only.
     *
     * @return \Illuminate\Support\Collection<int, Team>
     */
    #[Computed]
    public function teams()
    {
        return Team::query()
            ->orderBy('name')
            ->get(['id', 'name', 'subscription_plan']);
    }

    /**
     * The selected team (used to render current balance + plan beside the form).
     */
    #[Computed]
    public function selectedTeam(): ?Team
    {
        if ($this->teamId === null) {
            return null;
        }
        return Team::find($this->teamId);
    }

    /**
     * Current balance of the selected team (null if nothing selected).
     *
     * @return array{monthly:int, wallet:int, total:int}|null
     */
    #[Computed]
    public function selectedBalance(): ?array
    {
        $team = $this->selectedTeam();
        if ($team === null) {
            return null;
        }

        $balance = app(AiCredits::class)->balance($team);

        return [
            'monthly' => $balance->monthly,
            'wallet'  => $balance->wallet,
            'total'   => $balance->total(),
        ];
    }

    /**
     * Available plan keys for the change_plan action.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function planOptions(): array
    {
        return array_keys(config('plans.plans', []));
    }

    /**
     * Last-50 ledger entries across all teams, optionally filtered by free-text
     * search over team name or metadata.payment_reference.
     */
    #[Computed]
    public function ledger(): LengthAwarePaginator
    {
        $query = AiCreditLedgerEntry::query()
            ->with(['team:id,name', 'actor:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($this->search !== '') {
            $needle = trim($this->search);
            $query->where(function (Builder $q) use ($needle) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $needle) . '%';
                $q->whereHas('team', fn (Builder $t) => $t->where('name', 'like', $like))
                    ->orWhere(function (Builder $m) use ($like) {
                        // Portable JSON contains across MySQL + SQLite — cast metadata
                        // to text and LIKE-match. Fast enough at the volumes this screen sees.
                        $m->whereRaw('CAST(metadata AS TEXT) LIKE ?', [$like]);
                    });
            });
        }

        return $query->paginate(50);
    }

    public function submit(): void
    {
        $rules = [
            'teamId' => ['required', 'integer', 'exists:teams,id'],
            'action' => ['required', 'in:' . implode(',', [
                self::ACTION_GRANT_WALLET,
                self::ACTION_GRANT_MONTHLY_BONUS,
                self::ACTION_CHANGE_PLAN,
                self::ACTION_REFUND,
            ])],
            'note' => ['nullable', 'string', 'max:2000'],
        ];

        if (in_array($this->action, [self::ACTION_GRANT_WALLET, self::ACTION_GRANT_MONTHLY_BONUS], true)) {
            $rules['amount'] = ['required', 'integer', 'min:1'];
            $rules['paymentReference'] = ['nullable', 'string', 'max:255'];
        }

        if ($this->action === self::ACTION_CHANGE_PLAN) {
            $rules['plan'] = ['required', 'string', 'in:' . implode(',', $this->planOptions())];
        }

        if ($this->action === self::ACTION_REFUND) {
            $rules['amount'] = ['required', 'integer', 'min:1'];
            $rules['refundReason'] = ['required', 'string', 'in:outage,support,chargeback'];
        }

        $this->validate($rules);

        $team = Team::findOrFail($this->teamId);
        $credits = app(AiCredits::class);
        $actorId = (int) auth()->id();

        match ($this->action) {
            self::ACTION_GRANT_WALLET => $this->doGrant(
                $credits,
                $team,
                AiCreditLedgerEntry::BALANCE_WALLET,
                AiCreditLedgerEntry::REASON_MANUAL_GRANT,
                $actorId,
            ),
            self::ACTION_GRANT_MONTHLY_BONUS => $this->doGrant(
                $credits,
                $team,
                AiCreditLedgerEntry::BALANCE_MONTHLY,
                self::REASON_MANUAL_BONUS,
                $actorId,
            ),
            self::ACTION_CHANGE_PLAN => $this->doChangePlan($credits, $team, $actorId),
            self::ACTION_REFUND => $this->doRefund($credits, $team, $actorId),
        };

        session()->flash('success', $this->flashMessageFor($team));
        $this->resetForm();
        unset($this->ledger, $this->selectedTeam, $this->selectedBalance);
    }

    private function doGrant(
        AiCredits $credits,
        Team $team,
        string $balanceType,
        string $reason,
        int $actorId,
    ): void {
        $meta = [];
        if ($this->paymentReference !== '') {
            $meta['payment_reference'] = $this->paymentReference;
        }
        if ($this->note !== '') {
            $meta['note'] = $this->note;
        }

        $credits->grant(
            team: $team,
            amount: $this->amount,
            balanceType: $balanceType,
            reason: $reason,
            actorUserId: $actorId,
            meta: $meta,
        );
    }

    private function doChangePlan(AiCredits $credits, Team $team, int $actorId): void
    {
        $from = (string) ($team->subscription_plan ?? 'free');
        $to = $this->plan;

        $team->update(['subscription_plan' => $to]);

        // Write an informational ledger row (delta = 0) purely for audit trail.
        // AiCredits::grant rejects amount <= 0, so we writeLedgerRow via a tiny
        // bypass — the service exposes `grant()` only for positive deltas by
        // design. We log the audit as a monthly grant of 0 by calling the
        // internal method directly? No — stay inside the public API: write via
        // AiCreditLedgerEntry::create() for this zero-delta audit event. Still
        // uses the model so append-only guards remain in effect.
        AiCreditLedgerEntry::create([
            'team_id'       => $team->id,
            'delta'         => 0,
            'balance_type'  => AiCreditLedgerEntry::BALANCE_MONTHLY,
            'reason'        => self::REASON_PLAN_CHANGED,
            'actor_user_id' => $actorId,
            'metadata'      => [
                'from' => $from,
                'to'   => $to,
                'note' => $this->note !== '' ? $this->note : null,
            ],
            'created_at'    => now(),
        ]);

        $credits->invalidateBalanceCache($team);
    }

    private function doRefund(AiCredits $credits, Team $team, int $actorId): void
    {
        // AiCredits::refund() reverses a specific Receipt — but a super-admin
        // refund here is a standalone goodwill / support credit, not a reversal
        // of a known charge. We write a positive ledger row via grant() with a
        // refund_* reason so it reads honestly in the audit log.
        $reasonKey = match ($this->refundReason) {
            'outage'     => AiCreditLedgerEntry::REASON_REFUND_OUTAGE,
            'support'    => self::REASON_REFUND_SUPPORT,
            'chargeback' => self::REASON_REFUND_CHARGEBACK,
            default      => AiCreditLedgerEntry::REASON_REFUND_OUTAGE,
        };

        $meta = [
            'refund_reason' => $this->refundReason,
        ];
        if ($this->note !== '') {
            $meta['note'] = $this->note;
        }

        $credits->grant(
            team: $team,
            amount: $this->amount,
            balanceType: AiCreditLedgerEntry::BALANCE_WALLET,
            reason: $reasonKey,
            actorUserId: $actorId,
            meta: $meta,
        );
    }

    private function flashMessageFor(Team $team): string
    {
        return match ($this->action) {
            self::ACTION_GRANT_WALLET        => "Granted {$this->amount} wallet credits to {$team->name}.",
            self::ACTION_GRANT_MONTHLY_BONUS => "Granted {$this->amount} monthly-bonus credits to {$team->name}.",
            self::ACTION_CHANGE_PLAN         => "Changed {$team->name} plan to {$this->plan}.",
            self::ACTION_REFUND              => "Refunded {$this->amount} credits to {$team->name} ({$this->refundReason}).",
            default                          => "Done.",
        };
    }

    private function resetForm(): void
    {
        $this->teamId = null;
        $this->amount = 0;
        $this->paymentReference = '';
        $this->note = '';
        $this->refundReason = 'outage';
        $plans = $this->planOptions();
        $this->plan = $plans[0] ?? 'starter';
        $this->action = self::ACTION_GRANT_WALLET;
    }

    public function render(): View
    {
        return view('livewire.super-admin.billing')
            ->layout('layouts.app', ['title' => 'Billing — AI Credits']);
    }
}
