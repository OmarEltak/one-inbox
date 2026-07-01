@php
    $bannerTeam = auth()->user()?->currentTeam;
    $planExhausted = $bannerTeam
        && $bannerTeam->isAiEnabled()
        && ! \App\Http\Middleware\EnforcePlanLimits::hasAiCredits($bannerTeam);
    $upstreamPaused = $bannerTeam && $bannerTeam->isAiEnabled() && $bannerTeam->isAiUpstreamLimited();
    $showAiBanner   = $planExhausted || $upstreamPaused;
@endphp

<div
    x-data="{ show: @json($showAiBanner) }"
    x-show="show"
    x-on:ai-limit-reached.window="show = true"
    x-cloak
    class="bg-amber-50 border-b border-amber-200 dark:bg-amber-950/40 dark:border-amber-900/60"
>
    <div class="px-4 sm:px-6 py-2.5 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
            <flux:icon.exclamation-triangle class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0" />
            <p class="text-sm text-amber-900 dark:text-amber-200 truncate">
                <span class="font-medium">AI auto-replies are paused</span>
                <span class="text-amber-700/80 dark:text-amber-300/80">
                    @if($planExhausted)
                        — you've reached your plan's AI credit limit. New incoming messages will land in your inbox without an AI reply.
                    @else
                        — your AI token limits are used up for now. New incoming messages will land in your inbox without an AI reply.
                    @endif
                </span>
            </p>
        </div>
        <a href="{{ route('settings.billing') }}"
           class="text-sm font-medium text-amber-900 dark:text-amber-200 hover:text-amber-700 dark:hover:text-amber-100 underline whitespace-nowrap shrink-0">
            Upgrade plan
        </a>
    </div>
</div>
