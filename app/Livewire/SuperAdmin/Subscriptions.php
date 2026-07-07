<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin;

use App\Models\PaymentRequest;
use App\Models\Team;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Super-admin subscription control panel.
 *
 * The flow this backs:
 *   1. Customer submits payment via PayWire → PaymentRequest row created.
 *   2. Super-admin reviews here (linked from each team row if a pending
 *      PaymentRequest exists) OR grants access directly.
 *   3. Grant modal sets subscription_plan + subscription_ends_at + billing_cycle.
 *   4. Team::canDispatchAi() honours the expiry — AI stops at end_date until renewed.
 */
class Subscriptions extends Component
{
    public string $search = '';

    /** all | free | active | expiring | expired */
    public string $statusFilter = 'all';

    /** all | free | starter | pro | business | agency | enterprise | basic */
    public string $planFilter = 'all';

    /** Team IDs selected via row checkboxes for bulk actions. */
    public array $selected = [];

    // Grant modal state
    public ?int $grantTeamId = null;
    public string $grantPlan = 'starter';
    public string $grantDuration = '1_month';
    public string $grantBillingCycle = 'monthly';
    public string $grantMode = 'reset'; // reset | extend
    public ?string $grantCustomDate = null;

    public function updatedSearch(): void
    {
        unset($this->teams);
    }

    public function updatedStatusFilter(): void
    {
        unset($this->teams);
    }

    public function updatedPlanFilter(): void
    {
        unset($this->teams);
    }

    #[Computed]
    public function teams()
    {
        $query = Team::query()
            ->with(['owner:id,name,email'])
            ->withCount(['members', 'pages']);

        if ($this->search !== '') {
            $needle = '%' . str_replace(['%', '_'], ['\%', '\_'], $this->search) . '%';
            $query->where(function ($q) use ($needle) {
                $q->where('name', 'like', $needle)
                    ->orWhereHas('owner', function ($o) use ($needle) {
                        $o->where('name', 'like', $needle)
                            ->orWhere('email', 'like', $needle);
                    });
            });
        }

        if ($this->planFilter !== 'all') {
            $query->where('subscription_plan', $this->planFilter);
        }

        $now = now();
        $soon = $now->copy()->addDays(7);

        match ($this->statusFilter) {
            'free'     => $query->where(fn ($q) => $q->where('subscription_plan', 'free')->orWhereNull('subscription_plan')),
            'active'   => $query->where(function ($q) use ($now) {
                $q->where('subscription_plan', '!=', 'free')
                    ->whereNotNull('subscription_plan')
                    ->where(fn ($qq) => $qq->whereNull('subscription_ends_at')->orWhere('subscription_ends_at', '>', $now));
            }),
            'expiring' => $query->whereNotNull('subscription_ends_at')
                ->whereBetween('subscription_ends_at', [$now, $soon]),
            'expired'  => $query->whereNotNull('subscription_ends_at')
                ->where('subscription_ends_at', '<=', $now),
            default    => null,
        };

        return $query->orderBy('name')->limit(200)->get();
    }

    #[Computed]
    public function pendingPaymentByTeam(): array
    {
        return PaymentRequest::query()
            ->where('status', 'pending')
            ->pluck('id', 'team_id')
            ->all();
    }

    public function openGrant(int $teamId): void
    {
        $team = Team::find($teamId);
        if (! $team) {
            return;
        }

        $this->grantTeamId = $teamId;
        $this->grantPlan = in_array($team->subscription_plan, ['free', 'starter', 'pro', 'business', 'agency', 'enterprise'], true)
            && $team->subscription_plan !== 'free'
                ? $team->subscription_plan
                : 'starter';
        $this->grantBillingCycle = $team->billing_cycle ?: 'monthly';
        $this->grantDuration = '1_month';
        $this->grantMode = $team->subscription_ends_at && $team->subscription_ends_at->isFuture() ? 'extend' : 'reset';
        $this->grantCustomDate = null;
    }

    public function closeGrant(): void
    {
        $this->grantTeamId = null;
        $this->grantCustomDate = null;
    }

    public function grant(): void
    {
        if (! $this->grantTeamId) {
            return;
        }

        $team = Team::find($this->grantTeamId);
        if (! $team) {
            return;
        }

        $endsAt = $this->resolveEndsAt($team);
        if (! $endsAt) {
            session()->flash('error', 'Provide a valid duration or custom end date.');
            return;
        }

        $team->update([
            'subscription_plan'    => $this->grantPlan,
            'subscription_status'  => 'active',
            'subscription_ends_at' => $endsAt,
            'billing_cycle'        => $this->grantBillingCycle,
        ]);

        // Auto-approve any pending payment request for this team so the customer
        // doesn't stay in the "pending" state after we've already granted access.
        PaymentRequest::query()
            ->where('team_id', $team->id)
            ->where('status', 'pending')
            ->update(['status' => 'approved']);

        session()->flash('success', "Granted {$this->grantPlan} to {$team->name} until {$endsAt->toFormattedDateString()}.");
        $this->closeGrant();
        unset($this->teams, $this->pendingPaymentByTeam);
    }

    public function resetAiQuota(int $teamId): void
    {
        $team = Team::find($teamId);
        if (! $team) {
            return;
        }

        $team->update(['ai_credits_used' => 0]);
        session()->flash('success', "Reset AI quota for {$team->name}.");
        unset($this->teams);
    }

    public function bulkResetAiQuota(): void
    {
        $ids = array_values(array_filter(array_map('intval', $this->selected)));
        if (empty($ids)) {
            session()->flash('error', 'Select at least one team first.');
            return;
        }

        $count = Team::whereIn('id', $ids)->update(['ai_credits_used' => 0]);
        session()->flash('success', "Reset AI quota for {$count} team(s).");
        $this->selected = [];
        unset($this->teams);
    }

    public function selectAllVisible(): void
    {
        $this->selected = $this->teams->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function revoke(int $teamId): void
    {
        $team = Team::find($teamId);
        if (! $team) {
            return;
        }

        $team->update([
            'subscription_plan'    => 'free',
            'subscription_status'  => 'active',
            'subscription_ends_at' => null,
            'billing_cycle'        => null,
        ]);

        session()->flash('success', "Revoked access — {$team->name} is back on the free tier.");
        unset($this->teams);
    }

    private function resolveEndsAt(Team $team): ?Carbon
    {
        $base = $this->grantMode === 'extend'
                && $team->subscription_ends_at
                && $team->subscription_ends_at->isFuture()
            ? $team->subscription_ends_at->copy()
            : now();

        return match ($this->grantDuration) {
            '1_month'   => $base->addMonth(),
            '3_months'  => $base->addMonths(3),
            '6_months'  => $base->addMonths(6),
            '12_months' => $base->addYear(),
            'custom'    => $this->grantCustomDate
                ? Carbon::parse($this->grantCustomDate)->endOfDay()
                : null,
            default     => null,
        };
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.super-admin.subscriptions')
            ->layout('layouts.app', ['title' => 'Subscriptions']);
    }
}
