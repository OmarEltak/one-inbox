{{--
    ══ ARCHITECTURE REFERENCE §13 ══
    READ docs/ARCHITECTURE.md §13 (Team Upstream Pause + Banner) before
    modifying the state resolution or the amber-vs-red rendering.
    The red variant is deliberately different so operators can tell a
    provider outage apart from a quota-exhausted state at a glance.
--}}
@php
    $bannerTeam = auth()->user()?->currentTeam;
    $planExhausted = $bannerTeam
        && $bannerTeam->isAiEnabled()
        && ! \App\Http\Middleware\EnforcePlanLimits::hasAiCredits($bannerTeam);
    $upstreamPaused = $bannerTeam && $bannerTeam->isAiEnabled() && $bannerTeam->isAiUpstreamLimited();
    $upstreamReason = $upstreamPaused ? $bannerTeam->aiUpstreamPauseReason() : null;
    $isOutage       = $upstreamReason === 'outage';
    $showAiBanner   = $planExhausted || $upstreamPaused;
@endphp

<div
    x-data="{ show: @json($showAiBanner) }"
    x-show="show"
    x-on:ai-limit-reached.window="show = true"
    x-cloak
    class="{{ $isOutage ? 'bg-red-50 border-b border-red-200 dark:bg-red-950/40 dark:border-red-900/60' : 'bg-amber-50 border-b border-amber-200 dark:bg-amber-950/40 dark:border-amber-900/60' }}"
>
    <div class="px-4 sm:px-6 py-2.5 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
            <flux:icon.exclamation-triangle class="w-5 h-5 {{ $isOutage ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400' }} shrink-0" />
            <p class="text-sm {{ $isOutage ? 'text-red-900 dark:text-red-200' : 'text-amber-900 dark:text-amber-200' }} truncate">
                @if($isOutage)
                    <span class="font-medium">AI is temporarily unavailable.</span>
                    <span class="text-red-700/80 dark:text-red-300/80">
                        The upstream AI provider is experiencing an outage — new incoming messages will land in your inbox without an AI reply. If this persists for more than an hour, please contact support.
                    </span>
                @else
                    <span class="font-medium">AI auto-replies are paused</span>
                    <span class="text-amber-700/80 dark:text-amber-300/80">
                        @if($planExhausted)
                            — you've reached your plan's AI credit limit. New incoming messages will land in your inbox without an AI reply.
                        @else
                            — your AI token limits are used up for now. New incoming messages will land in your inbox without an AI reply.
                        @endif
                    </span>
                @endif
            </p>
        </div>
        @unless($isOutage)
            <a href="{{ route('settings.billing') }}"
               class="text-sm font-medium text-amber-900 dark:text-amber-200 hover:text-amber-700 dark:hover:text-amber-100 underline whitespace-nowrap shrink-0">
                Upgrade plan
            </a>
        @endunless
    </div>
</div>
