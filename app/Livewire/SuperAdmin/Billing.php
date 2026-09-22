<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin;

use App\Models\PaymentRequest;
use App\Models\Team;
use App\Services\Billing\PlanLifecycle;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Phase D — Super-admin billing surface.
 *
 * List of every team by plan_status with the numbers the founder needs to make
 * calls: days since signup, days since payment due, last activity. One-click
 * "Mark paid" and "Reset to trial" for the common cases; heavier flows (plan
 * grant with a specific end date) still live in App\Livewire\SuperAdmin\Subscriptions.
 */
class Billing extends Component
{
    public string $statusFilter = 'attention'; // attention | trial | pending_payment | overdue | paid | cancelled | all

    public string $search = '';

    /** @var array<int,string> rejection reason per team id (for mark-cancelled flow) */
    public array $note = [];

    public function updatedSearch(): void
    {
        unset($this->teams);
    }

    public function updatedStatusFilter(): void
    {
        unset($this->teams);
    }

    #[Computed]
    public function teams()
    {
        $query = Team::query()->with(['owner:id,name,email']);

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

        $lifecycle = app(PlanLifecycle::class);
        $now = now();

        match ($this->statusFilter) {
            'attention' => $query->whereIn('plan_status', [
                PlanLifecycle::STATUS_PENDING_PAYMENT,
                PlanLifecycle::STATUS_OVERDUE,
            ])->orWhere(function ($q) use ($now) {
                $q->where('plan_status', PlanLifecycle::STATUS_TRIAL)
                    ->whereNotNull('plan_trial_started_at')
                    ->where('plan_trial_started_at', '<=', $now->copy()->subDays(PlanLifecycle::TRIAL_DAYS - 3));
            }),
            'trial'           => $query->where('plan_status', PlanLifecycle::STATUS_TRIAL),
            'pending_payment' => $query->where('plan_status', PlanLifecycle::STATUS_PENDING_PAYMENT),
            'overdue'         => $query->where('plan_status', PlanLifecycle::STATUS_OVERDUE),
            'paid'            => $query->where('plan_status', PlanLifecycle::STATUS_PAID),
            'cancelled'       => $query->where('plan_status', PlanLifecycle::STATUS_CANCELLED),
            default           => null,
        };

        return $query->orderByDesc('plan_trial_started_at')->limit(500)->get();
    }

    #[Computed]
    public function pendingReceiptByTeam(): array
    {
        return PaymentRequest::query()
            ->where('status', 'pending')
            ->pluck('id', 'team_id')
            ->all();
    }

    public function markPaid(int $teamId): void
    {
        $team = Team::find($teamId);
        if (! $team) {
            return;
        }

        app(PlanLifecycle::class)->markPaid($team);

        // Auto-approve any pending receipt so the customer's PayWire flow shows
        // as approved without a second click in the Subscriptions panel.
        PaymentRequest::query()
            ->where('team_id', $team->id)
            ->where('status', 'pending')
            ->update(['status' => 'approved']);

        $this->dispatch('heron-event', name: 'payment_received', payload: [
            'team_id' => $team->id,
            'via'     => 'super_admin_billing',
        ]);

        session()->flash('success', "Marked {$team->name} as paid.");
        unset($this->teams, $this->pendingReceiptByTeam);
    }

    public function resetToTrial(int $teamId): void
    {
        $team = Team::find($teamId);
        if (! $team) {
            return;
        }

        app(PlanLifecycle::class)->resetToTrial($team);
        session()->flash('success', "Reset {$team->name} to a fresh 14-day trial.");
        unset($this->teams);
    }

    public function cancel(int $teamId): void
    {
        $team = Team::find($teamId);
        if (! $team) {
            return;
        }

        app(PlanLifecycle::class)->markCancelled($team);
        session()->flash('success', "Marked {$team->name} as cancelled.");
        unset($this->teams);
    }

    public function daysInTrialFor(Team $team): ?int
    {
        return app(PlanLifecycle::class)->trialDaysElapsed($team);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.super-admin.billing')
            ->layout('layouts.app', ['title' => 'Billing']);
    }
}
