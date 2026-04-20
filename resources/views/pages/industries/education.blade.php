<x-layouts.marketing
    :title="__('WhatsApp Inbox for Schools & Education — One Inbox')"
    :description="__('Handle student inquiries, enrollment questions, and parent communications via WhatsApp, Instagram, and Facebook. AI responds instantly to every inquiry, 24/7.')"
    :canonical="route('industry.education')"
>

@push('schema')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        {
            "@type": "Question",
            "name": "{{ addslashes(__('Can the AI answer enrollment and admission questions?')) }}",
            "acceptedAnswer": { "@type": "Answer", "text": "{{ addslashes(__('Yes. You configure the AI with your programs, fees, admission requirements, and deadlines. It answers prospective student inquiries accurately and around the clock — a huge advantage during enrollment season.')) }}" }
        },
        {
            "@type": "Question",
            "name": "{{ addslashes(__('Can it handle Arabic and English inquiries?')) }}",
            "acceptedAnswer": { "@type": "Answer", "text": "{{ addslashes(__('Yes. The AI detects the language the student or parent is writing in and responds in the same language — no configuration needed. Ideal for bilingual institutions.')) }}" }
        }
    ]
}
</script>
@endpush

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-zinc-950 via-purple-950/40 to-zinc-950 py-24 text-white">
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full border border-purple-500/30 bg-purple-500/10 px-4 py-1.5 text-sm font-medium text-purple-300">
                        {{ __('Education') }}
                    </span>
                    <h1 class="mt-5 text-4xl font-bold leading-tight tracking-tight sm:text-5xl">
                        {!! __('Answer Every <span class="text-purple-400">Student Inquiry</span> Instantly — AI-Powered WhatsApp for Education') !!}
                    </h1>
                    <p class="mt-5 text-lg text-zinc-300">
                        {{ __('Prospective students and parents message on WhatsApp and Instagram expecting fast answers about courses, fees, and enrollment. One Inbox ensures they always get one — instantly.') }}
                    </p>
                    <div class="mt-8 flex flex-wrap gap-4">
                        <a href="{{ route('register') }}" class="rounded-xl bg-purple-600 px-6 py-3 font-semibold text-white transition-colors hover:bg-purple-700">
                            {{ __('Start Free') }}
                        </a>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    @php
                    $stats = [
                        [__('Avg. first response'), '< 1 min', __('vs. hours manually')],
                        [__('Enrollment inquiries handled by AI'), '85%', ''],
                        [__('Languages supported'), __('Arabic + English'), __('auto-detect')],
                        [__('Available'), '24 / 7', __('incl. holidays')],
                    ];
                    @endphp
                    @foreach($stats as [$label, $value, $sub])
                    <div class="rounded-xl bg-zinc-800 p-4 text-center">
                        <p class="text-xs text-zinc-500">{{ $label }}</p>
                        <p class="mt-1 text-xl font-bold text-purple-400">{{ $value }}</p>
                        @if($sub)<p class="text-xs text-zinc-600">{{ $sub }}</p>@endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Use Cases --}}
    <section class="py-20">
        <div class="mx-auto max-w-6xl px-6">
            <h2 class="mb-10 text-center text-3xl font-bold">{{ __('What the AI Handles for Educational Institutions') }}</h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @php
                $usecases = [
                    ['📚', __('Course & Program Info'), __('Fees, duration, schedule, prerequisites, certificates. The AI knows your catalog and answers instantly.')],
                    ['📝', __('Enrollment Process'), __('Step-by-step guidance on how to enroll, what documents are needed, and upcoming intake deadlines.')],
                    ['💳', __('Fee & Payment Questions'), __('Tuition breakdown, payment plans, installment options, and financial aid information.')],
                    ['📅', __('Open Day & Tour Scheduling'), __('Collect name, contact, and preferred date for campus visits or online info sessions.')],
                    ['👨‍👩‍👧', __('Parent Communications'), __('Answer parent questions about curriculum, progress tracking, and school policies in Arabic or English.')],
                    ['🏆', __('Scholarship Inquiries'), __('Provide accurate information on available scholarships, eligibility criteria, and application process.')],
                ];
                @endphp
                @foreach($usecases as [$icon, $title, $desc])
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-2xl">{{ $icon }}</div>
                    <h3 class="mt-3 font-semibold">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="bg-zinc-50 py-20 dark:bg-zinc-900/40">
        <div class="mx-auto max-w-3xl px-6">
            <h2 class="mb-10 text-center text-3xl font-bold">{{ __('Frequently Asked Questions') }}</h2>
            @php
            $faqs = [
                [__('Can the AI answer enrollment and admission questions?'), __('Yes. You configure the AI with your programs, fees, admission requirements, and deadlines. It answers prospective student inquiries accurately and around the clock — a huge advantage during enrollment season.')],
                [__('Can it handle Arabic and English inquiries?'), __('Yes. The AI detects the language the student or parent is writing in and responds in the same language — no configuration needed. Ideal for bilingual institutions.')],
                [__('Can multiple departments use the same inbox?'), __('Yes. Use assignment rules to route enrollment inquiries to admissions, fee questions to finance, and academic questions to the relevant department — all from one shared inbox.')],
                [__('Does it work for universities, schools, and training centers?'), __('Yes. One Inbox works for any educational institution handling inquiries via WhatsApp or social media — from language schools to universities.')],
            ];
            @endphp
            <div class="space-y-4">
                @foreach($faqs as [$q, $a])
                <div x-data="{ open: false }" class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <button @click="open = !open" class="flex w-full items-center justify-between px-5 py-4 text-left font-medium">
                        <span>{{ $q }}</span>
                        <svg class="size-5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </button>
                    <div x-show="open" x-collapse class="border-t border-zinc-100 px-5 py-4 text-zinc-600 dark:border-zinc-700 dark:text-zinc-400">
                        {{ $a }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="bg-gradient-to-br from-purple-600 to-blue-600 py-20 text-white">
        <div class="mx-auto max-w-3xl px-6 text-center">
            <h2 class="text-3xl font-bold">{{ __('Turn Inquiries Into Enrollments — Automatically') }}</h2>
            <p class="mt-3 text-purple-100">{{ __('AI answers every student inquiry instantly. Your admissions team closes the ones that matter.') }}</p>
            <a href="{{ route('register') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3 font-semibold text-purple-700 transition-all hover:bg-purple-50">
                {{ __('Get Started Free') }}
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
            </a>
        </div>
    </section>

</x-layouts.marketing>
