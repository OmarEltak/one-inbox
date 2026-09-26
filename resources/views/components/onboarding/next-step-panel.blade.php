@props(['team' => null])

@php
    $team = $team ?? auth()->user()?->currentTeam;
    if (! $team) return;

    /** @var \App\Services\Onboarding\ProgressService $progress */
    $progress = app(\App\Services\Onboarding\ProgressService::class);
    $steps    = $progress->stepsFor($team);
    $percent  = $progress->percentComplete($team);
    $next     = $progress->nextStep($team);
@endphp

{{-- Static, pure-Blade card — NO Alpine x-data, NO flux:modal, NO x-teleport,
     NO wire:ignore, NO fixed positioning, NO inline <script>. Renders safely
     inside inbox/index.blade.php's @empty branch (see inbox-composer-safety
     skill — failure modes 1, 2, 4, 5, 6 all avoided by keeping this dumb). --}}
<div class="w-full max-w-md mx-auto p-6"
     data-testid="onboarding-next-step-panel">
    <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="size-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                <flux:icon name="sparkles" class="size-5" />
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] uppercase tracking-widest text-zinc-400 font-semibold">{{ __('Getting started') }}</p>
                <p class="text-sm font-semibold text-zinc-800">{{ $percent }}% {{ __('complete') }}</p>
            </div>
        </div>

        <div class="h-1.5 rounded-full bg-zinc-100 overflow-hidden mb-5">
            <div class="h-full bg-emerald-500 transition-all"
                 style="width: {{ max(2, $percent) }}%;"></div>
        </div>

        <ol class="space-y-2 mb-5">
            @foreach($steps as $step)
                <li class="flex items-center gap-3 text-sm">
                    @if($step['done'])
                        <span class="inline-flex size-5 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <span class="text-zinc-500 line-through">{{ __($step['label']) }}</span>
                    @else
                        <span class="inline-flex size-5 flex-shrink-0 items-center justify-center rounded-full border-2 border-zinc-300"></span>
                        <span class="text-zinc-800">{{ __($step['label']) }}</span>
                        @if($step['optional'])
                            <span class="text-[10px] uppercase tracking-wider text-zinc-400 font-semibold">{{ __('optional') }}</span>
                        @endif
                    @endif
                </li>
            @endforeach
        </ol>

        @if($next)
            <a href="{{ $next['url'] }}"
               wire:navigate.hover
               class="block w-full text-center rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold px-4 py-2.5 transition-colors">
                {{ __('Next') }}: {{ __($next['label']) }} →
            </a>
        @endif

        <p class="mt-4 text-center text-[11px] text-zinc-400">
            {{ __('Stuck?') }}
            <a href="mailto:omareltak7@gmail.com?subject=OT1-Pro%20setup%20help" class="underline decoration-dotted hover:text-zinc-600">{{ __('Email Omar') }}</a>
        </p>
    </div>
</div>
