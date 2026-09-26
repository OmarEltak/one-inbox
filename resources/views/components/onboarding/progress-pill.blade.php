@props(['team' => null])

@php
    $team = $team ?? auth()->user()?->currentTeam;
    if (! $team) return;

    /** @var \App\Services\Onboarding\ProgressService $progress */
    $progress = app(\App\Services\Onboarding\ProgressService::class);
    $percent  = $progress->percentComplete($team);
    $next     = $progress->nextStep($team);

    // Hide entirely once fully complete — the pill is a "get started" nudge,
    // not a permanent chrome element. Also hide if we somehow can't compute.
    if ($percent >= 100 || $next === null) return;

    // Single-hue violet across all progress buckets so the chrome pill matches
    // the wizard's primary color (violet-600). Weight steps up with progress so
    // users still feel the pill "warming" as they complete steps, without
    // introducing amber/blue/emerald that fight the app's violet identity.
    $ring = $percent < 40 ? 'ring-emerald-200 bg-emerald-50 text-emerald-700'
          : ($percent < 80 ? 'ring-emerald-300 bg-emerald-100 text-emerald-800'
          : 'ring-emerald-400 bg-emerald-100 text-emerald-900');
@endphp

<a href="{{ $next['url'] }}"
   wire:navigate.hover
   title="{{ __('Next step') }}: {{ $next['label'] }}"
   class="hidden md:inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 {{ $ring }} hover:opacity-90 transition-opacity"
   data-testid="onboarding-progress-pill"
>
    <span class="inline-flex items-center gap-1">
        <span class="relative flex size-4">
            <svg viewBox="0 0 16 16" class="size-4">
                <circle cx="8" cy="8" r="6.5" fill="none" stroke="currentColor" stroke-opacity="0.25" stroke-width="2"></circle>
                @php
                    $c = 2 * pi() * 6.5;
                    $offset = $c * (1 - min(1, $percent / 100));
                @endphp
                <circle cx="8" cy="8" r="6.5" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-dasharray="{{ number_format($c, 2, '.', '') }}"
                        stroke-dashoffset="{{ number_format($offset, 2, '.', '') }}"
                        transform="rotate(-90 8 8)" stroke-linecap="round"></circle>
            </svg>
        </span>
        <span>{{ $percent }}%</span>
    </span>
    <span class="hidden lg:inline text-[11px] font-medium opacity-80 truncate max-w-[180px]">
        {{ __('Next') }}: {{ $next['label'] }}
    </span>
</a>
