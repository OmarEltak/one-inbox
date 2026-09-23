<div class="min-h-screen bg-zinc-50 py-12 dark:bg-zinc-950">
    <div class="mx-auto max-w-6xl px-6">
        {{-- Headline. This copy is the whole point of the page — do NOT weaken. --}}
        <div class="text-center max-w-3xl mx-auto">
            <span class="inline-flex items-center rounded-full bg-indigo-100 px-4 py-1.5 text-sm font-medium text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                {{ __('One more step: pick a plan') }}
            </span>
            <h1 class="mt-6 text-4xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50 sm:text-5xl">
                {{ __('Try any plan free for 14 days.') }}
            </h1>
            <p class="mt-6 text-lg text-zinc-600 dark:text-zinc-300">
                {{ __('No card needed. We\'ll invoice by bank transfer only when you\'re ready to keep it.') }}
            </p>
            <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('You\'ll get the full plan immediately. Change your mind anytime — email us and we\'ll adjust it.') }}
            </p>
        </div>

        {{-- Plan cards --}}
        <div class="mt-12 grid gap-4 sm:gap-5 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($plans as $plan)
                <div class="relative rounded-2xl border bg-white dark:bg-zinc-900 p-6 flex flex-col
                    {{ $plan['popular'] ? 'border-2 border-violet-600' : 'border-zinc-200 dark:border-zinc-800' }}">

                    @if($plan['popular'])
                        <span class="absolute -top-4 left-1/2 -translate-x-1/2 rounded-full bg-violet-600 px-4 py-1 text-xs font-semibold text-white whitespace-nowrap">
                            {{ __('Most Popular') }}
                        </span>
                    @endif

                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ __($plan['name']) }}</h3>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __($plan['tagline']) }}</p>
                        <p class="mt-4">
                            <span class="text-3xl font-bold text-zinc-900 dark:text-zinc-50">{{ $plan['price'] }}</span>
                        </p>

                        <ul class="mt-6 space-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                            @foreach($plan['features'] as $feature)
                                <li class="flex items-start gap-2">
                                    <svg class="mt-0.5 size-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                    <span>{{ __($feature) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="mt-6">
                        <button
                            type="button"
                            wire:click="pickPlan('{{ $plan['id'] }}')"
                            wire:loading.attr="disabled"
                            wire:target="pickPlan"
                            class="block w-full rounded-lg py-2.5 text-center text-sm font-semibold transition-colors
                                {{ $plan['popular']
                                    ? 'bg-violet-600 text-white hover:bg-violet-700'
                                    : 'border border-violet-400 text-violet-700 hover:bg-violet-50 dark:hover:bg-violet-950/30' }}
                                disabled:opacity-50 disabled:cursor-wait">
                            <span wire:loading.remove wire:target="pickPlan('{{ $plan['id'] }}')">
                                {{ $plan['id'] === 'enterprise' ? __('Talk to sales') : __('Start 14-day trial') }}
                            </span>
                            <span wire:loading wire:target="pickPlan('{{ $plan['id'] }}')">
                                {{ __('One moment...') }}
                            </span>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Trust row --}}
        <div class="mt-10 rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900 p-6">
            <div class="grid gap-6 sm:grid-cols-3 text-sm">
                <div class="flex items-start gap-3">
                    <div class="rounded-full bg-emerald-100 dark:bg-emerald-900/30 p-2">
                        <svg class="size-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Full access from day one') }}</p>
                        <p class="mt-1 text-zinc-500 dark:text-zinc-400">{{ __('Every feature of your chosen plan, no drip-feed.') }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="rounded-full bg-indigo-100 dark:bg-indigo-900/30 p-2">
                        <svg class="size-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a5 5 0 00-10 0v2a2 2 0 00-2 2v7a2 2 0 002 2h10a2 2 0 002-2v-7a2 2 0 00-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-zinc-900 dark:text-zinc-50">{{ __('No card required') }}</p>
                        <p class="mt-1 text-zinc-500 dark:text-zinc-400">{{ __('We\'ll email an invoice after 14 days. Pay by bank transfer only if you love it.') }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="rounded-full bg-violet-100 dark:bg-violet-900/30 p-2">
                        <svg class="size-5 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Cancel by email, anytime') }}</p>
                        <p class="mt-1 text-zinc-500 dark:text-zinc-400">{{ __('One reply, we handle the rest. No calls, no forms.') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Escape hatch. Free-plan users just click here; still starts a trial clock but tagged separately. --}}
        <div class="mt-6 text-center">
            <button
                type="button"
                wire:click="decideLater"
                class="text-sm text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 underline">
                {{ __('I\'ll decide later — take me to my inbox') }}
            </button>
        </div>
    </div>
</div>
