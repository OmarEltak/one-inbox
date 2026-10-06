<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Mail\TopUpRequestedMail;
use App\Models\Team;
use App\Services\Billing\AiCredits;
use App\Services\Billing\Balance;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

/**
 * Phase F — /settings/billing/top-up.
 *
 * User-facing manual-payment page. Shows the current balance (same look as
 * Phase B's billing page), the plans and credit-pack prices, and the three
 * off-platform payment channels (PayPal, bank transfer, WhatsApp). The only
 * state-changing interaction is a rate-limited "notify Omar" button that
 * sends a TopUpRequestedMail — zero card capture, zero PCI concern.
 *
 * Omar reconciles payments manually, then grants credits via Phase E's
 * /super-admin/billing. Do NOT auto-credit from this component.
 */
final class TopUp extends Component
{
    /**
     * Which product the user just told us they bought. One of:
     *   - "plan:starter" | "plan:pro" | "plan:business"
     *   - "pack:small"   | "pack:medium" | "pack:large"
     * Bound to the dropdown in the "notify" panel.
     */
    public string $selectedProduct = '';

    /** How long a single team must wait between notifications (seconds). */
    public const NOTIFY_DECAY_SECONDS = 600;

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
        // Phase RP (2026-10-06): 3 paid tiers rendered — Starter / Pro /
        // Business. Free is implicit (every new team is on it) so no row.
        $all = (array) config('plans.plans', []);

        return array_intersect_key(
            $all,
            array_flip(['starter', 'pro', 'business']),
        );
    }

    #[Computed]
    public function packs(): array
    {
        return config('plans.packs', []);
    }

    #[Computed]
    public function payment(): array
    {
        return config('plans.manual_payment', []);
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
    public function monthlyQuota(): int
    {
        $plans = config('plans.plans', []);
        $plan = $plans[$this->currentPlan] ?? $plans['free'] ?? ['ai_credits' => 50];

        return (int) ($plan['ai_credits'] ?? 50);
    }

    #[Computed]
    public function isUnlimited(): bool
    {
        return $this->monthlyQuota === -1;
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
     * Pre-formatted wa.me URL for a plan or pack. Keeps the message assembly
     * in PHP so the Blade view stays translation-friendly.
     */
    public function waLink(string $product, string $label): string
    {
        $whatsapp = (string) ($this->payment['whatsapp'] ?? '');
        $teamId = $this->team?->id ?? 0;

        $text = __(
            'Hi, I would like to upgrade team #:id to :product.',
            ['id' => $teamId, 'product' => $label],
        );

        return 'https://wa.me/' . rawurlencode($whatsapp) . '?text=' . rawurlencode($text);
    }

    /**
     * Build the dropdown options (value => label).
     *
     * @return array<string, string>
     */
    #[Computed]
    public function productOptions(): array
    {
        $options = [];

        foreach ($this->plans as $slug => $plan) {
            $options['plan:' . $slug] = __(':name plan — $:price / month', [
                'name'  => $plan['name'],
                'price' => number_format((int) ($plan['price'] ?? 0)),
            ]);
        }

        foreach ($this->packs as $slug => $pack) {
            $options['pack:' . $slug] = __(':name pack — $:price for :credits credits', [
                'name'    => $pack['name'],
                'price'   => number_format((int) ($pack['price'] ?? 0)),
                'credits' => number_format((int) ($pack['credits'] ?? 0)),
            ]);
        }

        return $options;
    }

    /**
     * Human-readable label for a selected product value. Used in the mail
     * subject/body so Omar sees "Medium pack ($35 for 2,000 credits)" rather
     * than the opaque "pack:medium".
     */
    public function productLabel(string $value): string
    {
        return $this->productOptions[$value] ?? $value;
    }

    public function notify(): void
    {
        $team = $this->team;
        $user = Auth::user();

        if ($team === null || $user === null) {
            return;
        }

        $this->validate([
            'selectedProduct' => ['required', 'string', Rule::in(array_keys($this->productOptions))],
        ], attributes: [
            'selectedProduct' => __('plan or pack'),
        ]);

        $limiterKey = "topup-notify:{$team->id}";
        $allowed = RateLimiter::attempt(
            $limiterKey,
            1,
            fn () => true,
            self::NOTIFY_DECAY_SECONDS,
        );

        if (! $allowed) {
            session()->flash(
                'topup_warning',
                __("You've already notified us in the last 10 minutes — give us a moment to see it."),
            );
            return;
        }

        try {
            Mail::to(config('mail.admin_address', 'it@mishkahu.com'))
                ->send(new TopUpRequestedMail(
                    team: $team,
                    requester: $user,
                    product: $this->selectedProduct,
                    productLabel: $this->productLabel($this->selectedProduct),
                ));
        } catch (Throwable $e) {
            // Mail failure must not break the UX — the user still thinks their
            // payment is in flight; log it loudly so we can chase it up.
            Log::warning('TopUp: notify mail failed', [
                'team_id' => $team->id,
                'error'   => $e->getMessage(),
            ]);
        }

        session()->flash(
            'topup_success',
            __("Thanks — we'll credit your account within an hour of confirming the payment."),
        );

        $this->selectedProduct = '';
    }

    public function render()
    {
        return view('livewire.settings.top-up');
    }
}
