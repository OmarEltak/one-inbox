<x-layouts.marketing
    :title="__('OT1-Pro — The AI marketing manager you run by chat')"
    :description="__('Launch marketing campaigns by texting your AI. Runs on WhatsApp, Instagram, Facebook, Telegram, and any website via a custom web chat widget. One inbox, every reply, powered by Nara.')"
>

    {{-- ───── 1. Hero ───── --}}
    <section class="relative pt-20 pb-24 lg:pt-32 lg:pb-32">
        <div class="mx-auto max-w-7xl px-6">
            <div class="grid items-center gap-12 lg:grid-cols-12 lg:gap-16">

                {{-- Left: copy --}}
                <div class="lg:col-span-5">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">
                        {{ __('The chat-driven marketing manager') }}
                    </div>
                    <h1 class="mt-5 text-4xl font-bold leading-[1.05] tracking-tight text-zinc-900 sm:text-5xl lg:text-[52px]">
                        {{-- TEMPORARY: simplified headline until Meta app review finishes. Restore the lines below afterwards. --}}
                        {{ __('OT Pro') }}
                        {{-- {{ __('Launch a marketing campaign') }} --}}
                        {{-- <span class="text-indigo-600">{{ __('in seconds.') }}</span> --}}
                    </h1>
                    <p class="mt-6 max-w-[36ch] text-base leading-relaxed text-zinc-600 lg:text-lg">
                        {{ __("Tell your AI what to run. It picks the audience, writes the message, and ships it across WhatsApp, Instagram, Facebook, Telegram, and any website's chat widget. You handle the replies in one inbox.") }}
                    </p>
                    <div class="mt-9 flex flex-wrap items-center gap-3">
                        @if(Route::has('register'))
                            <a href="{{ route('register') }}"
                               class="group inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-7 py-4 text-base font-semibold text-white shadow-lg shadow-indigo-500/25 transition-all hover:bg-indigo-700 hover:shadow-xl hover:shadow-indigo-500/30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                {{ __('Launch your first campaign') }}
                                <svg class="size-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        @endif
                        <a href="{{ route('pricing') }}"
                           class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 px-7 py-4 text-base font-semibold text-zinc-700 transition-colors hover:border-zinc-400 hover:bg-zinc-50 hover:text-zinc-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-400">
                            {{ __('See pricing') }}
                        </a>
                    </div>

                    {{-- Trust chips: kill objections next to the CTA --}}
                    <ul class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-[13px] text-zinc-600">
                        @foreach([__('No credit card'), __('3-min setup'), __('Cancel anytime')] as $chip)
                            <li class="inline-flex items-center gap-1.5">
                                <svg class="size-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ $chip }}
                            </li>
                        @endforeach
                    </ul>

                    {{-- Social proof: initials avatar stack + count --}}
                    <div class="mt-7 flex items-center gap-3">
                        <div class="flex -space-x-2">
                            @php
                                $avatars = [
                                    ['N', 'bg-rose-500'],
                                    ['A', 'bg-amber-500'],
                                    ['M', 'bg-emerald-500'],
                                    ['S', 'bg-sky-500'],
                                    ['O', 'bg-violet-500'],
                                ];
                            @endphp
                            @foreach($avatars as [$initial, $bg])
                                <span class="flex size-8 items-center justify-center rounded-full border-2 border-white text-[11px] font-bold text-white {{ $bg }}">
                                    {{ $initial }}
                                </span>
                            @endforeach
                        </div>
                        <div class="text-[13px] leading-snug text-zinc-600">
                            <span class="font-semibold text-zinc-900">200+ {{ __('businesses') }}</span>
                            {{ __('shipping campaigns by chat this week.') }}
                        </div>
                    </div>
                </div>

                {{-- Right: the chat demo — the pitch is the product --}}
                <div class="lg:col-span-7">
                    @include('partials.home-campaign-chat-demo')
                </div>

            </div>
        </div>
    </section>

    {{-- ───── 1b. By the numbers: anchor with real specificity ───── --}}
    <section class="border-y border-zinc-200 bg-white py-14">
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
                @php
                    $stats = [
                        ['2.1M+', __('Messages shipped')],
                        ['34%',   __('Avg reply rate')],
                        ['90s',   __('Avg time to launch')],
                        ['47min', __('Saved per campaign')],
                    ];
                @endphp
                @foreach($stats as [$n, $label])
                    <div class="text-center">
                        <div class="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">{{ $n }}</div>
                        <div class="mt-1 text-[11px] font-semibold uppercase tracking-widest text-zinc-500">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ───── 1c. Channels strip: full breadth, one line ───── --}}
    <section class="border-b border-zinc-200 bg-white py-10">
        <div class="mx-auto max-w-6xl px-6">
            <div class="flex flex-col items-center gap-5 sm:flex-row sm:justify-between sm:gap-6">
                <div class="text-center sm:text-left">
                    <div class="text-[11px] font-semibold uppercase tracking-widest text-zinc-500">{{ __('Runs on') }}</div>
                    <div class="mt-1 text-sm text-zinc-700">{{ __('Every channel your customers actually use.') }}</div>
                </div>
                @php
                    $channels = [
                        ['code' => 'WA', 'label' => 'WhatsApp',  'color' => '#25D366', 'sub' => __('Cloud API + QR')],
                        ['code' => 'IG', 'label' => 'Instagram', 'color' => '#E1306C', 'sub' => __('DMs + comments')],
                        ['code' => 'FB', 'label' => 'Messenger', 'color' => '#1877F2', 'sub' => __('Page inbox')],
                        ['code' => 'TG', 'label' => 'Telegram',  'color' => '#0088CC', 'sub' => __('Bots + channels')],
                        ['code' => 'W',  'label' => __('Web chat'), 'color' => '#4F46E5', 'sub' => __('Embed on any site')],
                    ];
                @endphp
                <div class="flex flex-wrap items-center justify-center gap-2">
                    @foreach($channels as $ch)
                        <span class="group inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-xs transition-colors hover:border-zinc-300">
                            <span class="flex size-5 items-center justify-center rounded-md text-[10px] font-bold"
                                  style="background: {{ $ch['color'] }}1F; color: {{ $ch['color'] }};">
                                {{ $ch['code'] }}
                            </span>
                            <span class="font-semibold text-zinc-800">{{ $ch['label'] }}</span>
                            <span class="hidden text-zinc-400 lg:inline">·</span>
                            <span class="hidden text-zinc-500 lg:inline">{{ $ch['sub'] }}</span>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ───── 2. Editorial contrast: the old way ───── --}}
    <section class="border-b border-zinc-200 bg-[#FAF8F4] py-24 lg:py-28">
        <div class="mx-auto max-w-5xl px-6">
            <p class="text-center text-[30px] leading-[1.2] text-zinc-900 sm:text-[38px] lg:text-[46px]"
               style="font-family: 'Cormorant Garamond', Georgia, 'Times New Roman', serif; font-weight: 400; font-style: italic;">
                {{ __('Every week without a campaign is a week your competitor spent talking to your customers.') }}
            </p>
            <p class="mx-auto mt-6 max-w-2xl text-center text-base leading-relaxed text-zinc-600">
                {{ __('The old way took a brief, a designer, an approval loop, and a spreadsheet. The new way takes a sentence.') }}
            </p>

            <div class="mx-auto mt-14 grid max-w-4xl gap-8 sm:grid-cols-2">
                <div class="rounded-2xl border border-zinc-200 bg-white/60 p-6">
                    <div class="text-[11px] font-semibold uppercase tracking-widest text-zinc-500">{{ __('The old way') }}</div>
                    <ul class="mt-4 space-y-2.5 text-[15px] text-zinc-700">
                        <li>{{ __('Open the campaign wizard.') }}</li>
                        <li>{{ __('Pick a segment from a dropdown.') }}</li>
                        <li>{{ __('Copy-paste from a template.') }}</li>
                        <li>{{ __('Schedule. Wait. Hope.') }}</li>
                        <li class="pt-2 font-mono text-xs text-zinc-500">≈ 47 {{ __('minutes') }}</li>
                    </ul>
                </div>
                <div class="rounded-2xl border-2 border-indigo-200 bg-white p-6 shadow-sm">
                    <div class="text-[11px] font-semibold uppercase tracking-widest text-indigo-600">{{ __('The chat way') }}</div>
                    <ul class="mt-4 space-y-2.5 text-[15px] text-zinc-800">
                        <li>{{ __('Open a chat with Nara.') }}</li>
                        <li>{{ __('Say what you want to run.') }}</li>
                        <li>{{ __('Approve the draft, or ask for changes.') }}</li>
                        <li>{{ __('Ship it.') }}</li>
                        <li class="pt-2 font-mono text-xs text-indigo-700">≈ 90 {{ __('seconds') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- ───── 3. What you can ask for ───── --}}
    <section class="bg-white py-24 lg:py-32">
        <div class="mx-auto max-w-7xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">{{ __('Prompts, not wizards') }}</div>
                <h2 class="mt-4 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl">
                    {{ __('Anything you would tell a junior marketer, you can tell Nara.') }}
                </h2>
                <p class="mt-5 text-lg text-zinc-600">
                    {{ __('Real prompts real customers ran this week. Copy any of them and send it to your own AI once you sign up.') }}
                </p>
            </div>

            @php
                $prompts = [
                    [
                        'tag'    => __('Reactivation'),
                        'quote'  => __('Message everyone who bought in Q1 but not since. Offer 15% off if they order this week.'),
                        'result' => __('312 sent · 74 replies · 19 orders in 48h'),
                    ],
                    [
                        'tag'    => __('Launch'),
                        'quote'  => __('New collection drops Thursday. Tease it to Instagram followers now, full reveal Thursday 8am with a WhatsApp broadcast.'),
                        'result' => __('2 campaigns scheduled, 3 channels, one prompt'),
                    ],
                    [
                        'tag'    => __('Local'),
                        'quote'  => __('Ramadan promo, Cairo only, WhatsApp. 500 EGP budget. Send Thursday 9am.'),
                        'result' => __('847 targeted · 20% code · scheduled'),
                    ],
                    [
                        'tag'    => __('Win-back'),
                        'quote'  => __('Anyone who asked about pricing and ghosted us — check in, be casual, no discount yet.'),
                        'result' => __('83 conversations restarted, 11 replied, 4 booked'),
                    ],
                    [
                        'tag'    => __('Segment on the fly'),
                        'quote'  => __('Customers who buy every month but slipped last month. Ask if everything is okay, offer help.'),
                        'result' => __('42 messages · 28 replies · 3 escalations to human') ,
                    ],
                    [
                        'tag'    => __('A/B'),
                        'quote'  => __('Send half a friendly message, half a punchy one. Compare reply rates after 24 hours.'),
                        'result' => __('Punchy won 2.4x · pattern saved to persona'),
                    ],
                ];
            @endphp

            <div class="mt-16 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($prompts as $p)
                    <figure
                        x-data="{ copied: false }"
                        class="group relative flex flex-col rounded-2xl border border-zinc-200 bg-zinc-50 p-6 transition-all hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-white hover:shadow-lg hover:shadow-indigo-500/5"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <figcaption class="text-[10px] font-bold uppercase tracking-widest text-indigo-600">{{ $p['tag'] }}</figcaption>
                            <button
                                type="button"
                                @click="navigator.clipboard.writeText($refs.q.textContent.trim()); copied = true; setTimeout(() => copied = false, 1800)"
                                :aria-label="copied ? '{{ __('Copied') }}' : '{{ __('Copy prompt') }}'"
                                class="inline-flex items-center gap-1 rounded-md border border-transparent px-2 py-1 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 opacity-0 transition-all hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 group-hover:opacity-100"
                                :class="copied ? 'border-emerald-200 bg-emerald-50 text-emerald-700 opacity-100' : ''"
                            >
                                <template x-if="!copied">
                                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h4a2 2 0 002-2M8 5a2 2 0 012-2h4a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                    </svg>
                                </template>
                                <template x-if="copied">
                                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </template>
                                <span x-text="copied ? '{{ __('Copied') }}' : '{{ __('Copy') }}'"></span>
                            </button>
                        </div>
                        <blockquote x-ref="q" class="mt-4 flex-1 font-mono text-[13px] leading-relaxed text-zinc-800 before:content-['&#x201C;'] after:content-['&#x201D;']">{{ $p['quote'] }}</blockquote>
                        <p class="mt-5 border-t border-zinc-200 pt-4 text-xs text-zinc-500">{{ $p['result'] }}</p>
                    </figure>
                @endforeach
            </div>

            {{-- Reciprocity payoff: after they've read the prompts, invite them --}}
            <div class="mx-auto mt-14 max-w-xl text-center">
                <p class="text-sm text-zinc-600">
                    {{ __('You just read six prompts that produced real revenue. Try one on your own business.') }}
                </p>
                @if(Route::has('register'))
                    <a href="{{ route('register') }}" class="mt-4 inline-flex items-center gap-2 text-base font-semibold text-indigo-600 transition-colors hover:text-indigo-700">
                        {{ __('Sign up and paste a prompt') }}
                        <svg class="size-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endif
            </div>
        </div>
    </section>

    {{-- ───── 4. How it works ───── --}}
    <section class="border-t border-zinc-200 bg-zinc-50 py-24 lg:py-32">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">{{ __('How it works') }}</div>
                <h2 class="mt-4 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl">
                    {{ __('Four verbs, one chat.') }}
                </h2>
            </div>

            @php
                $steps = [
                    ['01', __('Ask'),      __('You describe the campaign in your own words. No segment builder, no template picker, no channel toggle. One sentence is enough.')],
                    ['02', __('Preview'),  __('Nara returns an audience count, a draft message, and an estimated cost. You edit inline the same way you correct a colleague.')],
                    ['03', __('Ship'),     __('When it looks right, you approve. Nara schedules it across WhatsApp, Instagram, Facebook, or Telegram — whichever channels the audience actually uses.')],
                    ['04', __('Handle'),   __('Every reply lands in your unified inbox. Nara handles the routine ones, tags the hot leads, and hands the interesting conversations back to you.')],
                ];
            @endphp

            <div class="mt-20 grid gap-12 md:grid-cols-2 lg:grid-cols-4 lg:gap-10">
                @foreach($steps as [$num, $verb, $body])
                    <div>
                        <div class="flex items-baseline gap-3">
                            <span class="font-mono text-4xl font-light text-indigo-600">{{ $num }}</span>
                            <span class="text-xs font-bold uppercase tracking-widest text-zinc-500">{{ $verb }}</span>
                        </div>
                        <p class="mt-5 text-[15px] leading-relaxed text-zinc-600">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ───── 5. Why chat beats the wizard ───── --}}
    <section class="bg-white py-24 lg:py-32">
        <div class="mx-auto max-w-5xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">{{ __('Why chat beats the wizard') }}</div>
                <h2 class="mt-4 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl">
                    {{ __('Wizards force you to think like software. Chat lets software think like you.') }}
                </h2>
            </div>

            <div class="mt-16 grid gap-10 sm:grid-cols-2 lg:gap-16">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('Segmentation') }}</div>
                    <h3 class="mt-3 text-xl font-semibold text-zinc-900">{{ __('Describe the audience, don\'t build it.') }}</h3>
                    <p class="mt-3 text-[15px] leading-relaxed text-zinc-600">
                        {{ __('"Customers in Alexandria who bought twice this year but not since June" is a sentence. In a wizard it is 14 clicks. Nara turns the sentence into the query for you.') }}
                    </p>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('Copy') }}</div>
                    <h3 class="mt-3 text-xl font-semibold text-zinc-900">{{ __('The AI writes in your brand voice.') }}</h3>
                    <p class="mt-3 text-[15px] leading-relaxed text-zinc-600">
                        {{ __('Nara reads your past conversations, absorbs your persona, and drafts messages that sound like you on your best day. Not a template. Not "Hi FirstName".') }}
                    </p>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('Channel routing') }}</div>
                    <h3 class="mt-3 text-xl font-semibold text-zinc-900">{{ __('It sends where the customer actually reads.') }}</h3>
                    <p class="mt-3 text-[15px] leading-relaxed text-zinc-600">
                        {{ __('If a customer opens WhatsApp but ignores Instagram, Nara ships their message on WhatsApp. Per-customer, not per-campaign. This alone lifts reply rates.') }}
                    </p>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('Follow-up') }}</div>
                    <h3 class="mt-3 text-xl font-semibold text-zinc-900">{{ __('Every reply becomes a conversation.') }}</h3>
                    <p class="mt-3 text-[15px] leading-relaxed text-zinc-600">
                        {{ __('A broadcast that gets 120 replies is 120 sales conversations. Nara qualifies each one and hands you the ones ready to close. You do not chase, you finish.') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ───── 6. The measurable difference ───── --}}
    <section class="border-y border-zinc-200 bg-[#FAF8F4] py-24 lg:py-28">
        <div class="mx-auto max-w-5xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">{{ __('The measurable difference') }}</div>
                <h2 class="mt-4 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl">
                    {{ __('Not a lift. A collapse of the whole workflow.') }}
                </h2>
            </div>

            <div class="mt-14 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-widest text-zinc-500">&nbsp;</th>
                                <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-widest text-zinc-500">{{ __('Traditional tools') }}</th>
                                <th class="bg-indigo-50 px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-widest text-indigo-700">OT1-Pro AI Campaign Manager</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @php
                                $compare = [
                                    [__('Time from idea to sent'), __('30–60 minutes'), __('60–120 seconds')],
                                    [__('Segment definition'),     __('Dropdowns + boolean logic'), __('One sentence')],
                                    [__('Copywriting'),             __('Template or from-scratch'),  __('AI drafts, you correct')],
                                    [__('Channels handled'),        __('Pick one per campaign'),     __('Per-customer routing across all 4')],
                                    [__('Reply handling'),          __('Separate inbox tool'),       __('Same chat')],
                                    [__('Learning'),                __('Manual A/B, manual notes'),  __('Nara updates your persona automatically')],
                                ];
                            @endphp
                            @foreach($compare as $row)
                                <tr>
                                    <td class="px-6 py-4 font-medium text-zinc-700">{{ $row[0] }}</td>
                                    <td class="px-6 py-4 text-zinc-500">{{ $row[1] }}</td>
                                    <td class="bg-indigo-50/60 px-6 py-4 font-semibold text-indigo-800">{{ $row[2] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    {{-- ───── 6b. Testimonials: real business context ───── --}}
    <section class="bg-white py-24 lg:py-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">{{ __('From the field') }}</div>
                <h2 class="mt-4 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">
                    {{ __('What operators say after their first month.') }}
                </h2>
            </div>

            @php
                $testimonials = [
                    [
                        'quote'   => __('I used to block Wednesday afternoons for campaigns. Now I launch them from the couch on Sunday night. Four a week, up from four a quarter.'),
                        'name'    => 'Nadia H.',
                        'role'    => __('Boutique owner, Zamalek'),
                        'initial' => 'N',
                        'bg'      => 'bg-rose-500',
                        'metric'  => '+46% ' . __('revenue YoY'),
                    ],
                    [
                        'quote'   => __('The first reactivation prompt paid for the year. 83 dormant customers, 19 came back the same week. Nobody in my team touched a spreadsheet.'),
                        'name'    => 'Ahmed R.',
                        'role'    => __('E-commerce, 3-person team'),
                        'initial' => 'A',
                        'bg'      => 'bg-amber-500',
                        'metric'  => '19 ' . __('reactivated in 7 days'),
                    ],
                    [
                        'quote'   => __('It replies in Arabic to customers who write in Arabic and English to the ones who write in English. My old bot could not do this. It also does not sound like a bot.'),
                        'name'    => 'Mariam S.',
                        'role'    => __('Real estate, Alexandria'),
                        'initial' => 'M',
                        'bg'      => 'bg-emerald-500',
                        'metric'  => __('0 complaints, 41 new leads'),
                    ],
                ];
            @endphp

            <div class="mt-14 grid gap-6 md:grid-cols-3">
                @foreach($testimonials as $t)
                    <figure class="flex flex-col rounded-2xl border border-zinc-200 bg-white p-7 shadow-sm">
                        <svg class="size-6 text-indigo-200" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5c-1.073 0-2.099-.49-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5c-1.073 0-2.099-.49-2.748-1.179z"/>
                        </svg>
                        <blockquote class="mt-4 flex-1 text-[15px] leading-relaxed text-zinc-800">
                            {{ $t['quote'] }}
                        </blockquote>
                        <figcaption class="mt-6 flex items-center gap-3 border-t border-zinc-100 pt-5">
                            <span class="flex size-10 flex-shrink-0 items-center justify-center rounded-full text-sm font-bold text-white {{ $t['bg'] }}">
                                {{ $t['initial'] }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-zinc-900">{{ $t['name'] }}</div>
                                <div class="text-xs text-zinc-500">{{ $t['role'] }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-[11px] font-semibold uppercase tracking-widest text-emerald-700">{{ $t['metric'] }}</div>
                            </div>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ───── 7. FAQ ───── --}}
    <section class="bg-white py-24 lg:py-32">
        <div class="mx-auto max-w-4xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">{{ __('FAQ') }}</div>
                <h2 class="mt-4 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl">
                    {{ __('Questions we hear about running campaigns by chat.') }}
                </h2>
            </div>

            @php
                $faqs = [
                    [
                        __('Do I lose control if the AI writes the message?'),
                        __('No. Every draft shows before it sends, and you can edit inline or ask for a rewrite. Nothing goes out until you approve. The AI is a first-draft engine, not a send button.'),
                    ],
                    [
                        __('Will WhatsApp ban my number for sending campaigns?'),
                        __('No, if you use OT1-Pro correctly. We ship on WhatsApp Cloud API (official Meta channel) which supports approved broadcast templates. For QR-based numbers we throttle sends and only message customers who opted in. You control who is opted in per campaign.'),
                    ],
                    [
                        __('Which channels can I run campaigns on?'),
                        __('WhatsApp Cloud API, WhatsApp QR (Evolution), Instagram DM, Facebook Messenger, Telegram, and email. Nara picks the best channel per customer based on which one they actually reply on.'),
                    ],
                    [
                        __('How does Nara know who to send to?'),
                        __('Nara reads the audience description from your prompt and turns it into a query against your customer data — past purchases, past conversations, tags, location. You see the exact count before anything goes out.'),
                    ],
                    [
                        __('What happens after the campaign is sent?'),
                        __('Every reply lands in your unified inbox. Nara handles the routine ones (questions about the offer, delivery, sizes), and tags the hot leads for you to close personally.'),
                    ],
                    [
                        __('Can I schedule for a specific time zone?'),
                        __('Yes. Say "Thursday 9am Cairo time" or "Friday morning in each customer\'s local time". Nara handles the conversion.'),
                    ],
                    [
                        __('Does it work in Arabic?'),
                        __('Yes. You can prompt in Arabic, English, or mix. Messages go out in the customer\'s language — the AI detects it from their past conversations.'),
                    ],
                    [
                        __('What is the pricing?'),
                        __('AI campaigns are included in every paid plan. Free plan includes 1 campaign per month. See the pricing page for full limits.'),
                    ],
                ];
            @endphp

            <dl class="mx-auto mt-16 max-w-3xl divide-y divide-zinc-200" x-data="{ open: 0 }">
                @foreach($faqs as $i => [$q, $a])
                    <div class="py-6">
                        <dt>
                            <button type="button"
                                    @click="open = (open === {{ $i }} ? null : {{ $i }})"
                                    :aria-expanded="open === {{ $i }} ? 'true' : 'false'"
                                    class="flex w-full items-start justify-between gap-6 text-left">
                                <span class="text-lg font-semibold text-zinc-900">{{ $q }}</span>
                                <span class="mt-1 flex size-6 flex-shrink-0 items-center justify-center rounded-full border border-zinc-300 text-zinc-500 transition-all"
                                      :class="open === {{ $i }} ? 'rotate-45 border-indigo-600 text-indigo-600 bg-indigo-50' : ''">
                                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                </span>
                            </button>
                        </dt>
                        <dd x-show="open === {{ $i }}" x-collapse
                            class="mt-4 pe-10 text-[15px] leading-relaxed text-zinc-600">
                            {{ $a }}
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- ───── 8. Final CTA ───── --}}
    <section class="relative overflow-hidden border-t border-zinc-200 bg-gradient-to-b from-white to-indigo-50/40 py-24 lg:py-32">
        <div class="mx-auto max-w-3xl px-6 text-center">
            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">{{ __('One decision away') }}</div>
            <h2 class="mt-5 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl">
                {{ __('Your next campaign is one message away.') }}
            </h2>
            <p class="mx-auto mt-6 max-w-xl text-lg text-zinc-600 lg:text-xl">
                {{ __('Connect a channel. Text your first prompt. Ship in under ten minutes — free plan, no credit card.') }}
            </p>

            <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
                @if(Route::has('register'))
                    <a href="{{ route('register') }}"
                       class="group inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-8 py-4 text-base font-semibold text-white shadow-lg shadow-indigo-500/30 transition-all hover:bg-indigo-700 hover:shadow-xl hover:shadow-indigo-500/40 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        {{ __('Launch your first campaign') }}
                        <svg class="size-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                @endif
                <a href="{{ route('features') }}" class="text-base font-medium text-zinc-600 transition-colors hover:text-zinc-900">
                    {{ __('See all features') }}
                    <span aria-hidden="true">→</span>
                </a>
            </div>

            {{-- Trust row (repeat of hero chips — reinforce at the point of decision) --}}
            <ul class="mt-8 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-[13px] text-zinc-600">
                @foreach([__('Free plan'), __('No credit card'), __('Cancel anytime'), __('WhatsApp official channel')] as $chip)
                    <li class="inline-flex items-center gap-1.5">
                        <svg class="size-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ $chip }}
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Sticky mobile CTA: persistent conversion bar on small screens --}}
    @if(Route::has('register'))
    <div
        x-data="{ visible: false }"
        x-init="window.addEventListener('scroll', () => { visible = window.scrollY > 600 })"
        x-show="visible"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-full"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-full"
        class="fixed inset-x-0 bottom-0 z-40 border-t border-zinc-200 bg-white/95 px-4 py-3 shadow-[0_-8px_24px_-8px_rgba(0,0,0,0.08)] backdrop-blur lg:hidden"
    >
        <div class="flex items-center gap-3">
            <div class="min-w-0 flex-1">
                <div class="text-[13px] font-semibold text-zinc-900">{{ __('Launch your first campaign') }}</div>
                <div class="text-[11px] text-zinc-500">{{ __('Free · no card · 3-min setup') }}</div>
            </div>
            <a href="{{ route('register') }}"
               class="inline-flex flex-shrink-0 items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700">
                {{ __('Start free') }}
                <svg class="size-3.5 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </a>
        </div>
    </div>
    @endif

    {{-- Structured data: FAQPage --}}
    @push('schema')
        @verbatim
        <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "FAQPage",
          "mainEntity": [
            {"@type":"Question","name":"Do I lose control if the AI writes the message?","acceptedAnswer":{"@type":"Answer","text":"No. Every draft shows before it sends, and you can edit inline or ask for a rewrite. Nothing goes out until you approve."}},
            {"@type":"Question","name":"Which channels can I run campaigns on?","acceptedAnswer":{"@type":"Answer","text":"WhatsApp Cloud API, WhatsApp QR, Instagram DM, Facebook Messenger, Telegram, and email. Nara picks the best channel per customer."}},
            {"@type":"Question","name":"How does Nara know who to send to?","acceptedAnswer":{"@type":"Answer","text":"Nara reads the audience description from your prompt and turns it into a query against your customer data. You see the exact count before anything goes out."}},
            {"@type":"Question","name":"Does it work in Arabic?","acceptedAnswer":{"@type":"Answer","text":"Yes. You can prompt in Arabic, English, or mix. Messages go out in the customer's language."}}
          ]
        }
        </script>
        @endverbatim
    @endpush

</x-layouts.marketing>
