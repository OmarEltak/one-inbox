<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\AiCreditLedgerEntry;
use App\Models\Team;
use App\Services\Billing\AiCredits;
use App\Services\Billing\Balance;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Phase B — /settings/billing.
 *
 * Replaces the pre-Phase-A Stripe/Cashier billing page with:
 *   - a balance card (monthly used/allowance, wallet, days-to-reset)
 *   - current plan + a placeholder "Top up" link (/settings/billing/top-up
 *     is Phase F — the route is NOT yet declared, so the link currently
 *     points at a soft-404 anchor until F ships).
 *   - a paginated, date-filtered ledger table.
 *
 * The old Cashier `subscribe()` / `manageSubscription()` endpoints are gone
 * because OT1 Pro has no payment provider wired per spec §3.2 — top-ups
 * flow through the super-admin grant screen (Phase E) with manual payment
 * confirmation via PayPal/bank/WhatsApp.
 */
class Billing extends Component
{
    use WithPagination;

    /** 7 / 30 / 90 — bound to the date-filter dropdown. */
    #[Url(as: 'range', keep: false)]
    public int $rangeDays = 30;

    /** Reset pagination whenever the filter changes. */
    public function updatedRangeDays(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function team(): ?Team
    {
        return Auth::user()?->currentTeam;
    }

    #[Computed]
    public function currentPlan(): string
    {
        return $this->team?->subscription_plan ?? 'free';
    }

    #[Computed]
    public function plans(): array
    {
        return config('plans.plans', []);
    }

    #[Computed]
    public function monthlyQuota(): int
    {
        $plan = $this->plans[$this->currentPlan] ?? $this->plans['free'] ?? ['ai_credits' => 50];

        return (int) ($plan['ai_credits'] ?? 50);
    }

    #[Computed]
    public function isUnlimited(): bool
    {
        return $this->monthlyQuota === -1;
    }

    #[Computed]
    public function balance(): Balance
    {
        $team = $this->team;
        if ($team === null) {
            return new Balance(monthly: 0, wallet: 0);
        }

        return app(AiCredits::class)->balance($team);
    }

    #[Computed]
    public function monthlyUsed(): int
    {
        if ($this->isUnlimited) {
            return 0;
        }

        return max(0, $this->monthlyQuota - $this->balance->monthly);
    }

    #[Computed]
    public function pctUsed(): int
    {
        if ($this->isUnlimited || $this->monthlyQuota <= 0) {
            return 0;
        }

        return (int) min(100, round(($this->monthlyUsed / $this->monthlyQuota) * 100));
    }

    #[Computed]
    public function daysToReset(): int
    {
        $team = $this->team;
        if ($team === null) {
            return 0;
        }

        $anchor = $team->billing_cycle_anchor
            ? CarbonImmutable::parse($team->billing_cycle_anchor)
            : CarbonImmutable::parse($team->created_at);

        $today = CarbonImmutable::now()->startOfDay();
        $anchorDay = (int) $anchor->day;
        $next = $today->day(min($anchorDay, $today->daysInMonth));
        if ($next->lte($today)) {
            $nextMonth = $today->addMonthNoOverflow();
            $next = $nextMonth->day(min($anchorDay, $nextMonth->daysInMonth));
        }

        return (int) $today->diffInDays($next);
    }

    /**
     * Paginated ledger rows for the current team within the chosen window.
     * The *table* query — the balance query sits on the service to pick up
     * the Redis cache.
     */
    #[Computed]
    public function ledger(): LengthAwarePaginator
    {
        $team = $this->team;
        if ($team === null) {
            return AiCreditLedgerEntry::query()->whereRaw('1 = 0')->paginate(10);
        }

        $since = now()->subDays($this->rangeDays);

        return AiCreditLedgerEntry::query()
            ->where('team_id', $team->id)
            ->where('created_at', '>=', $since)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10);
    }

    /**
     * Human-readable label for a ledger `reason` string.
     * Keeps the switch in one place so the Arabic translator has a single
     * list to track.
     */
    public function reasonLabel(string $reason): string
    {
        return match ($reason) {
            'ai_chat_turn'          => __('AI chat message'),
            'ai_reply_outbound'     => __('AI reply to customer'),
            'ai_comment_reply'      => __('AI comment reply'),
            'ai_lead_score'         => __('Lead scoring'),
            'deep_analysis'         => __('Deep analysis'),
            'agent_audit'           => __('Agent audit'),
            AiCreditLedgerEntry::REASON_MONTHLY_GRANT   => __('Monthly allowance'),
            AiCreditLedgerEntry::REASON_MONTHLY_ZEROING => __('Monthly reset'),
            AiCreditLedgerEntry::REASON_BACKFILL        => __('Initial allowance'),
            AiCreditLedgerEntry::REASON_MANUAL_GRANT    => __('Manual grant'),
            AiCreditLedgerEntry::REASON_PACK_PURCHASE   => __('Pack purchase'),
            AiCreditLedgerEntry::REASON_REFUND_OUTAGE   => __('Refund (outage)'),
            default                  => $reason,
        };
    }

    public function render()
    {
        return view('livewire.settings.billing');
    }
}
