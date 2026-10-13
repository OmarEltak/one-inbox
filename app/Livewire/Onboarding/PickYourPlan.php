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
        // 2026-10-13: Phase RP collapsed the ladder to 4 tiers. 'enterprise'
        // and 'basic' are legacy slugs — kept in the allowlist so an old
        // bookmark/URL doesn't break, but resolved to 'business' downstream
        // via Team::resolvePlanSlug().
        if (! in_array($plan, ['free', 'starter', 'pro', 'business', 'basic', 'enterprise'], true)) {
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
     * Plan cards for the picker. Sourced from config/plans.php so prices +
     * credits + pages stay in sync with the public /pricing page and the
     * server-side limit enforcement in Team::monthlyCampaignLimit() and
     * Team::aiCreditsPerMonth(). Feature strings go through __() in the
     * Blade so AR translations live in lang/ar.json.
     *
     * @return array<int, array{id: string, name: string, price: string, tagline: string, features: array<int,string>, popular: bool}>
     */
    protected function planCards(): array
    {
        $plans = (array) config('plans.plans', []);
        $cards = [];

        $taglines = [
            'free'     => 'Try before you commit',
            'starter'  => 'For side-projects',
            'pro'      => 'For growing DMs',
            'business' => 'Scale without hiring',
        ];
        $popular = 'pro';

        foreach (['free', 'starter', 'pro', 'business'] as $slug) {
            $def = $plans[$slug] ?? null;
            if (! $def) {
                continue;
            }

            $price      = (int) ($def['price'] ?? 0);
            $credits    = (int) ($def['ai_credits'] ?? 0);
            $pages      = (int) ($def['limits']['pages'] ?? $def['pages'] ?? 0);
            $campaigns  = (int) ($def['limits']['bulk_campaigns_monthly'] ?? 0);
            $wa         = (int) ($def['limits']['whatsapp_numbers'] ?? 0);

            // Feature list: keep each string short + translatable. Each
            // string lives in ar.json so Blade's __() picks it up.
            $features = [
                trans_choice('{1} :count connected page|[2,*] :count connected pages', $pages, ['count' => $pages]),
                trans_choice('{1} :count AI credit \/ mo|[2,*] :count AI credits \/ mo', $credits, ['count' => number_format($credits)]),
                $wa === 0
                    ? __('No WhatsApp numbers')
                    : trans_choice('{1} :count WhatsApp number|[2,*] :count WhatsApp numbers', $wa, ['count' => $wa]),
                $campaigns === 0
                    ? __('No bulk campaigns')
                    : trans_choice('{1} :count bulk campaign \/ mo|[2,*] :count bulk campaigns \/ mo', $campaigns, ['count' => $campaigns]),
            ];

            $cards[] = [
                'id'       => $slug,
                'name'     => $def['name'] ?? ucfirst($slug),
                'price'    => $price === 0 ? '$0' : "\${$price}/mo",
                'tagline'  => $taglines[$slug] ?? '',
                'features' => $features,
                'popular'  => $slug === $popular,
            ];
        }

        return $cards;
    }
}
