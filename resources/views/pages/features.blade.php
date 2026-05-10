<x-layouts.marketing
    :title="__('Features — Unified Inbox, AI Responder & Lead Scoring | One Inbox')"
    :description="__('Discover One Inbox features: manage Facebook, Instagram, WhatsApp & Telegram in one place. AI sales responder, lead scoring, analytics, and team collaboration.')">

@push('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        {
            "@@type": "Question",
            "name": "Which messaging platforms does One Inbox support?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Facebook Messenger, Instagram DMs, WhatsApp Business, and Telegram are fully supported today. TikTok DMs and LinkedIn Messages are in active development."}
        },
        {
            "@@type": "Question",
            "name": "Do I need a developer to set up One Inbox?",
            "acceptedAnswer": {"@@type": "Answer", "text": "No. Each platform is connected through a guided OAuth flow. WhatsApp uses an in-app QR connect that works without writing any code."}
        },
        {
            "@@type": "Question",
            "name": "How does the AI sales responder learn my product?",
            "acceptedAnswer": {"@@type": "Answer", "text": "You provide product information, pricing, and brand voice in a structured form. The AI uses that context plus the live conversation to generate replies and route hot leads to your team."}
        },
        {
            "@@type": "Question",
            "name": "Can my whole team use the same inbox?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. Team members are invited with role-based permissions. Conversations can be assigned, internal notes are kept separate from customer-visible messages, and an audit trail records every change."}
        }
    ]
}
</script>
@endpush

    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="text-center animate-fade-in-up">
                <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">{{ __('Powerful features for modern sales teams') }}</h1>
                <p class="mt-6 text-lg text-zinc-600 dark:text-zinc-400 max-w-3xl mx-auto">
                    {{ __('One Inbox gives sales and support teams a single place to manage every customer conversation across WhatsApp, Instagram, Facebook, and Telegram — with an AI agent that qualifies leads, answers questions, and hands off hot prospects automatically.') }}
                </p>
                <p class="mt-4 text-base text-zinc-500 dark:text-zinc-500 max-w-3xl mx-auto">
                    {{ __('Stop juggling four different apps and missing messages. Everything below is included on every plan, including the free tier.') }}
                </p>
            </div>

            <div class="mt-20 space-y-24">

                {{-- Feature: Unified Inbox --}}
                <div class="grid items-center gap-12 lg:grid-cols-2" x-data x-intersect.once="$el.classList.add('animate-slide-in-left')" style="opacity:0">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                            {{ __('Core Feature') }}
                        </div>
                        <h2 class="mt-4 text-3xl font-bold">{{ __('Unified Inbox') }}</h2>
                        <p class="mt-4 text-zinc-600 dark:text-zinc-400">
                            {{ __('Manage all your Facebook, Instagram, WhatsApp, and Telegram conversations from a single, clean interface. No more switching between apps.') }}
                        </p>
                        <ul class="mt-6 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Real-time message sync') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Filter by platform, status, or contact') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Send images, files, and emoji') }}
                            </li>
                        </ul>
                    </div>
                    <div class="card-hover relative overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50 p-8 dark:border-zinc-800 dark:bg-zinc-900">
                        {{-- Decorative grid dots --}}
                        <div class="pointer-events-none absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle, currentColor 1px, transparent 1px); background-size: 24px 24px;"></div>
                        <div class="pointer-events-none absolute -top-12 -right-12 size-32 rounded-full bg-blue-500/10 blur-2xl"></div>
                        <div class="relative space-y-4">
                            <div class="flex items-center gap-4">
                                <div class="flex size-12 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-900/30">
                                    <svg class="size-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z" /></svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-blue-600">4</p>
                                    <p class="text-sm text-zinc-500">{{ __('Platforms supported') }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-lg bg-white px-4 py-3 dark:bg-zinc-800">
                                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ __('Real-time') }}</p>
                                    <p class="text-xs text-zinc-500">{{ __('Message sync') }}</p>
                                </div>
                                <div class="rounded-lg bg-white px-4 py-3 dark:bg-zinc-800">
                                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">∞</p>
                                    <p class="text-xs text-zinc-500">{{ __('Messages') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Feature: AI Sales Responder --}}
                <div class="grid items-center gap-12 lg:grid-cols-2" x-data x-intersect.once="$el.classList.add('animate-slide-in-right')" style="opacity:0">
                    <div class="order-2 lg:order-1 card-hover relative overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50 p-8 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="pointer-events-none absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle, currentColor 1px, transparent 1px); background-size: 24px 24px;"></div>
                        <div class="pointer-events-none absolute -bottom-12 -left-12 size-32 rounded-full bg-purple-500/10 blur-2xl"></div>
                        <div class="relative space-y-4">
                            <div class="flex items-center gap-4">
                                <div class="flex size-12 items-center justify-center rounded-xl bg-purple-100 dark:bg-purple-900/30">
                                    <svg class="size-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-purple-600">24/7</p>
                                    <p class="text-sm text-zinc-500">{{ __('Always responding') }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-lg bg-white px-4 py-3 dark:bg-zinc-800">
                                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">&lt;5s</p>
                                    <p class="text-xs text-zinc-500">{{ __('Avg response') }}</p>
                                </div>
                                <div class="rounded-lg bg-white px-4 py-3 dark:bg-zinc-800">
                                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">100+</p>
                                    <p class="text-xs text-zinc-500">{{ __('Languages') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="order-1 lg:order-2">
                        <div class="inline-flex items-center gap-2 rounded-full bg-purple-50 px-3 py-1 text-sm font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                            {{ __('AI Powered') }}
                        </div>
                        <h2 class="mt-4 text-3xl font-bold">{{ __('AI Sales Responder') }}</h2>
                        <p class="mt-4 text-zinc-600 dark:text-zinc-400">
                            {{ __('Your AI sales agent qualifies leads, handles objections, and guides conversations toward a close. Configure it with your products, pricing, and brand voice.') }}
                        </p>
                        <ul class="mt-6 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Custom brand voice and product knowledge') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Automatic lead qualification') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Seamless human handoff') }}
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Feature: Lead Scoring --}}
                <div class="grid items-center gap-12 lg:grid-cols-2" x-data x-intersect.once="$el.classList.add('animate-slide-in-left')" style="opacity:0">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-sm font-medium text-orange-700 dark:bg-orange-900/30 dark:text-orange-300">
                            {{ __('Intelligence') }}
                        </div>
                        <h2 class="mt-4 text-3xl font-bold">{{ __('Lead Scoring & Analytics') }}</h2>
                        <p class="mt-4 text-zinc-600 dark:text-zinc-400">
                            {{ __('AI automatically scores every lead based on conversation signals. Track AI performance, response times, conversion rates, and your entire lead pipeline in real-time.') }}
                        </p>
                        <ul class="mt-6 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Automatic lead scoring (0-100)') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Conversion funnel tracking') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Platform performance comparison') }}
                            </li>
                        </ul>
                    </div>
                    <div class="card-hover relative overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50 p-8 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="pointer-events-none absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle, currentColor 1px, transparent 1px); background-size: 24px 24px;"></div>
                        <div class="pointer-events-none absolute -top-12 -left-12 size-32 rounded-full bg-orange-500/10 blur-2xl"></div>
                        <div class="relative space-y-4">
                            <div class="flex items-center gap-4">
                                <div class="flex size-12 items-center justify-center rounded-xl bg-orange-100 dark:bg-orange-900/30">
                                    <svg class="size-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75Z" /></svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-orange-600">100+</p>
                                    <p class="text-sm text-zinc-500">{{ __('Scoring signals tracked') }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-lg bg-white px-4 py-3 dark:bg-zinc-800">
                                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">0-100</p>
                                    <p class="text-xs text-zinc-500">{{ __('Lead score') }}</p>
                                </div>
                                <div class="rounded-lg bg-white px-4 py-3 dark:bg-zinc-800">
                                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ __('Live') }}</p>
                                    <p class="text-xs text-zinc-500">{{ __('Dashboards') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Why teams choose One Inbox --}}
    <section class="border-y border-zinc-200 bg-zinc-50 py-20 lg:py-28 dark:border-zinc-800 dark:bg-zinc-900/50">
        <div class="mx-auto max-w-6xl px-6">
            <div class="text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Built for the way real sales teams work') }}</h2>
                <p class="mx-auto mt-5 max-w-3xl text-lg text-zinc-600 dark:text-zinc-400">
                    {{ __('Most CRMs were built for email and phone. Most chat tools were built for website visitors. One Inbox is the only tool built specifically for businesses whose customers reach them on WhatsApp, Instagram, Facebook, and Telegram — the platforms they actually use.') }}
                </p>
            </div>

            <div class="mt-14 grid gap-6 md:grid-cols-3">
                <div class="rounded-2xl border border-zinc-200 bg-white p-7 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex size-12 items-center justify-center rounded-xl bg-purple-100 dark:bg-purple-900/30">
                        <svg class="size-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" /></svg>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold">{{ __('Faster than email') }}</h3>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Customers expect a reply in minutes on WhatsApp, not days. Average AI response time is under five seconds, and human agents see new conversations the moment they arrive.') }}
                    </p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-white p-7 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex size-12 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-900/30">
                        <svg class="size-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold">{{ __('Built for WhatsApp Business API') }}</h3>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Connect through the official WhatsApp Business API for unlimited messages, no phone-number restrictions, and full automation. We handle the API setup so you do not have to read Meta documentation.') }}
                    </p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-white p-7 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex size-12 items-center justify-center rounded-xl bg-green-100 dark:bg-green-900/30">
                        <svg class="size-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold">{{ __('Pricing that scales with you') }}</h3>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Start free, upgrade when you grow. Mid-market teams pay one transparent monthly fee instead of per-seat license stacking, and enterprise gets dedicated support.') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Team Collaboration --}}
    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid items-center gap-12 lg:grid-cols-2" x-data x-intersect.once="$el.classList.add('animate-slide-in-left')" style="opacity:0">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-green-50 px-3 py-1 text-sm font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">
                        {{ __('Teamwork') }}
                    </div>
                    <h2 class="mt-4 text-3xl font-bold">{{ __('Team collaboration without the chaos') }}</h2>
                    <p class="mt-4 text-zinc-600 dark:text-zinc-400">
                        {{ __('Assign conversations to specific agents, leave private internal notes, and watch every team member work in the same shared inbox without overlapping or losing context. New hires can read the full history of any conversation on day one.') }}
                    </p>
                    <ul class="mt-6 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                        <li class="flex items-center gap-2">
                            <svg class="size-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('Role-based permissions (admin, agent, viewer)') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="size-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('Internal notes never visible to customers') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="size-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('Full audit trail of who replied and when') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="size-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('Quick replies your whole team can reuse') }}
                        </li>
                    </ul>
                </div>
                <div class="card-hover relative overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50 p-8 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="pointer-events-none absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle, currentColor 1px, transparent 1px); background-size: 24px 24px;"></div>
                    <div class="pointer-events-none absolute -top-12 -right-12 size-32 rounded-full bg-green-500/10 blur-2xl"></div>
                    <div class="relative space-y-4">
                        <div class="flex items-center gap-4">
                            <div class="flex size-12 items-center justify-center rounded-xl bg-green-100 dark:bg-green-900/30">
                                <svg class="size-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-green-600">∞</p>
                                <p class="text-sm text-zinc-500">{{ __('Team members') }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-lg bg-white px-4 py-3 dark:bg-zinc-800">
                                <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">3</p>
                                <p class="text-xs text-zinc-500">{{ __('Permission roles') }}</p>
                            </div>
                            <div class="rounded-lg bg-white px-4 py-3 dark:bg-zinc-800">
                                <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">100%</p>
                                <p class="text-xs text-zinc-500">{{ __('Audit logged') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Multi-Platform Coverage --}}
    <section class="border-t border-zinc-200 py-20 lg:py-28 dark:border-zinc-800">
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid items-center gap-12 lg:grid-cols-2" x-data x-intersect.once="$el.classList.add('animate-slide-in-right')" style="opacity:0">
                <div class="order-2 lg:order-1 card-hover relative overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50 p-8 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="pointer-events-none absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle, currentColor 1px, transparent 1px); background-size: 24px 24px;"></div>
                    <div class="pointer-events-none absolute -bottom-12 -right-12 size-32 rounded-full bg-pink-500/10 blur-2xl"></div>
                    <div class="relative grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-white px-4 py-3 dark:bg-zinc-800">
                            <p class="text-base font-bold text-zinc-900 dark:text-zinc-100">{{ __('WhatsApp') }}</p>
                            <p class="mt-1 text-xs text-zinc-500">{{ __('Business API + QR') }}</p>
                        </div>
                        <div class="rounded-lg bg-white px-4 py-3 dark:bg-zinc-800">
                            <p class="text-base font-bold text-zinc-900 dark:text-zinc-100">{{ __('Instagram') }}</p>
                            <p class="mt-1 text-xs text-zinc-500">{{ __('DMs + comments') }}</p>
                        </div>
                        <div class="rounded-lg bg-white px-4 py-3 dark:bg-zinc-800">
                            <p class="text-base font-bold text-zinc-900 dark:text-zinc-100">{{ __('Facebook') }}</p>
                            <p class="mt-1 text-xs text-zinc-500">{{ __('Page messages') }}</p>
                        </div>
                        <div class="rounded-lg bg-white px-4 py-3 dark:bg-zinc-800">
                            <p class="text-base font-bold text-zinc-900 dark:text-zinc-100">{{ __('Telegram') }}</p>
                            <p class="mt-1 text-xs text-zinc-500">{{ __('Bot + groups') }}</p>
                        </div>
                    </div>
                </div>
                <div class="order-1 lg:order-2">
                    <div class="inline-flex items-center gap-2 rounded-full bg-pink-50 px-3 py-1 text-sm font-medium text-pink-700 dark:bg-pink-900/30 dark:text-pink-300">
                        {{ __('Coverage') }}
                    </div>
                    <h2 class="mt-4 text-3xl font-bold">{{ __('Every platform your customers actually use') }}</h2>
                    <p class="mt-4 text-zinc-600 dark:text-zinc-400">
                        {{ __('Connect WhatsApp Business, Instagram DMs, Facebook Pages, and Telegram bots from a single dashboard. Each platform comes with a guided setup that handles the OAuth and webhook plumbing for you, so you never have to touch developer documentation.') }}
                    </p>
                    <ul class="mt-6 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                        <li class="flex items-center gap-2">
                            <svg class="size-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('Connect multiple WhatsApp numbers and Facebook Pages') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="size-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('Auto-reply to Instagram comments and DMs') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="size-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('TikTok DMs and LinkedIn Messages — coming soon') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="size-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('Email channels and web chat widget included') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="border-t border-zinc-200 bg-zinc-50 py-20 lg:py-28 dark:border-zinc-800 dark:bg-zinc-900/50">
        <div class="mx-auto max-w-3xl px-6">
            <h2 class="text-3xl font-bold tracking-tight">{{ __('Frequently asked questions') }}</h2>
            <div class="mt-10 divide-y divide-zinc-200 dark:divide-zinc-800">
                <div class="py-6">
                    <h3 class="text-lg font-semibold">{{ __('Which messaging platforms does One Inbox support?') }}</h3>
                    <p class="mt-3 text-zinc-600 dark:text-zinc-400">{{ __('Facebook Messenger, Instagram DMs, WhatsApp Business, and Telegram are fully supported today. TikTok DMs and LinkedIn Messages are in active development. Email and an embeddable web chat widget round out the channel set.') }}</p>
                </div>
                <div class="py-6">
                    <h3 class="text-lg font-semibold">{{ __('Do I need a developer to set up One Inbox?') }}</h3>
                    <p class="mt-3 text-zinc-600 dark:text-zinc-400">{{ __('No. Each platform is connected through a guided OAuth flow. WhatsApp uses an in-app QR connect that works without writing any code. Most teams are up and replying within an hour of signing up.') }}</p>
                </div>
                <div class="py-6">
                    <h3 class="text-lg font-semibold">{{ __('How does the AI sales responder learn my product?') }}</h3>
                    <p class="mt-3 text-zinc-600 dark:text-zinc-400">{{ __('You provide product information, pricing, and brand voice in a structured form. The AI uses that context plus the live conversation to generate replies, qualify leads, and route hot prospects to your team. You can override or take over from the AI at any point.') }}</p>
                </div>
                <div class="py-6">
                    <h3 class="text-lg font-semibold">{{ __('Can my whole team use the same inbox?') }}</h3>
                    <p class="mt-3 text-zinc-600 dark:text-zinc-400">{{ __('Yes. Team members are invited with role-based permissions. Conversations can be assigned, internal notes are kept separate from customer-visible messages, and a full audit trail records every change. Plans on the Starter tier and above support unlimited team members.') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-6xl px-6" x-data x-intersect.once="$el.classList.add('animate-fade-in-up')" style="opacity:0">
            <div class="overflow-hidden rounded-3xl bg-gradient-to-br from-purple-600 via-purple-700 to-blue-600 animate-gradient-shift p-10 text-center text-white sm:p-16 relative">
                <div class="pointer-events-none absolute inset-0 overflow-hidden">
                    <div class="absolute -top-24 -right-24 size-64 rounded-full bg-white/5 blur-2xl"></div>
                    <div class="absolute -bottom-24 -left-24 size-64 rounded-full bg-white/5 blur-2xl"></div>
                </div>
                <div class="relative">
                    <h2 class="text-3xl font-bold sm:text-4xl">{{ __('Ready to get started?') }}</h2>
                    <p class="mx-auto mt-4 max-w-xl text-lg text-purple-100">{{ __('Start your free trial today. No credit card required.') }}</p>
                    @if(Route::has('register'))
                        <a href="{{ route('register') }}" class="btn-shimmer arrow-slide group mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3.5 font-semibold text-purple-700 shadow-lg transition-all hover:bg-purple-50 hover:shadow-xl">
                            {{ __('Get Started Free') }}
                            <svg class="arrow-icon size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

</x-layouts.marketing>
