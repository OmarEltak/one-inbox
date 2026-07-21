<x-layouts.marketing
    :title="$config['keyword'] . ' — OT1-Pro'"
    :description="$config['subhead']"
    :canonical="url('/unified-inbox-for-' . $role)"
>

@push('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        @foreach($config['faq'] as $i => [$q, $a])
        {
            "@@type": "Question",
            "name": "{{ addslashes($q) }}",
            "acceptedAnswer": { "@@type": "Answer", "text": "{{ addslashes($a) }}" }
        }@if(!$loop->last),@endif
        @endforeach
    ]
}
</script>
@endpush

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-white via-indigo-50/60 to-white py-24 text-zinc-900">
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-1.5 text-sm font-medium text-indigo-700">
                        {{ $config['role_label'] }}
                    </span>
                    <h1 class="mt-5 text-4xl font-bold leading-tight tracking-tight sm:text-5xl">
                        {{ $config['keyword'] }}
                    </h1>
                    <p class="mt-4 text-2xl font-semibold text-indigo-600">{{ $config['headline'] }}</p>
                    <p class="mt-5 text-lg text-zinc-700">
                        {{ $config['subhead'] }}
                    </p>
                    <div class="mt-8 flex flex-wrap gap-4">
                        <a href="{{ route('register') }}" class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white transition-colors hover:bg-indigo-700">
                            {{ __('Start Free') }}
                        </a>
                        <a href="{{ route('pricing') }}" class="rounded-xl border border-zinc-300 px-6 py-3 font-semibold text-zinc-700 transition-colors hover:border-zinc-400">
                            {{ __('See Pricing') }}
                        </a>
                    </div>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-white p-6">
                    <p class="mb-4 text-sm font-semibold text-zinc-600">{{ __('Real results for') }} {{ $config['role_label'] }}</p>
                    @foreach($config['metrics'] as [$label, $value, $sub])
                    <div class="mb-4 rounded-lg bg-zinc-100 px-4 py-3">
                        <p class="text-xs text-zinc-500">{{ $label }}</p>
                        <p class="text-2xl font-bold text-indigo-600">{{ $value }}</p>
                        <p class="text-xs text-zinc-500">{{ $sub }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Pain Points --}}
    <section class="py-20">
        <div class="mx-auto max-w-4xl px-6">
            <h2 class="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">{{ __('Why') }} {{ $config['role_label'] }} {{ __('Need a Unified Inbox') }}</h2>
            <p class="mt-4 text-lg text-zinc-600">{{ $config['pain_intro'] }}</p>
            <ul class="mt-8 space-y-4">
                @foreach($config['pain_points'] as $pain)
                <li class="flex gap-3 rounded-lg border border-zinc-200 bg-white p-5">
                    <span class="mt-1 shrink-0 text-red-500">✗</span>
                    <span class="text-zinc-700">{{ $pain }}</span>
                </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Use Cases --}}
    <section class="bg-zinc-50 py-20">
        <div class="mx-auto max-w-6xl px-6">
            <div class="text-center">
                <h2 class="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">{{ __('What OT1-Pro Does for') }} {{ $config['role_label'] }}</h2>
                <p class="mt-3 text-zinc-600">{{ __('Six ways a unified inbox with AI transforms daily work.') }}</p>
            </div>
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($config['use_cases'] as $i => [$icon, $title, $desc])
                <div class="rounded-xl border p-5 transition-colors {{ $i === 0
                    ? 'lg:col-span-2 lg:p-7 border-indigo-200 bg-indigo-50/60'
                    : 'border-zinc-200 bg-white' }}">
                    <div class="text-2xl">{{ $icon }}</div>
                    <h3 class="mt-3 font-semibold">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-zinc-600">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Cross-links to related landing pages --}}
    <section class="py-16">
        <div class="mx-auto max-w-6xl px-6">
            <h2 class="mb-8 text-2xl font-bold text-zinc-900">{{ __('Unified Inbox for Other Teams') }}</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach(App\Http\Controllers\VerticalLandingController::roleSlugs() as $otherRole)
                    @if($otherRole !== $role)
                    <a href="{{ url('/unified-inbox-for-' . $otherRole) }}" class="rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 transition-colors hover:border-indigo-300 hover:text-indigo-700">
                        Unified Inbox for {{ ucwords(str_replace('-', ' ', $otherRole)) }}
                    </a>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="bg-zinc-50 py-20">
        <div class="mx-auto max-w-3xl px-6">
            <h2 class="mb-10 text-center text-3xl font-bold">{{ __('Frequently Asked Questions') }}</h2>
            <div class="space-y-4">
                @foreach($config['faq'] as [$q, $a])
                <div x-data="{ open: false }" class="rounded-xl border border-zinc-200 bg-white">
                    <button @click="open = !open" class="flex w-full items-center justify-between px-5 py-4 text-left font-medium">
                        <span>{{ $q }}</span>
                        <svg class="size-5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </button>
                    <div x-show="open" x-collapse class="border-t border-zinc-100 px-5 py-4 text-zinc-600">
                        {{ $a }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="border-t border-zinc-200 bg-white py-20 lg:py-28">
        <div class="mx-auto max-w-3xl px-6 text-center">
            <h2 class="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">{{ __('Ready to Unify Your Inbox?') }}</h2>
            <p class="mx-auto mt-5 max-w-xl text-lg text-zinc-600">{{ __('One inbox for every channel, AI that works 24/7, permanent free tier. Setup in 10 minutes.') }}</p>
            <a href="{{ route('register') }}" class="mt-10 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-7 py-3.5 text-base font-semibold text-white shadow-sm transition-all hover:bg-indigo-700 hover:shadow-md">
                {{ __('Get Started Free') }}
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
            </a>
        </div>
    </section>

</x-layouts.marketing>
