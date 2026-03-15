<x-layouts.marketing :title="__('Pricing') . ' — One Inbox'" :description="__('Simple, transparent pricing for One Inbox. Start free, upgrade when you grow.')">

    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="text-center">
                <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">{{ __('Simple, transparent pricing') }}</h1>
                <p class="mt-6 text-lg text-zinc-600 dark:text-zinc-400">
                    {{ __('Start free. Upgrade when your business grows.') }}
                </p>
            </div>

            <div class="mt-16 grid gap-8 lg:grid-cols-3">
                {{-- Free Plan --}}
                <div class="rounded-2xl border border-zinc-200 bg-white p-8 dark:border-zinc-800 dark:bg-zinc-900">
                    <h3 class="text-lg font-semibold">{{ __('Starter') }}</h3>
                    <p class="mt-2 text-sm text-zinc-500">{{ __('Perfect for getting started') }}</p>
                    <p class="mt-6"><span class="text-4xl font-bold">{{ __('Free') }}</span></p>
                    <ul class="mt-8 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                        <li class="flex items-center gap-2">
                            <svg class="size-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('1 connected channel') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="size-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('100 AI responses/month') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="size-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('Basic analytics') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="size-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('1 team member') }}
                        </li>
                    </ul>
                    @if(Route::has('register'))
                        <a href="{{ route('register') }}" class="mt-8 block w-full rounded-lg border border-zinc-300 py-2.5 text-center text-sm font-semibold transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                            {{ __('Get Started') }}
                        </a>
                    @endif
                </div>

                {{-- Pro Plan --}}
                <div class="relative rounded-2xl border-2 border-purple-600 bg-white p-8 dark:bg-zinc-900">
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-purple-600 px-4 py-1 text-xs font-semibold text-white">{{ __('Most Popular') }}</span>
                    <h3 class="text-lg font-semibold">{{ __('Pro') }}</h3>
                    <p class="mt-2 text-sm text-zinc-500">{{ __('For growing businesses') }}</p>
                    <p class="mt-6"><span class="text-4xl font-bold">$49</span><span class="text-zinc-500">/{{ __('month') }}</span></p>
                    <ul class="mt-8 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                        <li class="flex items-center gap-2">
                            <svg class="size-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('5 connected channels') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="size-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('Unlimited AI responses') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="size-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('Advanced analytics') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="size-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('5 team members') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="size-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('Lead scoring') }}
                        </li>
                    </ul>
                    @if(Route::has('register'))
                        <a href="{{ route('register') }}" class="mt-8 block w-full rounded-lg bg-purple-600 py-2.5 text-center text-sm font-semibold text-white transition-colors hover:bg-purple-700">
                            {{ __('Start Free Trial') }}
                        </a>
                    @endif
                </div>

                {{-- Enterprise Plan --}}
                <div class="rounded-2xl border border-zinc-200 bg-white p-8 dark:border-zinc-800 dark:bg-zinc-900">
                    <h3 class="text-lg font-semibold">{{ __('Enterprise') }}</h3>
                    <p class="mt-2 text-sm text-zinc-500">{{ __('For large teams') }}</p>
                    <p class="mt-6"><span class="text-4xl font-bold">{{ __('Custom') }}</span></p>
                    <ul class="mt-8 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                        <li class="flex items-center gap-2">
                            <svg class="size-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('Unlimited channels') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="size-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('Unlimited AI responses') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="size-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('Custom AI training') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="size-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('Unlimited team members') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="size-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('Priority support') }}
                        </li>
                    </ul>
                    <a href="{{ route('contact') }}" class="mt-8 block w-full rounded-lg border border-zinc-300 py-2.5 text-center text-sm font-semibold transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                        {{ __('Contact Sales') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

</x-layouts.marketing>
