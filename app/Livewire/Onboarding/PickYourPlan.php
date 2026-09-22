<?php

declare(strict_types=1);

namespace App\Livewire\Onboarding;

use App\Services\Billing\PlanLifecycle;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Phase D — Trust-based plan trial picker.
 *
 * Shown ONCE, immediately after Phase A completion, before /inbox. Guarded by
 * plan_trial_started_at being null; if the user has already picked (or been
 * seeded as `paid`), the mount() bounces them to /inbox so refreshing the URL
 * never re-charges the trial clock.
 *
 * Payment model: bank transfer only via App\Livewire\PayWire. No card capture
 * anywhere in this flow — per tasks/onboarding-activation-plan.md Phase D.
 */
#[Layout('layouts.app')]
#[Title('Pick your plan')]
class PickYourPlan extends Component
{
    /** @var string one of: free|basic|starter|pro|enterprise */
    public string $selectedPlan = 'starter';

    #[Locked]
    public bool $picked = false;

    public function mount(): void
    {
        $team = Auth::user()?->currentTeam;
        if (! $team) {
            $this->redirect(route('teams.create'), navigate: false);
            return;
        }

        // Show ONCE: once the trial clock has started, or once the team is
        // seeded/paid, this page is a no-op.
        if ($team->plan_trial_started_at !== null
            || $team->plan_status === PlanLifecycle::STATUS_PAID
            || $team->plan_status === PlanLifecycle::STATUS_CANCELLED
        ) {
            $this->redirect(route('dashboard', absolute: false), navigate: false);
        }
    }

    /**
     * User picks a plan card. Starts the 14-day trial clock (idempotent) and
     * sends the user into /inbox. If they picked a paid plan, the sidebar
     * (Phase C) will nudge them toward /pay-wire before day 14.
     */
    public function pickPlan(string $plan): void
    {
        if (! in_array($plan, ['free', 'basic', 'starter', 'pro', 'enterprise'], true)) {
            return;
        }

        $team = Auth::user()?->currentTeam;
        if (! $team) {
            return;
        }

        $this->selectedPlan = $plan;

        app(PlanLifecycle::class)->startTrial($team, $plan);

        $this->picked = true;

        $this->dispatch('heron-event', name: 'plan_picked', payload: [
            'plan' => $plan,
        ]);

        // Enterprise picks route to WhatsApp for a real sales conversation;
        // everyone else lands on /inbox to start using the product.
        if ($plan === 'enterprise') {
            // Absolute URL is intentional — external link.
            $this->redirect('https://wa.me/201026361218?text=' . urlencode(
                "Hi, I picked the Enterprise plan on OT1-Pro and want to talk."
            ));
            return;
        }

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    /**
     * "I'll decide later" — same as picking free, but tagged separately in
     * telemetry so we can see the drop-off honestly.
     */
    public function decideLater(): void
    {
        $team = Auth::user()?->currentTeam;
        if (! $team) {
            return;
        }

        app(PlanLifecycle::class)->startTrial($team, 'free');

        $this->dispatch('heron-event', name: 'plan_picked', payload: [
            'plan' => 'free',
            'via'  => 'decide_later',
        ]);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    public function render()
    {
        return view('livewire.onboarding.pick-your-plan', [
            'plans' => $this->planCards(),
        ]);
    }

    /**
     * Plan cards for the picker. Kept in-component (rather than a config) so
     * Blade stays trivial and the reviewer can read one file to see all copy.
     *
     * @return array<int, array{id: string, name: string, price: string, tagline: string, features: array<int,string>, popular: bool}>
     */
    protected function planCards(): array
    {
        return [
            [
                'id'       => 'free',
                'name'     => 'Free',
                'price'    => '$0',
                'tagline'  => 'Try before you commit',
                'features' => [
                    '1 connected page',
                    '20 AI responses/mo',
                    'Unified inbox',
                    '1 team member',
                ],
                'popular'  => false,
            ],
            [
                'id'       => 'starter',
                'name'     => 'Starter',
                'price'    => '$29/mo',
                'tagline'  => 'Your AI sales rep, 24/7',
                'features' => [
                    '3 connected pages',
                    '500 AI responses/mo',
                    'All 4 platforms',
                    '3 team members',
                ],
                'popular'  => false,
            ],
            [
                'id'       => 'pro',
                'name'     => 'Pro',
                'price'    => '$79/mo',
                'tagline'  => 'Built for teams that close deals',
                'features' => [
                    '5 connected pages',
                    '2,000 AI responses/mo',
                    'AI bulk campaigns',
                    'Advanced analytics',
                    '10 team members',
                ],
                'popular'  => true,
            ],
            [
                'id'       => 'enterprise',
                'name'     => 'Enterprise',
                'price'    => 'Custom',
                'tagline'  => 'For agencies & large teams',
                'features' => [
                    'Unlimited pages',
                    'Unlimited AI responses',
                    'Custom AI voice & training',
                    'White-label option',
                    'Dedicated onboarding',
                ],
                'popular'  => false,
            ],
        ];
    }
}
