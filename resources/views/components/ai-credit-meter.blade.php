{{--
    ══ AI CREDIT METER (Phase B) ══

    Always-visible pill in the authenticated header showing the current team's
    AI credit balance. Click opens /settings/billing with ledger history.

    Rendering rules (per spec §3.7):
      - ≤ 60% used → emerald
      - 60-85% used → amber
      - >  85% used → red
      - unlimited (ai_credits = -1) → "∞ · unlimited", no bar

    Fast path: wraps the balance snapshot in a 10s cache. The chip renders
    on every authenticated page so a MySQL round-trip per request is not
    acceptable. The AiCredits service itself caches 60s on top of this —
    the extra 10s wrapper exists so switching teams or opening /settings/billing
    (which forces a cache clear via AiCredits::invalidateBalanceCache) does
    NOT leave a 60s-stale pill.

    Colour pairs below are drawn from the contrast-guardrails safe-pair list
    (emerald-50/900, amber-50/900, red-50/900 with -200 borders). Dark-mode
    swatches use *-950/40 bg + *-200 text + *-900/60 border which clear WCAG
    AA against zinc-900/zinc-950 surfaces.
--}}
@php
    use App\Models\Team;
    use App\Services\Billing\AiCredits;
    use Illuminate\Support\Facades\Cache;

    $meterUser = auth()->user();
    $meterTeam = $meterUser?->currentTeam;
@endphp

@if($meterTeam)
    @php
        // MUST route through Team::resolvePlanSlug — legacy 'enterprise' / 'agency'
        // slugs no longer exist in config('plans.plans') (collapsed in Phase RP,
        // 2026-10-06) and silently fell back to the hard-coded 50 default, which
        // then showed "0 of 50 monthly" on the chip for Enterprise teams. See
        // CLAUDE.md pin on plan slug resolution.
        $planKey = Team::resolvePlanSlug($meterTeam->subscription_plan ?? null);
        $monthlyQuota = (int) (config("plans.plans.{$planKey}.ai_credits", (int) config('plans.plans.free.ai_credits', 100)));
        $isUnlimited = $monthlyQuota === -1;

        // 10s cache tuned per the header-chip-renders-on-every-page concern.
        // AiCredits::invalidateBalanceCache clears the deeper 60s layer; this
        // wrapper refreshes within 10s so the pill feels live.
        $balance = Cache::remember(
            "meter:team:{$meterTeam->id}",
            10,
            fn () => app(AiCredits::class)->balance($meterTeam),
        );

        $monthlyRemaining = $balance->monthly;
        $walletRemaining  = $balance->wallet;

        // Default state values; overridden for unlimited plans below.
        $monthlyUsed = $isUnlimited ? 0 : max(0, $monthlyQuota - $monthlyRemaining);
        $pctUsed = ($isUnlimited || $monthlyQuota <= 0)
            ? 0
            : min(100, (int) round(($monthlyUsed / $monthlyQuota) * 100));

        // Colour variant (contrast-guardrails safe pairs only).
        if ($isUnlimited) {
            $variant = 'emerald';
        } elseif ($pctUsed > 85) {
            $variant = 'red';
        } elseif ($pctUsed >= 60) {
            $variant = 'amber';
        } else {
            $variant = 'emerald';
        }

        $variantClasses = [
            'emerald' => 'bg-emerald-50 text-emerald-900 border-emerald-200 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-200 dark:border-emerald-900/60 dark:hover:bg-emerald-900/50',
            'amber'   => 'bg-amber-50 text-amber-900 border-amber-200 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-200 dark:border-amber-900/60 dark:hover:bg-amber-900/50',
            'red'     => 'bg-red-50 text-red-900 border-red-200 hover:bg-red-100 dark:bg-red-950/40 dark:text-red-200 dark:border-red-900/60 dark:hover:bg-red-900/50',
        ][$variant];

        $barFill = [
            'emerald' => 'bg-emerald-500 dark:bg-emerald-400',
            'amber'   => 'bg-amber-500 dark:bg-amber-400',
            'red'     => 'bg-red-500 dark:bg-red-400',
        ][$variant];

        // Days-to-reset — the anchor is a date column; next reset is the next
        // occurrence of the anchor day in the current or following month.
        $anchor = $meterTeam->billing_cycle_anchor
            ? \Illuminate\Support\Carbon::parse($meterTeam->billing_cycle_anchor)
            : \Illuminate\Support\Carbon::parse($meterTeam->created_at);
        $today = now()->startOfDay();
        $anchorDay = (int) $anchor->day;
        $nextReset = $today->copy()->day(min($anchorDay, $today->daysInMonth));
        if ($nextReset->lte($today)) {
            $nextReset = $today->copy()->addMonthNoOverflow()->day(min($anchorDay, $today->copy()->addMonthNoOverflow()->daysInMonth));
        }
        $daysToReset = (int) $today->diffInDays($nextReset);

        // Aria label — localized with placeholders (never string interpolation).
        if ($isUnlimited) {
            $ariaLabel = __('AI credits: unlimited');
        } else {
            $ariaLabel = __('AI credits: :used of :total used', [
                'used'  => number_format($monthlyUsed),
                'total' => number_format($monthlyQuota),
            ]);
        }

        $tooltipLine = $isUnlimited
            ? __('Unlimited AI credits on this plan')
            : trans_choice(':remaining of :total monthly · :wallet wallet · resets in :days day|:remaining of :total monthly · :wallet wallet · resets in :days days', $daysToReset, [
                'remaining' => number_format($monthlyRemaining),
                'total'     => number_format($monthlyQuota),
                'wallet'    => number_format($walletRemaining),
                'days'      => $daysToReset,
            ]);
    @endphp

    <flux:tooltip :content="$tooltipLine" position="bottom">
        <a
            href="{{ route('settings.billing') }}"
            wire:navigate.hover
            data-test="ai-credit-meter"
            data-variant="{{ $variant }}"
            aria-label="{{ $ariaLabel }}"
            class="hidden md:inline-flex items-center gap-2 ps-2.5 pe-3 py-1.5 rounded-xl border text-xs font-semibold transition-colors cursor-pointer {{ $variantClasses }}"
        >
            <flux:icon name="sparkles" class="size-3.5 shrink-0" />

            @if($isUnlimited)
                <span class="font-mono tabular-nums">
                    <span class="text-base leading-none" aria-hidden="true">∞</span>
                    <span class="ms-1">· {{ __('unlimited') }}</span>
                </span>
            @else
                <span class="font-mono tabular-nums whitespace-nowrap">
                    {{ number_format($monthlyRemaining) }}<span class="text-current/60"> / {{ number_format($monthlyQuota) }}</span>@if($walletRemaining > 0)<span class="text-current/60"> +</span> {{ number_format($walletRemaining) }}@endif
                </span>

                {{-- Compact progress bar — only when we have a meaningful quota. --}}
                <span
                    class="hidden lg:inline-block relative h-1 w-10 rounded-full bg-black/10 dark:bg-white/10 overflow-hidden"
                    aria-hidden="true"
                >
                    <span class="absolute inset-y-0 start-0 {{ $barFill }}" style="width: {{ $pctUsed }}%;"></span>
                </span>
            @endif
        </a>
    </flux:tooltip>
@endif
