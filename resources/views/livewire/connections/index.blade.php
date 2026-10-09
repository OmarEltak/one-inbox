{{--
    ══ ARCHITECTURE REFERENCE §1 ══
    READ docs/ARCHITECTURE.md §1 (Meta App Verification & Managed Onboarding)
    before modifying $metaVerified or the FB/IG connect buttons.

    metaVerified is passed as view-data from Connections\Index::render()
    reading config('services.meta.app_verified'). It is NOT a Livewire
    component property. Do NOT rewrite it as one. Do NOT default it to
    true "to restore the OAuth button" - Meta rejects unverified apps at
    the OAuth callback, so direct OAuth is silently broken for real
    customers.

    When Meta approves us: set META_APP_VERIFIED=true in prod .env and
    run php artisan config:cache. OAuth buttons return automatically.
--}}
<div class="p-4 md:p-6 space-y-6 min-w-0">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">{{ __('Connections') }}</h1>
            <p class="mt-1 text-sm text-zinc-500">{{ __('Connect your social media accounts to start receiving messages.') }}</p>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 rounded-xl border border-green-300 bg-green-50 p-4">
            <p class="text-sm font-medium text-green-700">{{ session('success') }}</p>
        </div>
    @endif
    {{-- Syncing toast: fixed bottom-right, dismissible with X.
         No localStorage — session('syncing') is a one-shot flash so it only
         appears for the one render after an OAuth success. Dismissing in-page
         just hides it for the current pageview. --}}
    @if(session('syncing'))
        <div
            x-data="{ show: true }"
            x-show="show"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            class="fixed bottom-5 end-5 z-50 max-w-sm rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-lg flex items-start gap-3"
        >
            <svg class="w-4 h-4 text-blue-600 animate-spin flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="text-sm text-blue-700 flex-1">{{ __('Syncing conversations in the background — this may take a minute. Check your inbox shortly.') }}</p>
            <button type="button" @click="show = false" aria-label="{{ __('Dismiss') }}"
                class="text-blue-400 hover:text-blue-700 transition-colors flex-shrink-0 -mt-0.5 -me-0.5 p-1 rounded cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
            <p class="text-sm font-medium text-red-700">{{ session('error') }}</p>
        </div>
    @endif

    {{-- AI Setup Prompt — shown once after first ever page is connected --}}
    @if(session('show_ai_setup_prompt'))
    <div x-data="{ show: true }" x-show="show" x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
         class="mb-4 rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-emerald-50 p-5 shadow-sm">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 size-10 rounded-xl bg-emerald-600 flex items-center justify-center shadow-sm">
                <svg class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-emerald-900">{{ __("You're connected! Now activate your AI.") }}</p>
                <p class="mt-1 text-sm text-emerald-700 leading-relaxed">
                    {{ __('Set up your AI Sales Assistant with your business info so it can start responding to customers automatically — 24/7, in your voice.') }}
                </p>
                <div class="mt-3 flex items-center gap-3">
                    <a href="{{ route('settings.ai') }}" wire:navigate
                       class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-sm font-semibold text-white transition-colors shadow-sm">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                        </svg>
                        {{ __('Set up AI') }}
                    </a>
                    <button type="button" @click="show = false"
                            class="text-sm text-emerald-500 hover:text-emerald-700 font-medium transition-colors">
                        {{ __('Do it later') }}
                    </button>
                </div>
            </div>
            <button type="button" @click="show = false" class="flex-shrink-0 text-emerald-400 hover:text-emerald-600 transition-colors">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
    @endif

    {{--
        Concierge hero card (Facebook / Instagram).

        $usesConciergeFlow is passed in from Connections\Index::render() and is
        config-driven per CLAUDE.md pin #1 — do NOT default it to false here to
        "restore the OAuth button" for regular customers. Direct OAuth is
        silently broken until Meta App Review lands Advanced Access on every
        required permission. Super-admins bypass this flag inside the computed
        property so they can still smoke-test OAuth end-to-end.
    --}}
    @if($usesConciergeFlow)
        {{--
            Solid emerald surface — white-on-emerald reads cleanly.
            Mobile-first sizing: at 320px the text column has ~220px after padding+icon+gap,
            enough for the heading to break naturally. Buttons stack vertically below 640px
            so each hits its native full-width target instead of wrapping mid-word.
        --}}
        <div class="mb-2 rounded-2xl bg-emerald-600 p-4 md:p-5 shadow-md"
             x-data="{ showDetails: false }">
            <div class="flex items-start gap-3 md:gap-4">
                <div class="flex-shrink-0 size-8 md:size-11 rounded-xl bg-white/15 ring-1 ring-white/25 flex items-center justify-center">
                    <svg class="size-4 md:size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[15px] md:text-base font-semibold text-white leading-snug">
                        {{ __('We connect Facebook and Instagram for you, usually within :n min.', ['n' => $conciergeMedianMinutes ?? 10]) }}
                    </p>
                    <p class="mt-1.5 text-sm text-white/90 leading-relaxed">
                        {{ __('Our team personally verifies every page before it goes live. It\'s how we keep the platform clean for early customers, free with any plan.') }}
                        {{ __('Send us your page details and we\'ll take it from there.') }}
                    </p>
                    <div class="mt-4 flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-2">
                        <button type="button" wire:click="openRequestForm('facebook')"
                                class="inline-flex w-full sm:w-auto items-center justify-center sm:justify-start gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-emerald-700 shadow-sm hover:bg-white/95 transition">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                            {{ __('Request Facebook connection') }}
                        </button>
                        <button type="button" wire:click="openRequestForm('instagram')"
                                class="inline-flex w-full sm:w-auto items-center justify-center sm:justify-start gap-1.5 rounded-lg bg-white/15 ring-1 ring-white/30 px-3 py-2 text-sm font-semibold text-white hover:bg-white/25 transition">
                            {{ __('Or request Instagram') }}
                        </button>
                        <button type="button" @click="showDetails = ! showDetails"
                                class="mt-1 sm:mt-0 sm:ml-1 text-xs font-semibold text-white/90 hover:text-white underline underline-offset-2 cursor-pointer text-left">
                            <span x-show="!showDetails">{{ __('How long does it take?') }}</span>
                            <span x-show="showDetails" x-cloak>{{ __('Hide details') }}</span>
                        </button>
                    </div>
                    <div x-show="showDetails" x-cloak x-transition
                         class="mt-3 rounded-lg bg-white/15 ring-1 ring-white/30 p-3 text-xs text-white leading-relaxed space-y-2">
                        @if($conciergeMedianMinutes !== null)
                            <p>
                                <strong class="text-white">{{ __('Median turnaround so far:') }}</strong>
                                {{ trans_choice('{1} :n minute|[2,*] :n minutes', $conciergeMedianMinutes, ['n' => $conciergeMedianMinutes]) }}.
                                {{ __('Business hours are 9am to 9pm Cairo; outside that window we finish the next morning.') }}
                            </p>
                        @else
                            <p>{{ __('Usually under 10 minutes during business hours (9am to 9pm Cairo). Outside that window we finish the next morning, we\'ll email you the moment your page is live.') }}</p>
                        @endif
                        <p>
                            {{ __('Add') }}
                            <a href="https://www.facebook.com/omarEltak88/" target="_blank"
                               class="underline font-semibold text-white hover:text-white/90">{{ __('our Facebook account') }}</a>
                            {{ __('as an admin on your page (Basic control is enough), then submit the form. That\'s all we need to finish the handoff on our side.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Available Platforms --}}
    {{-- min-w-0 on the grid parent + explicit grid-cols-1 on mobile prevents
         aio-card children from being sized by min-content and overflowing the
         viewport horizontally (the "page shifted right" bug on 320-375px). --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 mb-8 min-w-0">

        {{-- Facebook --}}
        <div class="aio-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-blue-500 flex items-center justify-center text-white font-bold text-lg">f</div>
                <div>
                    <h3 class="font-semibold text-white/80">Facebook</h3>
                    <p class="text-xs text-white/40">{{ __('Messenger') }}</p>
                </div>
            </div>

            @php
                $facebookAccounts = $this->connectedAccounts->where('platform', 'facebook');
                $facebookPages    = $this->pages->where('platform', 'facebook');
                $fbRejected       = $this->rejectedByPlatform['facebook'] ?? null;
            @endphp

            {{-- Connected pages (works for both direct-OAuth + admin-handoff pages) --}}
            @foreach($facebookPages as $fbPage)
                @php
                    // metadata.subscription_error ships in two shapes depending on who wrote it:
                    //   - Legacy (FacebookPlatform / Livewire\Connections\Index): plain string code
                    //     like 'twofa_required', 'subscribe_failed', 'refresh_failed'
                    //   - New (PageHealthCheckCommand): array { code, message, source, first_seen_at, last_seen_at }
                    // Normalize to the array shape so the banner can render either safely.
                    $raw = $fbPage->metadata['subscription_error'] ?? null;
                    $fbSubErr = null;
                    if (is_array($raw)) {
                        $fbSubErr = $raw + ['code' => 'unknown', 'message' => __('Reconnection needed.'), 'first_seen_at' => now()->toIso8601String()];
                    } elseif (is_string($raw) && $raw !== '') {
                        $legacy = [
                            'twofa_required'  => __('Facebook requires two-factor authentication on this page to subscribe to webhooks.'),
                            'subscribe_failed'=> __('Meta refused to subscribe this page to our webhooks. Reconnect to try again.'),
                            'refresh_failed'  => __('Could not refresh this page\'s token on Meta. Reconnect to restore access.'),
                        ];
                        $fbSubErr = ['code' => $raw, 'message' => $legacy[$raw] ?? $raw, 'first_seen_at' => now()->toIso8601String()];
                    }
                @endphp
                <div class="flex items-center justify-between py-2 border-t border-white/15">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-xs text-white/80 truncate">{{ $fbPage->name }}</span>
                        @if($fbSubErr)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-amber-100 text-amber-900 ring-1 ring-amber-300 font-medium">{{ __('Needs reconnect') }}</span>
                        @else
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-green-500/20 text-zinc-900 font-medium">{{ __('Active') }}</span>
                        @endif
                    </div>
                    <flux:button
                        wire:click="disconnectPage({{ $fbPage->id }})"
                        wire:confirm="{{ __('Disconnect') }} '{{ addslashes($fbPage->name) }}'? {{ __('Messenger messages will stop coming in for this page.') }}"
                        wire:loading.attr="disabled"
                        size="xs"
                        variant="ghost"
                        class="!text-red-500 hover:!text-red-700 flex-shrink-0 ml-2 !border !border-red-400 !bg-red-50 hover:!bg-red-100 cursor-pointer"
                    >
                        {{ __('Disconnect') }}
                    </flux:button>
                </div>
                @if($fbSubErr)
                    <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-xs mb-2">
                        <p class="text-amber-900 font-medium">{{ __('Messages from this page have stopped arriving.') }}</p>
                        <p class="text-amber-900/90 mt-1">{{ __('Meta removed this page from your authorized list, usually after you re-ran OAuth and unchecked it in the Pages picker. Reconnect to restore webhook delivery.') }}</p>
                        <p class="text-amber-800/80 mt-1 text-[10px]">{{ __('First detected :when · Meta: :msg', ['when' => \Carbon\Carbon::parse($fbSubErr['first_seen_at'])->diffForHumans(), 'msg' => \Illuminate\Support\Str::limit($fbSubErr['message'], 140)]) }}</p>
                        <div class="mt-2">
                            <flux:button as="a" href="{{ route('connections.facebook.redirect') }}" size="xs" variant="primary" class="!bg-amber-600 hover:!bg-amber-700">
                                {{ __('Reconnect :name', ['name' => $fbPage->name]) }}
                            </flux:button>
                        </div>
                    </div>
                @endif
            @endforeach

            {{-- Persistent rejection banner (only dismissible by explicit click) --}}
            @if($fbRejected)
                <div class="mt-3 rounded-lg bg-red-500/10 border border-red-500/30 p-3 text-xs">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-zinc-900 font-medium">{{ __('Your Facebook connection request was rejected') }}</p>
                            <p class="text-zinc-800 mt-1 whitespace-pre-wrap break-words">{{ $fbRejected->admin_notes }}</p>
                            <p class="text-zinc-600 mt-1 text-[10px]">{{ __('Rejected') }} {{ optional($fbRejected->completed_at)->diffForHumans() }}</p>
                        </div>
                        <button
                            type="button"
                            wire:click="dismissRejection({{ $fbRejected->id }})"
                            class="flex-shrink-0 text-[10px] uppercase tracking-wide text-red-300/70 hover:text-red-100 border border-red-500/40 rounded px-2 py-1 cursor-pointer"
                        >
                            {{ __('Dismiss') }}
                        </button>
                    </div>
                </div>
            @endif

            <div class="{{ $facebookPages->isNotEmpty() || $fbRejected ? 'mt-3' : '' }} space-y-2">
                @if(empty(config('services.meta.app_id')))
                    <p class="text-xs text-white/40">{{ __('Requires META_APP_ID and META_APP_SECRET in .env') }}</p>
                @elseif($metaVerified)
                    {{-- Primary: direct OAuth. --}}
                    <flux:button as="a" href="{{ route('connections.facebook.redirect') }}" variant="primary" size="sm" class="w-full">
                        {{ $facebookAccounts->isNotEmpty() ? __('Add Another Account') : __('Connect with Facebook') }}
                    </flux:button>
                    {{-- Secondary: concierge escape hatch. Even verified apps have corners where
                         real customers can't OAuth (2FA-required Business Portfolios, missing
                         Page roles, dead pending invites). Keep this visible 2026-10-09 after a
                         user ask — "add the ability to our customers to request a connection back
                         just under add a connection for fb or ig". --}}
                    @if(isset($this->openOnboardingByPlatform['facebook']))
                        @php $fbReq = $this->openOnboardingByPlatform['facebook']; @endphp
                        <div class="rounded-lg bg-blue-50 border border-blue-200 p-2.5 text-xs">
                            <p class="text-blue-700 font-semibold capitalize">{{ str_replace('_', ' ', $fbReq->status) }}</p>
                            <p class="text-blue-600 mt-0.5">{{ __('Requested :time · we\'ll email you when ready.', ['time' => $fbReq->created_at->diffForHumans()]) }}</p>
                        </div>
                    @else
                        <button type="button" wire:click="openRequestForm('facebook')" class="w-full text-xs text-zinc-700 hover:text-emerald-700 underline underline-offset-2 cursor-pointer py-1 transition-colors">
                            {{ __('Having trouble? Ask us to connect it for you') }}
                        </button>
                    @endif
                @elseif(isset($this->openOnboardingByPlatform['facebook']))
                    @php $fbReq = $this->openOnboardingByPlatform['facebook']; @endphp
                    <div class="rounded-lg bg-blue-50 border border-blue-200 p-3 text-xs">
                        <p class="text-blue-700 font-semibold capitalize">
                            {{ str_replace('_', ' ', $fbReq->status) }}
                        </p>
                        <p class="text-blue-600 mt-0.5">{{ __('Requested :time · we\'ll email you when ready.', ['time' => $fbReq->created_at->diffForHumans()]) }}</p>
                    </div>
                    {{-- Meta OAuth kept reachable so Meta App Review can walk the
                         real Facebook Login flow (Business Portfolio handshake,
                         Pages picker) even while a concierge request is open. --}}
                    <flux:button as="a" href="{{ route('connections.facebook.redirect') }}" variant="outline" size="sm" class="w-full">
                        {{ __('Connect with Meta API') }}
                    </flux:button>
                @else
                    <flux:button wire:click="openRequestForm('facebook')" variant="primary" size="sm" class="w-full">
                        {{ $facebookPages->isNotEmpty() ? __('Add another page') : __('Request connection') }}
                    </flux:button>
                    {{-- Official Meta OAuth — secondary path for Meta App Review.
                         Concierge stays primary (pin #1); this gives reviewers a
                         self-serve Facebook Login entry point (the "connect your
                         personal account linked to the Business Portfolio" flow
                         the verification team asked us to expose) without
                         removing the managed onboarding path. --}}
                    <flux:button as="a" href="{{ route('connections.facebook.redirect') }}" variant="outline" size="sm" class="w-full">
                        {{ __('Connect with Meta API') }}
                    </flux:button>
                @endif
            </div>
        </div>

        {{-- Instagram --}}
        <div class="aio-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-pink-500 flex items-center justify-center text-white font-bold text-sm">IG</div>
                <div>
                    <h3 class="font-semibold text-white/80">Instagram</h3>
                    <p class="text-xs text-white/40">{{ __('Direct Messages') }}</p>
                </div>
            </div>

            @php
                $instagramAccounts = $this->connectedAccounts->where('platform', 'instagram');
                $instagramPages    = $this->pages->where('platform', 'instagram');
                $igRejected        = $this->rejectedByPlatform['instagram'] ?? null;
            @endphp

            @foreach($instagramPages as $igPage)
                @php
                    // See the Facebook block above for the two shapes of this field.
                    $raw = $igPage->metadata['subscription_error'] ?? null;
                    $igSubErr = null;
                    if (is_array($raw)) {
                        $igSubErr = $raw + ['code' => 'unknown', 'message' => __('Reconnection needed.'), 'first_seen_at' => now()->toIso8601String()];
                    } elseif (is_string($raw) && $raw !== '') {
                        $legacy = [
                            'twofa_required'  => __('Facebook requires two-factor authentication on this account to subscribe to webhooks.'),
                            'subscribe_failed'=> __('Meta refused to subscribe this account to our webhooks. Reconnect to try again.'),
                            'refresh_failed'  => __('Could not refresh this account\'s token on Meta. Reconnect to restore access.'),
                        ];
                        $igSubErr = ['code' => $raw, 'message' => $legacy[$raw] ?? $raw, 'first_seen_at' => now()->toIso8601String()];
                    }
                @endphp
                <div class="flex items-center justify-between py-2 border-t border-white/15">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-xs text-white/80 truncate">
                            {{ $igPage->name }}{{ isset($igPage->metadata['username']) ? ' (@' . $igPage->metadata['username'] . ')' : '' }}
                        </span>
                        @if($igSubErr)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-amber-100 text-amber-900 ring-1 ring-amber-300 font-medium">{{ __('Needs reconnect') }}</span>
                        @else
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-green-500/20 text-zinc-900 font-medium">{{ __('Active') }}</span>
                        @endif
                    </div>
                    <flux:button
                        wire:click="disconnectPage({{ $igPage->id }})"
                        wire:confirm="{{ __('Disconnect') }} '{{ addslashes($igPage->name) }}'? {{ __('Instagram DMs will stop coming in for this page.') }}"
                        wire:loading.attr="disabled"
                        size="xs"
                        variant="ghost"
                        class="!text-red-500 hover:!text-red-700 flex-shrink-0 ml-2 !border !border-red-400 !bg-red-50 hover:!bg-red-100 cursor-pointer"
                    >
                        {{ __('Disconnect') }}
                    </flux:button>
                </div>
                @if($igSubErr)
                    <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-xs mb-2">
                        <p class="text-amber-900 font-medium">{{ __('DMs from this account have stopped arriving.') }}</p>
                        <p class="text-amber-900/90 mt-1">{{ __('Meta removed this Instagram account from your authorized list. Reconnect to restore DM delivery.') }}</p>
                        <p class="text-amber-800/80 mt-1 text-[10px]">{{ __('First detected :when · Meta: :msg', ['when' => \Carbon\Carbon::parse($igSubErr['first_seen_at'])->diffForHumans(), 'msg' => \Illuminate\Support\Str::limit($igSubErr['message'], 140)]) }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <flux:button as="a" href="{{ route('connections.instagram.redirect') }}" size="xs" variant="primary" class="!bg-amber-600 hover:!bg-amber-700">
                                {{ __('Reconnect (Direct IG Login)') }}
                            </flux:button>
                            <flux:button as="a" href="{{ route('connections.instagram-via-facebook.redirect') }}" size="xs" variant="outline">
                                {{ __('Reconnect via Meta') }}
                            </flux:button>
                        </div>
                    </div>
                @endif
            @endforeach

            @if($igRejected)
                <div class="mt-3 rounded-lg bg-red-500/10 border border-red-500/30 p-3 text-xs">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-zinc-900 font-medium">{{ __('Your Instagram connection request was rejected') }}</p>
                            <p class="text-zinc-800 mt-1 whitespace-pre-wrap break-words">{{ $igRejected->admin_notes }}</p>
                            <p class="text-zinc-600 mt-1 text-[10px]">{{ __('Rejected') }} {{ optional($igRejected->completed_at)->diffForHumans() }}</p>
                        </div>
                        <button
                            type="button"
                            wire:click="dismissRejection({{ $igRejected->id }})"
                            class="flex-shrink-0 text-[10px] uppercase tracking-wide text-red-300/70 hover:text-red-100 border border-red-500/40 rounded px-2 py-1 cursor-pointer"
                        >
                            {{ __('Dismiss') }}
                        </button>
                    </div>
                </div>
            @endif

            <div class="{{ $instagramPages->isNotEmpty() || $igRejected ? 'mt-3' : '' }} space-y-2">
                @if(empty(config('services.meta.app_id')))
                    <p class="text-xs text-white/40">{{ __('Requires META_APP_ID and META_APP_SECRET in .env') }}</p>
                @elseif($metaVerified)
                    {{-- Direct IG Login MUST stay while the main Meta app lacks Advanced Access.
                         "Via Meta" (FB Login, main app) on Standard Access only gets webhooks for
                         DMs from people with an app role — real customer DMs never arrive. Direct
                         IG Login (Instagram sub-app, /api/webhooks/meta-ig) does receive them.
                         Removing it on 2026-09-27 (8c46719) is why new IG connections went silent.
                         Via Meta stays for managed onboarding of customer pages. --}}
                    <flux:button as="a" href="{{ route('connections.instagram.redirect') }}" variant="primary" size="sm" class="w-full">
                        {{ $instagramAccounts->isNotEmpty() ? __('Add Direct (IG Login)') : __('Connect Direct (IG Login)') }}
                    </flux:button>
                    <flux:button as="a" href="{{ route('connections.instagram-via-facebook.redirect') }}" variant="outline" size="sm" class="w-full">
                        {{ $instagramAccounts->isNotEmpty() ? __('Add via Meta') : __('Connect via Meta') }}
                    </flux:button>
                    {{-- Concierge escape hatch for the "Via Meta" (FB Login) path only.
                         Direct IG Login above handles itself without ever needing concierge.
                         Shown 2026-10-09 after a user ask — some customers hit 2FA-required
                         Business Portfolios or missing-Page-role dead ends on the Via Meta
                         OAuth; this link lets them ask us to connect it for them. --}}
                    @if(isset($this->openOnboardingByPlatform['instagram']))
                        @php $igReq = $this->openOnboardingByPlatform['instagram']; @endphp
                        <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-2.5 text-xs">
                            <p class="text-emerald-700 font-semibold capitalize">{{ str_replace('_', ' ', $igReq->status) }}</p>
                            <p class="text-emerald-600 mt-0.5">{{ __('Requested :time · we\'ll email you when ready.', ['time' => $igReq->created_at->diffForHumans()]) }}</p>
                        </div>
                    @else
                        <button type="button" wire:click="openRequestForm('instagram')" class="w-full text-xs text-zinc-700 hover:text-emerald-700 underline underline-offset-2 cursor-pointer py-1 transition-colors">
                            {{ __('Having trouble with "Via Meta"? Ask us to connect it for you') }}
                        </button>
                    @endif
                    @unless(config('services.meta.app_verified'))
                        <p class="text-xs text-zinc-700">{{ __('Use Direct (IG Login) to receive DMs. "Via Meta" only receives DMs from app testers until Meta approves the app.') }}</p>
                    @endunless
                @elseif(isset($this->openOnboardingByPlatform['instagram']))
                    @php $igReq = $this->openOnboardingByPlatform['instagram']; @endphp
                    <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-3 text-xs">
                        <p class="text-emerald-700 font-semibold capitalize">
                            {{ str_replace('_', ' ', $igReq->status) }}
                        </p>
                        <p class="text-emerald-600 mt-0.5">{{ __('Requested :time · we\'ll email you when ready.', ['time' => $igReq->created_at->diffForHumans()]) }}</p>
                    </div>
                    {{-- Keep Meta API path visible even when a concierge request is open,
                         so Meta App Review reviewers can always reach the OAuth flow. --}}
                    <flux:button as="a" href="{{ route('connections.instagram-via-facebook.redirect') }}" variant="outline" size="sm" class="w-full">
                        {{ __('Connect with Meta API') }}
                    </flux:button>
                @else
                    <flux:button wire:click="openRequestForm('instagram')" variant="primary" size="sm" class="w-full">
                        {{ $instagramPages->isNotEmpty() ? __('Add another page') : __('Request connection') }}
                    </flux:button>
                    {{-- Official Meta OAuth — secondary path for Meta App Review.
                         Concierge stays primary (pin #1); this gives reviewers a
                         self-serve OAuth entry point without removing onboarding. --}}
                    <flux:button as="a" href="{{ route('connections.instagram-via-facebook.redirect') }}" variant="outline" size="sm" class="w-full">
                        {{ __('Connect with Meta API') }}
                    </flux:button>
                    {{-- Direct IG Login (Instagram sub-app, /api/webhooks/meta-ig).
                         Also exposed to reviewers so they can walk the Direct IG
                         Login flow — the only path that currently receives DMs
                         from real customers while the main app is on Standard
                         Access. See pin #1 and ARCHITECTURE §1. --}}
                    <flux:button as="a" href="{{ route('connections.instagram.redirect') }}" variant="outline" size="sm" class="w-full">
                        {{ $instagramAccounts->isNotEmpty() ? __('Add Direct (IG Login)') : __('Connect Direct (IG Login)') }}
                    </flux:button>
                @endif
            </div>
        </div>

        {{-- WhatsApp --}}
        <div class="aio-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-green-500 flex items-center justify-center text-white font-bold text-sm">WA</div>
                <div>
                    <h3 class="font-semibold text-white/80">WhatsApp</h3>
                    <p class="text-xs text-white/40">{{ __('Business API & QR Connect') }}</p>
                </div>
            </div>

            @php $whatsappAccounts = $this->connectedAccounts->where('platform', 'whatsapp'); @endphp
            @foreach($whatsappAccounts as $account)
                @php
                    $instanceName = $account->metadata['gateway_instance'] ?? null;
                    $isGateway    = ! empty($account->metadata['gateway_mode']);
                    // Use helper that also grants a 90s "just paired" grace window,
                    // covering the Wuzapi jid-propagation lag right after PairSuccess.
                    $isOnline     = $isGateway && $this->isGatewayAccountActive($account);
                @endphp
                <div class="flex items-center justify-between py-2 border-t border-white/15">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-xs text-white/80 truncate">{{ $account->name }}</span>
                        @if($isGateway)
                            @if($isOnline)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-green-500/20 text-zinc-900 font-medium">{{ __('Active') }}</span>
                            @else
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-yellow-500/20 text-yellow-400">{{ __('Disconnected') }}</span>
                            @endif
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-blue-500/20 text-blue-400">QR</span>
                        @else
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-green-500/20 text-zinc-900 font-medium">{{ __('Active') }}</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0 ml-2">
                        @if($isGateway && ! $isOnline)
                            <flux:button
                                wire:click="reconnectGateway({{ $account->id }})"
                                wire:loading.attr="disabled"
                                size="xs"
                                variant="ghost"
                                class="text-yellow-400 hover:text-yellow-300"
                            >
                                {{ __('Reconnect') }}
                            </flux:button>
                        @endif
                        <flux:button
                            wire:click="disconnect({{ $account->id }})"
                            wire:confirm="{{ __('Disconnect') }} '{{ addslashes($account->name) }}'? {{ __('Messages from this number will stop coming in.') }}"
                            wire:loading.attr="disabled"
                            size="xs"
                            variant="ghost"
                            class="!text-red-500 hover:!text-red-700 !border !border-red-400 !bg-red-50 hover:!bg-red-100 cursor-pointer"
                        >
                            {{ __('Disconnect') }}
                        </flux:button>
                    </div>
                </div>
            @endforeach

            <div class="{{ $whatsappAccounts->isNotEmpty() ? 'mt-3' : '' }} space-y-2">
                @if(config('services.wuzapi.qr_enabled'))
                    {{-- QR pairing via Wuzapi gateway — primary user-facing WhatsApp path. --}}
                    <flux:button
                        x-on:click="$dispatch('open-whatsapp-qr')"
                        variant="primary"
                        icon="qr-code"
                        class="w-full !bg-green-600 hover:!bg-green-700"
                    >
                        {{ __('Connect via QR (Beta)') }}
                    </flux:button>
                    <p class="text-[11px] text-zinc-600 text-center leading-snug">
                        {{ __('Scan with WhatsApp on your phone. No Meta setup required.') }}
                    </p>
                @endif

                @php $waEsConfigId = (string) config('services.meta.whatsapp_embedded_signup_config_id'); @endphp
                @if($waEsConfigId !== '' && ! empty(config('services.meta.app_id')))
                    {{-- Meta Embedded Signup — Meta's own iframe where the user
                         picks their business + phone number on Meta's side.
                         Set META_WHATSAPP_EMBEDDED_SIGNUP_CONFIG_ID in .env
                         after creating a configuration in developers.facebook.com
                         → App → WhatsApp → Embedded Signup → Configuration. --}}
                    <div x-data="waEmbeddedSignup({
                            appId: @js(config('services.meta.app_id')),
                            configId: @js($waEsConfigId),
                            graphVersion: @js(config('services.meta.graph_api_version', 'v21.0')),
                            callbackUrl: @js(route('connections.whatsapp.embedded-signup')),
                            csrf: @js(csrf_token()),
                        })" x-init="init()" class="space-y-1">
                        <button type="button"
                                x-on:click="launch()"
                                :disabled="loading"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition
                                       {{ config('services.wuzapi.qr_enabled')
                                            ? 'border border-zinc-300 bg-white text-zinc-800 hover:bg-zinc-50'
                                            : 'bg-emerald-600 text-white hover:bg-emerald-700' }}
                                       disabled:opacity-60 disabled:cursor-wait">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4" fill="currentColor" aria-hidden="true">
                                <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 2.1.55 4.14 1.6 5.95L2 22l4.26-1.69a9.9 9.9 0 0 0 5.78 1.86h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.84 9.84 0 0 0 12.04 2m0 1.82c2.17 0 4.21.84 5.74 2.38a8.1 8.1 0 0 1 2.38 5.74c0 4.47-3.64 8.1-8.11 8.1-1.6 0-3.16-.44-4.52-1.28l-.32-.19-2.69 1.07.86-2.63-.21-.34a8.07 8.07 0 0 1-1.24-4.3c0-4.47 3.64-8.1 8.1-8.1"/>
                            </svg>
                            <span x-text="loading ? @js(__('Connecting WhatsApp...')) : @js(__('Connect with Meta API'))"></span>
                        </button>
                        <template x-if="error">
                            <p class="text-[11px] text-red-700 bg-red-50 border border-red-200 rounded px-2 py-1" x-text="error"></p>
                        </template>
                        <p class="text-[11px] text-zinc-600 text-center leading-snug">
                            {{ __('Meta opens a popup where you select your WhatsApp Business Account and phone number.') }}
                        </p>
                    </div>
                @else
                    {{-- Fallback: step-by-step System User token modal. Used
                         when META_WHATSAPP_EMBEDDED_SIGNUP_CONFIG_ID isn't set
                         (local dev, or before the Embedded Signup config is
                         created in developers.facebook.com). --}}
                    <flux:modal.trigger name="whatsapp-connect">
                        <flux:button
                            variant="{{ config('services.wuzapi.qr_enabled') ? 'outline' : 'primary' }}"
                            size="sm"
                            class="w-full"
                        >
                            {{ __('Connect with Meta API') }}
                        </flux:button>
                    </flux:modal.trigger>
                @endif
            </div>
        </div>

        {{-- Telegram --}}
        <div class="aio-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-cyan-500 flex items-center justify-center text-white font-bold text-sm">TG</div>
                <div>
                    <h3 class="font-semibold text-white/80">Telegram</h3>
                    <p class="text-xs text-white/40">{{ __('Bot API') }}</p>
                </div>
            </div>

            @php $telegramAccounts = $this->connectedAccounts->where('platform', 'telegram'); @endphp
            @foreach($telegramAccounts as $account)
                <div class="flex items-center justify-between py-2 border-t border-white/15">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-xs text-white/80 truncate">{{ $account->name }}</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-green-500/20 text-zinc-900 font-medium">{{ __('Active') }}</span>
                    </div>
                    <flux:button
                        wire:click="disconnect({{ $account->id }})"
                        wire:confirm="{{ __('Disconnect') }} {{ __('Telegram bot') }} '{{ addslashes($account->name) }}'?"
                        wire:loading.attr="disabled"
                        size="xs"
                        variant="ghost"
                        class="!text-red-500 hover:!text-red-700 flex-shrink-0 ml-2 !border !border-red-400 !bg-red-50 hover:!bg-red-100 cursor-pointer"
                    >
                        {{ __('Disconnect') }}
                    </flux:button>
                </div>
            @endforeach

            <div class="{{ $telegramAccounts->isNotEmpty() ? 'mt-3' : '' }}">
                <flux:modal.trigger name="telegram-connect">
                    <flux:button variant="primary" size="sm" class="w-full">
                        {{ $telegramAccounts->isNotEmpty() ? __('Add Another Bot') : __('Connect Telegram Bot') }}
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        {{-- Web Chat Widget --}}
        <div class="aio-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-500 flex items-center justify-center text-white font-bold text-sm">WC</div>
                <div>
                    <h3 class="font-semibold text-white/80">Web Chat</h3>
                    <p class="text-xs text-white/40">{{ __('Embed on your site') }}</p>
                </div>
            </div>

            @php $webchatAccounts = $this->connectedAccounts->where('platform', 'webchat'); @endphp
            @foreach($webchatAccounts as $account)
                @php $page = $account->pages->where('platform', 'webchat')->first(); @endphp
                <div class="flex items-center justify-between py-2 border-t border-white/15">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-xs text-white/80 truncate">{{ $account->name }}</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-green-500/20 text-zinc-900 font-medium">{{ __('Active') }}</span>
                    </div>
                    <div class="flex gap-1 flex-shrink-0 ml-2">
                        @if($page)
                            <flux:button wire:click="showWebChatSnippetFor({{ $page->id }})" size="xs" variant="ghost">{{ __('Snippet') }}</flux:button>
                        @endif
                        <flux:button
                            wire:click="disconnect({{ $account->id }})"
                            wire:confirm="{{ __('Remove Web Chat widget') }} '{{ addslashes($account->name) }}'? {{ __('Your visitors will see the bubble disappear.') }}"
                            wire:loading.attr="disabled"
                            size="xs"
                            variant="ghost"
                            class="!text-red-500 hover:!text-red-700 !border !border-red-400 !bg-red-50 hover:!bg-red-100 cursor-pointer"
                        >
                            {{ __('Disconnect') }}
                        </flux:button>
                    </div>
                </div>
            @endforeach

            <div class="{{ $webchatAccounts->isNotEmpty() ? 'mt-3' : '' }} space-y-2">
                <flux:input
                    wire:model="webChatSiteName"
                    size="sm"
                    placeholder="{{ __('Site name (e.g. Acme Store)') }}"
                />
                <flux:button wire:click="connectWebChat" variant="primary" size="sm" class="w-full">
                    {{ $webchatAccounts->isNotEmpty() ? __('Add Another Widget') : __('Create Web Chat Widget') }}
                </flux:button>
                <p class="text-[10px] text-white/40 leading-relaxed text-center px-2">
                    {{ __('Free, never breaks. Visitors chat from a bubble on your site; messages land here in real time.') }}
                </p>
            </div>
        </div>

        {{-- Slack --}}
        <div class="aio-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-[#4A154B] flex items-center justify-center text-white font-bold text-sm">SL</div>
                <div>
                    <h3 class="font-semibold text-white/80">Slack</h3>
                    <p class="text-xs text-white/40">{{ __('Workspace bot') }}</p>
                </div>
            </div>

            @php $slackAccounts = $this->connectedAccounts->where('platform', 'slack'); @endphp
            @foreach($slackAccounts as $account)
                <div class="flex items-center justify-between py-2 border-t border-white/15">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-xs text-white/80 truncate">{{ $account->name }}</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-green-500/20 text-zinc-900 font-medium">{{ __('Active') }}</span>
                    </div>
                    <flux:button
                        wire:click="disconnect({{ $account->id }})"
                        wire:confirm="{{ __('Disconnect') }} {{ __('Slack workspace') }} '{{ addslashes($account->name) }}'?"
                        wire:loading.attr="disabled"
                        size="xs"
                        variant="ghost"
                        class="!text-red-500 hover:!text-red-700 flex-shrink-0 ml-2 !border !border-red-400 !bg-red-50 hover:!bg-red-100 cursor-pointer"
                    >
                        {{ __('Disconnect') }}
                    </flux:button>
                </div>
            @endforeach

            <div class="{{ $slackAccounts->isNotEmpty() ? 'mt-3' : '' }}">
                <flux:modal.trigger name="slack-connect">
                    <flux:button variant="primary" size="sm" class="w-full">
                        {{ $slackAccounts->isNotEmpty() ? __('Add Another Workspace') : __('Connect Slack') }}
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        {{-- Discord --}}
        <div class="aio-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-[#5865F2] flex items-center justify-center text-white font-bold text-sm">DC</div>
                <div>
                    <h3 class="font-semibold text-white/80">Discord</h3>
                    <p class="text-xs text-white/40">{{ __('Bot via /support') }}</p>
                </div>
            </div>

            @php $discordAccounts = $this->connectedAccounts->where('platform', 'discord'); @endphp
            @foreach($discordAccounts as $account)
                <div class="flex items-center justify-between py-2 border-t border-white/15">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-xs text-white/80 truncate">{{ $account->name }}</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-green-500/20 text-zinc-900 font-medium">{{ __('Active') }}</span>
                    </div>
                    <flux:button
                        wire:click="disconnect({{ $account->id }})"
                        wire:confirm="{{ __('Disconnect') }} {{ __('Discord bot') }} '{{ addslashes($account->name) }}'?"
                        wire:loading.attr="disabled"
                        size="xs"
                        variant="ghost"
                        class="!text-red-500 hover:!text-red-700 flex-shrink-0 ml-2 !border !border-red-400 !bg-red-50 hover:!bg-red-100 cursor-pointer"
                    >
                        {{ __('Disconnect') }}
                    </flux:button>
                </div>
            @endforeach

            <div class="{{ $discordAccounts->isNotEmpty() ? 'mt-3' : '' }}">
                <flux:modal.trigger name="discord-connect">
                    <flux:button variant="primary" size="sm" class="w-full">
                        {{ $discordAccounts->isNotEmpty() ? __('Add Another Bot') : __('Connect Discord') }}
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        {{-- TikTok --}}
        <div class="aio-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-black flex items-center justify-center text-white font-bold text-sm border border-white/15">TT</div>
                <div>
                    <h3 class="font-semibold text-white/80">TikTok</h3>
                    <p class="text-xs text-white/40">{{ __('Direct Messages') }}</p>
                </div>
            </div>

            @php $tiktokAccounts = $this->connectedAccounts->where('platform', 'tiktok'); @endphp
            @foreach($tiktokAccounts as $account)
                <div class="flex items-center justify-between py-2 border-t border-white/15">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-xs text-white/80 truncate">{{ $account->name }}</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-green-500/20 text-zinc-900 font-medium">{{ __('Active') }}</span>
                    </div>
                    <flux:button
                        wire:click="disconnect({{ $account->id }})"
                        wire:confirm="{{ __('Disconnect') }} {{ __('TikTok account') }} '{{ addslashes($account->name) }}'?"
                        wire:loading.attr="disabled"
                        size="xs"
                        variant="ghost"
                        class="!text-red-500 hover:!text-red-700 flex-shrink-0 ml-2 !border !border-red-400 !bg-red-50 hover:!bg-red-100 cursor-pointer"
                    >
                        {{ __('Disconnect') }}
                    </flux:button>
                </div>
            @endforeach

            <div class="{{ $tiktokAccounts->isNotEmpty() ? 'mt-3' : '' }} space-y-2">
                <div class="rounded-lg bg-zinc-100 border border-zinc-200 p-3 text-xs text-center">
                    <span class="inline-flex items-center gap-1.5 text-zinc-600 font-medium">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        {{ __('Currently unavailable') }}
                    </span>
                    <p class="text-zinc-500 mt-1">{{ __('TikTok connections are temporarily unavailable. Coming back soon.') }}</p>
                </div>
            </div>
        </div>

        {{-- Snapchat — Coming Soon --}}
        <div class="aio-card rounded-2xl p-5 opacity-60">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-yellow-400/30 flex items-center justify-center text-yellow-600 font-bold text-sm">SC</div>
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <h3 class="font-semibold text-white/50">Snapchat</h3>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-yellow-400/10 text-yellow-400/70 border border-yellow-400/20">{{ __('Coming Soon') }}</span>
                    </div>
                    <p class="text-xs text-white/25">{{ __('Business Messaging') }}</p>
                </div>
            </div>
            <p class="text-xs text-white/30 leading-relaxed">{{ __('Snapchat\'s messaging API requires partner approval. We\'re working on it — stay tuned.') }}</p>
        </div>

        {{-- Email --}}
        <div class="aio-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-orange-500 flex items-center justify-center text-white font-bold text-sm">@</div>
                <div>
                    <h3 class="font-semibold text-white/80">Email</h3>
                    <p class="text-xs text-white/40">{{ __('Gmail, Outlook, IMAP') }}</p>
                </div>
            </div>

            @php $emailAccounts = $this->connectedAccounts->where('platform', 'email'); @endphp
            @foreach($emailAccounts as $account)
                <div class="flex items-center justify-between py-2 border-t border-white/15">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-xs text-white/80 truncate">{{ $account->name }}</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-green-500/20 text-zinc-900 font-medium">{{ __('Active') }}</span>
                    </div>
                    <flux:button
                        wire:click="disconnect({{ $account->id }})"
                        wire:confirm="{{ __('Disconnect') }} {{ __('email account') }} '{{ addslashes($account->name) }}'?"
                        wire:loading.attr="disabled"
                        size="xs"
                        variant="ghost"
                        class="!text-red-500 hover:!text-red-700 flex-shrink-0 ml-2 !border !border-red-400 !bg-red-50 hover:!bg-red-100 cursor-pointer"
                    >
                        {{ __('Disconnect') }}
                    </flux:button>
                </div>
            @endforeach

            <details class="{{ $emailAccounts->isNotEmpty() ? 'mt-3' : '' }} group">
                <summary class="flex items-center justify-center gap-2 w-full px-3 py-2 rounded-xl text-sm font-semibold cursor-pointer select-none list-none
                                text-zinc-700 border border-zinc-200 hover:border-orange-500/50 hover:bg-orange-50 transition-all bg-zinc-50">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    {{ $emailAccounts->isNotEmpty() ? __('Add Another Email') : __('Connect Email') }}
                </summary>

                <div class="mt-3 space-y-4">

                    {{-- Gmail steps --}}
                    <div class="rounded-xl border border-zinc-200 overflow-hidden bg-white">
                        <div class="flex items-center gap-2 px-4 py-3 border-b border-zinc-200" style="background: rgba(234,67,53,0.06);">
                            <svg class="size-4 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor" style="color:#EA4335;">
                                <path d="M24 5.457v13.909c0 .904-.732 1.636-1.636 1.636h-3.819V11.73L12 16.64l-6.545-4.91v9.273H1.636A1.636 1.636 0 010 19.366V5.457c0-.886.716-1.542 1.601-1.542.49 0 .918.206 1.226.54L12 11.73l9.173-7.274c.308-.335.737-.541 1.226-.541.885 0 1.601.656 1.601 1.542z"/>
                            </svg>
                            <span class="text-xs font-semibold text-zinc-600">{{ __('Gmail setup — 2 steps') }}</span>
                        </div>
                        <div class="px-4 py-3 space-y-2.5">
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 size-5 rounded-full flex items-center justify-center text-[10px] font-bold text-white" style="background:#EA4335;">1</span>
                                <div>
                                    <p class="text-xs text-zinc-700 font-medium">{{ __('Enable 2-Step Verification') }}</p>
                                    <a href="https://myaccount.google.com/signinoptions/two-step-verification" target="_blank"
                                       class="inline-flex items-center gap-1 text-[11px] text-[#4285F4] hover:underline mt-0.5">
                                        myaccount.google.com → Security → 2-Step Verification
                                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 size-5 rounded-full flex items-center justify-center text-[10px] font-bold text-white" style="background:#EA4335;">2</span>
                                <div>
                                    <p class="text-xs text-zinc-700 font-medium">{{ __('Create an App Password') }}</p>
                                    <a href="https://myaccount.google.com/apppasswords" target="_blank"
                                       class="inline-flex items-center gap-1 text-[11px] text-[#4285F4] hover:underline mt-0.5">
                                        myaccount.google.com → App Passwords
                                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                    <p class="text-[11px] text-zinc-400 mt-0.5">{{ __('Select app: "Mail" → generate → copy the 16-char password') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Outlook steps --}}
                    <div class="rounded-xl border border-zinc-200 overflow-hidden bg-white">
                        <div class="flex items-center gap-2 px-4 py-3 border-b border-zinc-200" style="background: rgba(0,120,212,0.06);">
                            <svg class="size-4 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor" style="color:#0078D4;">
                                <path d="M7.88 12.04q0 .45-.11.87-.1.41-.33.74-.22.33-.58.52-.37.2-.87.2t-.85-.2q-.35-.21-.57-.55-.22-.33-.33-.75-.1-.42-.1-.86t.1-.87q.1-.43.34-.76.22-.34.59-.54.36-.2.87-.2t.86.2q.35.21.57.55.22.34.32.77.1.43.1.88zM24 12v9.38q0 .46-.33.8-.33.32-.8.32H7.13q-.46 0-.8-.33-.32-.33-.32-.8V18H1q-.41 0-.7-.3-.3-.29-.3-.7V7q0-.41.3-.7Q.58 6 1 6h6.1V2.55q0-.44.3-.75.3-.3.75-.3h12.9q.44 0 .75.3.3.3.3.75V10.85l1.24.72q.07.04.07.13z"/>
                            </svg>
                            <span class="text-xs font-semibold text-zinc-600">{{ __('Outlook / Hotmail setup') }}</span>
                        </div>
                        <div class="px-4 py-3 space-y-2.5">
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 size-5 rounded-full flex items-center justify-center text-[10px] font-bold text-white" style="background:#0078D4;">1</span>
                                <div>
                                    <p class="text-xs text-zinc-700 font-medium">{{ __('Enable IMAP access') }}</p>
                                    <a href="https://outlook.live.com/mail/0/options/mail/accounts/popImap" target="_blank"
                                       class="inline-flex items-center gap-1 text-[11px] text-[#4285F4] hover:underline mt-0.5">
                                        Outlook Settings → Mail → Sync → POP and IMAP
                                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 size-5 rounded-full flex items-center justify-center text-[10px] font-bold text-white" style="background:#0078D4;">2</span>
                                <p class="text-xs text-zinc-500 pt-0.5">{{ __('Use your regular Outlook password below. No app password needed.') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- The form --}}
                    <form method="POST" action="{{ route('connections.email.connect') }}" class="space-y-3 rounded-xl border border-zinc-200 p-4 bg-zinc-50">
                        @csrf
                        <p class="text-xs font-semibold text-zinc-600">{{ __('Enter your credentials') }}</p>
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('Email Address') }}</label>
                            <input type="email" name="email" placeholder="you@gmail.com" required
                                   class="w-full rounded-lg px-3 py-2 text-sm text-zinc-800 placeholder-zinc-400 focus:outline-none bg-white border border-zinc-200 focus:border-emerald-400" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('Password / App Password') }}</label>
                            <input type="password" name="password" placeholder="{{ __('Paste the 16-char app password here') }}" required
                                   class="w-full rounded-lg px-3 py-2 text-sm text-zinc-800 placeholder-zinc-400 focus:outline-none bg-white border border-zinc-200 focus:border-emerald-400" />
                        </div>
                        <button type="submit"
                                class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-sm font-semibold text-white transition-all aio-btn-primary">
                            {{ __('Connect Email') }}
                        </button>
                    </form>

                </div>
            </details>
        </div>

    </div>

    {{-- Connected Pages & Accounts Table --}}
    @if($this->pages->isNotEmpty())
        <div class="aio-card rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-zinc-100">
                <h3 class="font-semibold text-zinc-800">{{ __('Connected Pages & Accounts') }}</h3>
                <p class="text-xs text-zinc-500 mt-0.5">{{ __('All active pages receiving messages') }}</p>
            </div>
            <div class="divide-y divide-zinc-100">
                @foreach($this->pages as $page)
                    <div class="flex items-center gap-4 px-5 py-3">
                        <div class="size-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0 {{ match($page->platform) {
                            'facebook' => 'bg-blue-500',
                            'instagram' => 'bg-pink-500',
                            'whatsapp' => 'bg-green-500',
                            'telegram' => 'bg-cyan-500',
                            'tiktok' => 'bg-red-500',
                            'snapchat' => 'bg-yellow-400 text-yellow-900',
                            'email' => 'bg-orange-500',
                            default => 'bg-gray-500',
                        } }}">
                            {{ strtoupper(substr($page->platform, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-zinc-800 truncate">{{ $page->name }}</p>
                            <p class="text-xs text-zinc-500">{{ ucfirst($page->platform) }} {{ isset($page->metadata['category']) ? '· ' . $page->metadata['category'] : '' }}</p>
                            @if(($page->metadata['subscription_error'] ?? null) === 'twofa_required')
                                <p class="text-xs text-yellow-400 mt-0.5">
                                            ⚠ {{ __('Not receiving messages — Two-Factor Authentication required on Facebook.') }}
                                    <a href="https://www.facebook.com/settings?tab=security" target="_blank" class="underline hover:text-yellow-300">{{ __('Enable 2FA on Facebook') }}</a>,
                                    {{ __('then') }} <button wire:click="retryPageSubscription({{ $page->id }})" class="underline hover:text-yellow-300 cursor-pointer">{{ __('retry here') }}</button>.
                                </p>
                            @endif
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs {{ $page->is_active ? 'bg-green-500/20 text-zinc-900 font-medium' : 'bg-red-500/20 text-red-400' }}">
                            {{ $page->is_active ? __('Active') : __('Inactive') }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- WhatsApp Cloud API (Meta) Modal — guided onboarding.
         Contrast-guardrails pass: this modal lives on the light (cream) app shell,
         so every surface uses the safe light-theme palette (`-900` text on `-50/-100`
         tinted containers; `text-zinc-900` body on white). Do NOT re-introduce the
         old dark-theme classes (`text-white/*`, `bg-white/[0.02]`, `bg-black/30`,
         `variant="ghost"`) — they render invisible here. See skill `contrast-guardrails`. --}}
    <flux:modal name="whatsapp-connect" class="w-full max-w-2xl !bg-white dark:!bg-white">
        <div class="space-y-5" x-data="{ help: null }">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h3 class="text-lg font-semibold text-zinc-900">{{ __('Connect WhatsApp via Cloud API') }}</h3>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-900 ring-1 ring-emerald-200">Official Meta API</span>
                </div>
                <p class="text-sm text-zinc-700 mt-1">
                    Meta's official WhatsApp Business pipe. Stable, supports message templates, never drops because of WhatsApp protocol updates.
                </p>
            </div>

            {{-- Prerequisites strip --}}
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs text-blue-900 leading-relaxed">
                <strong class="text-blue-900">Before you start, make sure you have:</strong>
                <ul class="mt-1.5 ml-4 list-disc space-y-0.5">
                    <li>A Facebook account that owns (or is admin of) a Meta Business account</li>
                    <li>A phone number that is <strong class="bg-amber-100 text-amber-900 px-1 rounded">not currently active</strong> on any WhatsApp / WhatsApp Business app — Cloud API takes the number over</li>
                    <li>Permission to verify the business in Meta Business Manager (your Meta Verified status, if needed for Egyptian / KSA / UAE numbers)</li>
                </ul>
            </div>

            {{-- Step-by-step setup --}}
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 space-y-4">
                <p class="text-xs font-semibold text-zinc-700 uppercase tracking-wide">Setup (≈ 20–40 min, one-time)</p>

                {{-- Step 1 --}}
                <div class="text-xs text-zinc-700 leading-relaxed">
                    <p class="font-semibold text-zinc-900">① Create a WhatsApp Business Account in Meta Business Manager</p>
                    <ol class="mt-1 ml-4 space-y-1 list-decimal list-outside marker:text-zinc-400">
                        <li>Open <a href="https://business.facebook.com/settings/whatsapp-business-accounts" target="_blank" rel="noopener" class="text-blue-700 hover:underline">business.facebook.com/settings/whatsapp-business-accounts</a> (direct link to the WhatsApp Accounts page)</li>
                        <li>Top-right click <strong class="text-zinc-900">Add → Create a WhatsApp Account</strong> (skip if you already have one)</li>
                        <li>Pick the business that owns it; give it a display name (this is what your customers will see in WhatsApp)</li>
                    </ol>
                </div>

                {{-- Step 2 --}}
                <div class="text-xs text-zinc-700 leading-relaxed">
                    <p class="font-semibold text-zinc-900">② Add and verify your phone number</p>
                    <ol class="mt-1 ml-4 space-y-1 list-decimal list-outside marker:text-zinc-400">
                        <li>Click your WhatsApp account → <strong class="text-zinc-900">Phone numbers → Add phone number</strong></li>
                        <li>Pick a verification method (SMS or call) — Meta will read out / text you a 6-digit code</li>
                        <li>Pick a display name (e.g. "Acme Support"). Meta reviews it for ~24h; you can keep going while it's pending</li>
                    </ol>
                    <p class="mt-2 ml-4 rounded border border-amber-200 bg-amber-50 px-2 py-1.5 text-amber-900">
                        ⚠ The phone <em>cannot</em> be in use on the regular WhatsApp app simultaneously. Log it out everywhere first.
                    </p>
                </div>

                {{-- Step 3 — WABA ID --}}
                <div class="text-xs text-zinc-700 leading-relaxed">
                    <p class="font-semibold text-zinc-900">③ Copy the WhatsApp Business Account ID (WABA ID) — paste it below</p>
                    <ol class="mt-1 ml-4 space-y-1 list-decimal list-outside marker:text-zinc-400">
                        <li>Still on <a href="https://business.facebook.com/settings/whatsapp-business-accounts" target="_blank" rel="noopener" class="text-blue-700 hover:underline">the WhatsApp Accounts page</a>, click your WhatsApp Business Account</li>
                        <li>Look at the top of the panel — under the account name there's a 15- or 16-digit number labelled <strong class="text-zinc-900">"WhatsApp Business Account ID"</strong>. Click the copy icon next to it.</li>
                        <li>Alternative: open <a href="https://business.facebook.com/wa/manage" target="_blank" rel="noopener" class="text-blue-700 hover:underline">WhatsApp Manager</a> → top of any page shows the same ID</li>
                    </ol>
                    <p class="mt-1.5 ml-4">
                        <button type="button" @click="help = (help === 'waba_id' ? null : 'waba_id')" class="text-blue-700 hover:underline">
                            <span x-text="help === 'waba_id' ? '▼ Hide' : '▶ Show'"></span> what the ID looks like
                        </button>
                    </p>
                    <div x-show="help === 'waba_id'" x-cloak class="mt-2 ml-4 p-2 rounded border border-zinc-200 bg-white text-zinc-800">
                        Looks like <code class="bg-zinc-100 border border-zinc-200 text-zinc-900 px-1 rounded">110424298547381</code>. Always digits, no dashes, 15–17 chars.
                        Don't confuse it with the <em>Phone Number ID</em> (which is a different value also visible on the WABA page).
                    </div>
                </div>

                {{-- Step 4 — App + System User --}}
                <div class="text-xs text-zinc-700 leading-relaxed">
                    <p class="font-semibold text-zinc-900">④ Create / open a Meta App and link it to your WABA</p>
                    <ol class="mt-1 ml-4 space-y-1 list-decimal list-outside marker:text-zinc-400">
                        <li>Go to <a href="https://developers.facebook.com/apps" target="_blank" rel="noopener" class="text-blue-700 hover:underline">developers.facebook.com/apps</a></li>
                        <li>If you don't have one yet: <strong class="text-zinc-900">Create App → Business → Next</strong>, give it a name, link it to your business</li>
                        <li>In the app's left sidebar: <strong class="text-zinc-900">Add Products → WhatsApp → Set Up</strong></li>
                        <li>WhatsApp Setup screen → <strong class="text-zinc-900">"Select a WhatsApp Business Account"</strong> → pick the WABA from step ①</li>
                    </ol>
                </div>

                {{-- Step 5 — System User Token --}}
                <div class="text-xs text-zinc-700 leading-relaxed">
                    <p class="font-semibold text-zinc-900">⑤ Generate a permanent System User access token — paste it below</p>
                    <ol class="mt-1 ml-4 space-y-1 list-decimal list-outside marker:text-zinc-400">
                        <li>Open <a href="https://business.facebook.com/settings/system-users" target="_blank" rel="noopener" class="text-blue-700 hover:underline">business.facebook.com/settings/system-users</a></li>
                        <li>Click <strong class="text-zinc-900">Add → Create System User</strong>. Name it (e.g. "OT1-Pro API"), set role to <strong class="text-zinc-900">Admin</strong></li>
                        <li>With the system user selected, click <strong class="text-zinc-900">Add Assets → Apps</strong> → choose your WhatsApp app from step ④, toggle <strong class="text-zinc-900">"Develop app"</strong> to on, save</li>
                        <li>Click <strong class="text-zinc-900">Add Assets → WhatsApp Accounts</strong> → choose the WABA, toggle <strong class="text-zinc-900">"Manage WhatsApp account"</strong> to on, save</li>
                        <li>Now click <strong class="text-zinc-900">Generate New Token</strong></li>
                        <li>App: pick the WhatsApp app from step ④</li>
                        <li>Token expiration: <strong class="bg-amber-100 text-amber-900 px-1 rounded">"Never"</strong> (highly recommended — otherwise you'll have to re-paste a new token every 60 days)</li>
                        <li>Permissions — check both:
                            <ul class="mt-0.5 ml-4 list-disc list-outside marker:text-zinc-400">
                                <li><code class="text-[11px] bg-zinc-100 border border-zinc-200 text-zinc-900 px-1 rounded">whatsapp_business_messaging</code></li>
                                <li><code class="text-[11px] bg-zinc-100 border border-zinc-200 text-zinc-900 px-1 rounded">whatsapp_business_management</code></li>
                            </ul>
                        </li>
                        <li>Click <strong class="text-zinc-900">Generate Token</strong>, then <strong class="bg-amber-100 text-amber-900 px-1 rounded">copy it immediately</strong> — Meta will not show it again. The token starts with <code class="bg-zinc-100 border border-zinc-200 text-zinc-900 px-1 rounded">EAA</code> and is roughly 200 characters long.</li>
                    </ol>
                    <p class="mt-1.5 ml-4">
                        <button type="button" @click="help = (help === 'token' ? null : 'token')" class="text-blue-700 hover:underline">
                            <span x-text="help === 'token' ? '▼ Hide' : '▶ Show'"></span> "Generate New Token isn't there" / I see an error
                        </button>
                    </p>
                    <div x-show="help === 'token'" x-cloak class="mt-2 ml-4 p-2 rounded border border-zinc-200 bg-white text-zinc-800 space-y-1">
                        <p>The button only appears once the system user has the WhatsApp app <em>and</em> the WABA assigned to it (steps 3–4 above). If it's greyed out or missing:</p>
                        <ul class="ml-4 list-disc">
                            <li>Confirm your business is the <strong class="text-zinc-900">owner</strong> (not just admin) of both the app and the WABA</li>
                            <li>Confirm the system user role is <strong class="text-zinc-900">Admin</strong>, not Employee</li>
                            <li>If your business is in Egypt / KSA / UAE / similar, Meta Verified business status may be required — Settings → Security Center → Business Verification</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Pricing teaser --}}
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-3">
                <p class="text-xs text-amber-900 leading-relaxed">
                    <strong class="text-amber-900 font-semibold">Pricing:</strong> Meta bills your WABA directly — not us.
                    Replies sent within 24 hours of a customer's message are <strong class="text-amber-900 font-semibold">free, unlimited</strong>.
                    Marketing / utility templates outside that window cost cents per message and depend on the recipient's country.
                    Live estimates show up in the Campaigns page when you build a broadcast.
                    <a href="https://developers.facebook.com/docs/whatsapp/pricing" target="_blank" rel="noopener" class="underline hover:text-amber-950 font-medium">Meta's pricing reference →</a>
                </p>
            </div>

            <form method="POST" action="{{ route('connections.whatsapp.connect') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-zinc-900 mb-1.5">
                        WhatsApp Business Account ID (WABA ID)
                        <span class="text-zinc-600 font-normal">— from step ③</span>
                    </label>
                    {{-- Hand-rolled input per contrast-guardrails failure mode 2:
                         flux:input renders typed content as muted zinc-500 which is
                         unreadable for a long numeric ID. Zinc-900 text + zinc-400
                         placeholder keeps typed vs. hint distinct. --}}
                    <input type="text" name="waba_id" placeholder="110424298547381" required pattern="[0-9]{12,18}"
                           class="block w-full rounded-lg border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-zinc-900 placeholder:text-zinc-400 shadow-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition font-mono" />
                    <p class="mt-1 text-[11px] text-zinc-600">15- to 17-digit number. Digits only — no dashes or spaces.</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-900 mb-1.5">
                        System User Access Token
                        <span class="text-zinc-600 font-normal">— from step ⑤</span>
                    </label>
                    <textarea name="access_token" rows="3" placeholder="EAA..." required
                              class="block w-full rounded-lg border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-zinc-900 placeholder:text-zinc-400 shadow-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition font-mono break-all"></textarea>
                    <p class="mt-1 text-[11px] text-zinc-600">
                        Starts with <code class="bg-zinc-100 border border-zinc-200 text-zinc-900 px-1 rounded">EAA</code>, around 200 characters. We store this encrypted in our database; only your team can use it.
                    </p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    {{-- Cancel: plain outlined button per contrast-guardrails failure mode 3.
                         variant="ghost" is invisible on a white modal. --}}
                    <flux:modal.close>
                        <button type="button" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 transition">
                            {{ __('Cancel') }}
                        </button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="shield-check">{{ __('Connect WhatsApp') }}</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Telegram Bot Modal --}}
    <flux:modal name="telegram-connect" class="w-full max-w-lg !bg-[#f5f5f5] dark:!bg-[#f5f5f5]">
        <div class="space-y-4">
            <div>
                <h3 class="text-lg font-semibold text-zinc-800">{{ __('Connect Telegram Bot') }}</h3>
                <p class="text-sm text-zinc-500 mt-1">A Telegram bot is a free account that messages people in your name. Anyone who chats with the bot lands in your inbox.</p>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-zinc-100 p-3 text-xs text-zinc-600 space-y-1.5 leading-relaxed">
                <p class="font-semibold text-zinc-700">How to get a bot token (3 minutes)</p>
                <p>① Open Telegram on your phone or desktop. Search for <a href="https://t.me/BotFather" target="_blank" rel="noopener" class="text-emerald-600 hover:underline"><strong>@BotFather</strong></a> and start a chat.</p>
                <p>② Send the message <code class="text-emerald-600">/newbot</code></p>
                <p>③ BotFather asks for a <strong class="text-zinc-700">name</strong> — type whatever you want (e.g. "Acme Support").</p>
                <p>④ Then it asks for a <strong class="text-zinc-700">username</strong> — must end in <code class="text-emerald-600">bot</code> (e.g. <code class="text-emerald-600">acme_support_bot</code>).</p>
                <p>⑤ BotFather replies with a <strong class="text-zinc-700">token</strong> that looks like <code class="text-emerald-600">123456789:ABCdefGHIjklMNOpqrSTUvwxyz</code>. <strong>Copy the entire token</strong> and paste it below.</p>
                <p class="text-yellow-600 mt-2">⚠️ Keep this token private — it's the equivalent of a password for your bot.</p>
            </div>

            <form method="POST" action="{{ route('connections.telegram.connect') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-zinc-600 mb-1.5">Bot Token (from BotFather)</label>
                    <input type="text" name="bot_token" placeholder="123456789:ABCdef..." required
                           class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-[#3b82f6] focus:outline-none font-mono" />
                </div>

                <div class="rounded-lg border border-zinc-200 bg-zinc-100 p-3 text-xs text-zinc-600 leading-relaxed">
                    <p class="font-semibold text-zinc-700 mb-1">After you click Connect Bot:</p>
                    <p>• Anyone who searches for <code class="text-emerald-600">@your_bot_username</code> on Telegram and starts a chat → lands in your inbox.</p>
                    <p>• Share the bot link with your customers: <code class="text-emerald-600">https://t.me/your_bot_username</code></p>
                    <p>• Replies you send from the inbox arrive in their Telegram.</p>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">{{ __('Connect Bot') }}</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Slack connect modal --}}
    <flux:modal name="slack-connect" class="w-full max-w-lg !bg-[#f5f5f5] dark:!bg-[#f5f5f5]">
        <div class="space-y-4">
            <div>
                <h3 class="text-lg font-semibold text-zinc-800">{{ __('Connect Slack Workspace') }}</h3>
                <p class="text-sm text-zinc-500 mt-1">
                    Create a Slack App at <a href="https://api.slack.com/apps" target="_blank" rel="noopener" class="text-emerald-600 hover:underline">api.slack.com/apps</a>, install it to your workspace, then paste the credentials below.
                </p>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-zinc-100 p-3 text-xs text-zinc-600 space-y-1.5">
                <p class="font-semibold text-zinc-700">Setup checklist</p>
                <p>① Create app → "From scratch" → pick a workspace</p>
                <p>② OAuth &amp; Permissions → add Bot Token Scopes: <code class="text-emerald-600">chat:write</code>, <code class="text-emerald-600">channels:history</code>, <code class="text-emerald-600">groups:history</code>, <code class="text-emerald-600">im:history</code>, <code class="text-emerald-600">users:read</code></p>
                <p>③ Install to workspace → copy the <strong class="text-zinc-700">Bot User OAuth Token</strong> (starts with <code class="text-emerald-600">xoxb-</code>)</p>
                <p>④ Basic Information → copy the <strong class="text-zinc-700">Signing Secret</strong></p>
                <p>⑤ Event Subscriptions → enable, set Request URL to <code class="text-emerald-600">{{ url('/api/webhooks/slack') }}</code>, subscribe to bot events: <code class="text-emerald-600">message.channels</code>, <code class="text-emerald-600">message.groups</code>, <code class="text-emerald-600">message.im</code></p>
                <p>⑥ Invite the bot to any channels you want messages to flow from</p>
            </div>

            <form method="POST" action="{{ route('connections.slack.connect') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-zinc-600 mb-1.5">Bot User OAuth Token</label>
                    <input type="text" name="bot_token" placeholder="xoxb-..." required
                           class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-[#3b82f6] focus:outline-none font-mono" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-600 mb-1.5">Signing Secret</label>
                    <input type="text" name="signing_secret" placeholder="32-character hex string" required
                           class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-[#3b82f6] focus:outline-none font-mono" />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">{{ __('Connect Slack') }}</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Discord connect modal --}}
    <flux:modal name="discord-connect" class="w-full max-w-lg !bg-[#f5f5f5] dark:!bg-[#f5f5f5]">
        <div class="space-y-4">
            <div>
                <h3 class="text-lg font-semibold text-zinc-800">{{ __('Connect Discord Bot') }}</h3>
                <p class="text-sm text-zinc-500 mt-1">
                    Create an Application at <a href="https://discord.com/developers/applications" target="_blank" rel="noopener" class="text-emerald-600 hover:underline">discord.com/developers/applications</a>, add a Bot user, then paste the credentials below.
                </p>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-zinc-100 p-3 text-xs text-zinc-600 space-y-1.5">
                <p class="font-semibold text-zinc-700">Setup checklist</p>
                <p>① New Application → name it → copy <strong class="text-zinc-700">Application ID</strong> from General Information</p>
                <p>② Copy the <strong class="text-zinc-700">Public Key</strong> from the same page</p>
                <p>③ Bot tab → Reset Token → copy the <strong class="text-zinc-700">Bot Token</strong> (shown only once)</p>
                <p>④ General Information → set <strong class="text-zinc-700">Interactions Endpoint URL</strong> = <code class="text-emerald-600">{{ url('/api/webhooks/discord') }}</code></p>
                <p>⑤ Installation tab → enable Guild Install with <code class="text-emerald-600">applications.commands</code> scope, then use the Install Link to add the bot to your server</p>
                <p class="text-emerald-700">After connecting, your members type <code>/support &lt;message&gt;</code> in any channel; messages land in this inbox and your replies DM them back.</p>
            </div>

            <form method="POST" action="{{ route('connections.discord.connect') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-zinc-600 mb-1.5">Application ID</label>
                    <input type="text" name="application_id" placeholder="18-digit numeric snowflake" required
                           class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-[#3b82f6] focus:outline-none font-mono" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-600 mb-1.5">Public Key</label>
                    <input type="text" name="public_key" placeholder="64-character hex" required
                           class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-[#3b82f6] focus:outline-none font-mono" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-600 mb-1.5">Bot Token</label>
                    <input type="text" name="bot_token" placeholder="MTI..." required
                           class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-[#3b82f6] focus:outline-none font-mono" />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">{{ __('Connect Discord') }}</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- WhatsApp QR Modal Component --}}
    @livewire('connections.whats-app-qr-modal')

    {{-- Web Chat: embed-snippet modal (shown after creating a widget OR via "Snippet" button) --}}
    <flux:modal wire:model="showWebChatModal" class="w-full max-w-2xl">
        @if($newWebChatId)
            @php
                $widgetSrc = url('/widget.js');
                $snippet = '<script src="' . $widgetSrc . '" data-widget-id="' . $newWebChatId . '" defer></script>';
                $testUrl = url('/webchat-test.html?wid=' . $newWebChatId);
            @endphp
            <div class="space-y-5" x-data="{ tab: 'wordpress' }">
                {{-- Header --}}
                <div>
                    <flux:heading size="lg" class="!text-ink">{{ __('Your Web Chat widget is ready') }}</flux:heading>
                    <p class="mt-1 text-sm text-zinc-700 leading-relaxed">{{ __('Add the snippet below to your website and a green chat bubble appears in the bottom-right corner. Visitors who click it can chat with you — their messages land in this inbox.') }}</p>
                </div>

                {{-- Snippet block — kept intentionally dark (bg-ink) because it's a code editor
                     surface. Light-on-dark syntax is legible + it's the recognisable "code" look. --}}
                <div>
                    <p class="text-[10px] uppercase tracking-wider text-zinc-500 mb-2 font-semibold">{{ __('① Copy this snippet') }}</p>
                    <div class="rounded-xl border border-zinc-200 bg-ink p-3">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <span class="text-[10px] text-cream/60">{{ __('embed snippet') }}</span>
                            <button
                                type="button"
                                x-data
                                x-on:click="navigator.clipboard.writeText($refs.snippet.innerText); $el.innerText = '✓ {{ __('Copied') }}'; setTimeout(() => $el.innerText = '{{ __('Copy') }}', 1500)"
                                class="text-[11px] px-2 py-0.5 rounded bg-emer-600 text-white hover:bg-emer-700"
                            >{{ __('Copy') }}</button>
                        </div>
                        <pre x-ref="snippet" class="text-xs text-emerald-200 whitespace-pre-wrap break-all leading-relaxed">{{ $snippet }}</pre>
                    </div>
                </div>

                {{-- Platform-specific install instructions (tabs) --}}
                <div>
                    <p class="text-[10px] uppercase tracking-wider text-zinc-500 mb-2 font-semibold">{{ __('② Paste it on your site — pick where your site lives') }}</p>
                    <div class="flex flex-wrap gap-1 mb-3 border-b border-zinc-200">
                        @foreach(['wordpress' => 'WordPress', 'shopify' => 'Shopify', 'wix' => 'Wix', 'squarespace' => 'Squarespace', 'webflow' => 'Webflow', 'html' => 'Custom HTML'] as $key => $label)
                            <button
                                type="button"
                                x-on:click="tab = '{{ $key }}'"
                                :class="tab === '{{ $key }}' ? 'bg-emer-50 text-emer-700 border-b-2 border-emer-600 -mb-px' : 'text-zinc-600 hover:text-ink hover:bg-zinc-50'"
                                class="px-3 py-1.5 text-xs font-medium transition rounded-t-md"
                            >{{ $label }}</button>
                        @endforeach
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-xs text-zinc-700 leading-relaxed space-y-2 min-h-[140px]">
                        {{-- WordPress --}}
                        <div x-show="tab === 'wordpress'" x-cloak>
                            <p class="text-ink font-semibold mb-2">WordPress ({{ __('easiest way') }})</p>
                            <p>① {{ __('Install the free plugin') }} <strong class="text-ink">"Insert Headers and Footers"</strong> {{ __('by WPBeginner (or "WPCode").') }}</p>
                            <p>② {{ __('In your WP admin:') }} <strong class="text-ink">Settings → Insert Headers and Footers</strong>.</p>
                            <p>③ {{ __('Paste the snippet into the') }} <strong class="text-ink">"Scripts in Footer"</strong> {{ __('box.') }}</p>
                            <p>④ {{ __('Click') }} <strong class="text-ink">Save</strong>. {{ __('The bubble appears on every page within ~30 seconds (browser cache may delay).') }}</p>
                            <p class="text-zinc-500 mt-2">{{ __('No FTP / no theme editing needed.') }}</p>
                        </div>

                        {{-- Shopify --}}
                        <div x-show="tab === 'shopify'" x-cloak>
                            <p class="text-ink font-semibold mb-2">Shopify</p>
                            <p>① {{ __('In your Shopify admin:') }} <strong class="text-ink">Online Store → Themes</strong>.</p>
                            <p>② {{ __('On your active theme click') }} <strong class="text-ink">Actions → Edit code</strong>.</p>
                            <p>③ {{ __('Open') }} <code class="text-emer-700 bg-emer-50 rounded px-1 py-0.5">layout/theme.liquid</code> {{ __('in the file list.') }}</p>
                            <p>④ {{ __('Find the line with') }} <code class="text-emer-700 bg-emer-50 rounded px-1 py-0.5">&lt;/body&gt;</code> {{ __('(near the bottom). Paste the snippet on the line just above it.') }}</p>
                            <p>⑤ {{ __('Click') }} <strong class="text-ink">Save</strong>. {{ __('Refresh your storefront — the bubble shows up.') }}</p>
                        </div>

                        {{-- Wix --}}
                        <div x-show="tab === 'wix'" x-cloak>
                            <p class="text-ink font-semibold mb-2">Wix</p>
                            <p>① {{ __('In your Wix dashboard:') }} <strong class="text-ink">Settings → Custom Code</strong> ({{ __('under "Advanced"') }}).</p>
                            <p>② {{ __('Click') }} <strong class="text-ink">+ Add Custom Code</strong>.</p>
                            <p>③ {{ __('Paste the snippet into the code box. Set:') }}</p>
                            <p class="ml-4">• Name: <code class="text-emer-700 bg-emer-50 rounded px-1 py-0.5">OT1-Pro Chat</code></p>
                            <p class="ml-4">• Add Code to Pages: <strong class="text-ink">All pages</strong></p>
                            <p class="ml-4">• Place Code in: <strong class="text-ink">Body — end</strong></p>
                            <p>④ {{ __('Click') }} <strong class="text-ink">Apply</strong>. {{ __('Publish your site if needed.') }}</p>
                            <p class="text-zinc-500 mt-2">{{ __("Note: Wix free plans don't allow custom code — you need a Premium plan.") }}</p>
                        </div>

                        {{-- Squarespace --}}
                        <div x-show="tab === 'squarespace'" x-cloak>
                            <p class="text-ink font-semibold mb-2">Squarespace</p>
                            <p>① <strong class="text-ink">Settings → Advanced → Code Injection</strong>.</p>
                            <p>② {{ __('Paste the snippet into the') }} <strong class="text-ink">Footer</strong> {{ __('box.') }}</p>
                            <p>③ {{ __('Click') }} <strong class="text-ink">Save</strong>.</p>
                            <p class="text-zinc-500 mt-2">{{ __('Note: Code Injection requires a Business plan or higher.') }}</p>
                        </div>

                        {{-- Webflow --}}
                        <div x-show="tab === 'webflow'" x-cloak>
                            <p class="text-ink font-semibold mb-2">Webflow</p>
                            <p>① {{ __('Open your project →') }} <strong class="text-ink">Project Settings → Custom Code</strong>.</p>
                            <p>② {{ __('Paste the snippet in the') }} <strong class="text-ink">Footer Code</strong> {{ __('box.') }}</p>
                            <p>③ {{ __('Click') }} <strong class="text-ink">Save Changes</strong>, {{ __('then publish your site.') }}</p>
                        </div>

                        {{-- Custom HTML --}}
                        <div x-show="tab === 'html'" x-cloak>
                            <p class="text-ink font-semibold mb-2">{{ __('Plain HTML / your own framework') }}</p>
                            <p>{{ __('Open the HTML file (or template) for every page you want the bubble on. Find the closing') }} <code class="text-emer-700 bg-emer-50 rounded px-1 py-0.5">&lt;/body&gt;</code> {{ __('tag and paste the snippet on the line right before it. Example:') }}</p>
                            <pre class="mt-2 p-2 bg-ink rounded text-[11px] text-emerald-200 overflow-x-auto"><code>&lt;body&gt;
  ... {{ __('your page content') }} ...
  {{ $snippet }}
&lt;/body&gt;</code></pre>
                            <p class="mt-2 text-zinc-500">{{ __('Works with React/Vue/Next.js too — drop it in your root layout /') }} <code>_document.tsx</code> / <code>app.html</code>.</p>
                        </div>
                    </div>
                </div>

                {{-- Test it — emerald-tinted call-out block --}}
                <div class="rounded-xl border border-emer-200 bg-emer-50 p-4">
                    <p class="text-[10px] uppercase tracking-wider text-emer-700 mb-1 font-semibold">{{ __('③ Test it now (without your site)') }}</p>
                    <p class="text-xs text-zinc-700 leading-relaxed">{{ __('We hosted a demo page that already has your widget embedded. Open it, click the green bubble, send a test message — then watch your inbox.') }}</p>
                    <a href="{{ url('/webchat-test.html') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 mt-2 text-xs font-medium text-emer-700 hover:text-emer-900 hover:underline">
                        <flux:icon.arrow-top-right-on-square class="w-3.5 h-3.5" />
                        {{ __('Open test page') }}
                    </a>
                </div>

                {{-- Where messages go --}}
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 text-xs text-zinc-700 leading-relaxed">
                    <p><strong class="text-ink">{{ __('Where messages go:') }}</strong> {{ __('Site visitors\' messages appear in your') }} <a href="{{ route('inbox') }}" class="text-emer-700 hover:underline">{{ __('Inbox') }}</a> {{ __('under the channel') }} <strong class="text-ink">"Web Chat"</strong>. {{ __('Your replies are pushed back to the chat bubble within ~1.5 seconds.') }}</p>
                </div>

                {{-- Troubleshooting --}}
                <details class="rounded-xl border border-zinc-200 bg-zinc-50 p-3">
                    <summary class="text-xs text-zinc-700 cursor-pointer hover:text-ink font-medium">{{ __("Bubble isn't appearing? Click to troubleshoot") }}</summary>
                    <div class="mt-3 space-y-1.5 text-xs text-zinc-700 leading-relaxed">
                        <p><strong class="text-ink">{{ __('Wait 30 seconds and refresh.') }}</strong> {{ __('Many CDNs (Cloudflare, etc.) cache HTML — your snippet update may not be live yet.') }}</p>
                        <p><strong class="text-ink">{{ __('Check browser console') }}</strong> (F12). {{ __('If you see a CSP error, your site has a Content-Security-Policy that needs') }} <code class="text-emer-700 bg-emer-50 rounded px-1 py-0.5">{{ parse_url(url('/'), PHP_URL_HOST) }}</code> {{ __('added to') }} <code class="text-emer-700 bg-emer-50 rounded px-1 py-0.5">script-src</code>.</p>
                        <p><strong class="text-ink">{{ __('Ad blockers') }}</strong> {{ __('can sometimes block widgets. Disable yours and reload to confirm.') }}</p>
                        <p><strong class="text-ink">{{ __('Snippet in the wrong place?') }}</strong> {{ __('It must be in the page') }} <code class="text-emer-700 bg-emer-50 rounded px-1 py-0.5">&lt;body&gt;</code> — {{ __('not') }} <code class="text-emer-700 bg-emer-50 rounded px-1 py-0.5">&lt;head&gt;</code>. {{ __('The') }} <code>defer</code> {{ __('attribute means it loads after the page is ready.') }}</p>
                    </div>
                </details>

                {{-- Widget id reference --}}
                <div class="text-[11px] text-zinc-500 border-t border-zinc-200 pt-3 leading-relaxed">
                    {{ __('Widget ID:') }} <code class="text-emer-700 bg-emer-50 rounded px-1 py-0.5">{{ $newWebChatId }}</code> — {{ __('keep this private; anyone with it can post messages to your inbox under this widget.') }}
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-zinc-200">
                    <button type="button" wire:click="closeWebChatModal"
                            class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-ink hover:bg-zinc-50 transition">{{ __('Close') }}</button>
                </div>
            </div>
        @endif
    </flux:modal>

    {{-- Managed onboarding request form (FB/IG while Meta unverified).
         Rewritten for the light brand theme — the previous dark-theme classes
         (bg-blue-500/5, text-blue-200, text-white/60) rendered near-invisible
         on cream. Hand-rolled inputs used per contrast-guardrails skill because
         flux:input defaults typed text to zinc-500 (illegible in a wizard). --}}
    {{-- Force light surface — Flux modal defaults to dark bg which makes
         text-ink labels invisible. Overriding here so brand tokens work as intended. --}}
    <flux:modal name="onboarding-request" class="md:w-[560px] !bg-white dark:!bg-zinc-900">
        @php
            $platformLabel = ucfirst($requestPlatform ?? 'facebook');
            $isInstagram   = ($requestPlatform ?? '') === 'instagram';
            // Step 1 wording differs slightly between FB and IG (IG needs the linked FB page)
            $step1 = $isInstagram
                ? __('Open the Facebook page linked to your Instagram → Settings → Page setup → Page access → Add new → add our user with basic control (not full control). Instagram inherits the access through its linked Page.')
                : __('Open your Facebook Business Page → Settings → Page setup → Page access → Add new → add our user with basic control (not full control)');
        @endphp
        <form wire:submit.prevent="submitOnboardingRequest" class="space-y-5">
            <div>
                <flux:heading size="lg" class="!text-ink">{{ __('Request :platform connection', ['platform' => $platformLabel]) }}</flux:heading>
                <p class="mt-2 text-sm text-zinc-700 leading-relaxed">
                    {{ __('To connect your :platform page, we need temporary admin access via our account.', ['platform' => $platformLabel]) }}
                </p>
            </div>

            {{-- Instructions block — solid brand-blue on light bg, per contrast skill failure modes 4/5.
                 Uses paired -50 background + -900 text so it passes WCAG AA on both light and dark themes. --}}
            <div class="rounded-lg border border-blue-200 bg-blue-50 dark:border-blue-800/60 dark:bg-blue-900/20 p-4 text-sm space-y-2">
                <p class="font-semibold text-blue-900 dark:text-blue-100">{{ __('Before submitting:') }}</p>
                <ol class="list-decimal list-inside text-blue-900 dark:text-blue-100 space-y-1 leading-relaxed">
                    <li>{{ $step1 }}</li>
                    <li>{{ __('Add') }}
                        <a href="https://www.facebook.com/omarEltak88/" target="_blank" class="underline font-semibold text-blue-900 dark:text-blue-100 hover:text-blue-700">
                            {{ __('our admin account') }}
                        </a>
                        {{ __('with basic control (not full control)') }}</li>
                    <li>{{ __('Submit this form so we know which page is yours') }}</li>
                </ol>
                <p class="rounded-md bg-amber-50 border border-amber-200 text-amber-900 dark:bg-amber-900/20 dark:border-amber-800/60 dark:text-amber-100 text-xs px-3 py-2 mt-3">{{ __('Note: Meta sometimes redirects you to Meta Business Suite, and in some cases the "Add new" option is only available in the Meta Business Suite mobile app — not on desktop. If you cannot find it on PC, please use the phone app.') }}</p>
                <p class="text-xs text-blue-900/80 dark:text-blue-100/80 pt-1">{{ __("Our admin needs to accept your Page invitation on Facebook before we can finish. This usually happens within a few hours during business hours (9am–9pm Cairo). You'll get an email the moment the connection is live.") }}</p>
            </div>

            {{-- Hand-rolled inputs per contrast-guardrails failure mode 2:
                 flux:input defaults typed text to zinc-500 (illegible). --}}
            <div>
                <label for="request-business-name" class="block text-sm font-medium text-ink mb-1.5">{{ __('Business / Page name') }} <span class="text-red-600">*</span></label>
                <input id="request-business-name" type="text" required
                       wire:model="requestBusinessName"
                       placeholder="{{ __('e.g. Brandk') }}"
                       class="block w-full rounded-lg border border-zinc-300 bg-white text-ink placeholder:text-zinc-400 px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:border-emer-500 focus:ring-2 focus:ring-emer-100 transition" />
            </div>

            <div>
                <label for="request-page-url" class="block text-sm font-medium text-ink mb-1.5">{{ __('Page URL') }}</label>
                <input id="request-page-url" type="url"
                       wire:model="requestPageUrl"
                       placeholder="https://www.facebook.com/yourpage"
                       class="block w-full rounded-lg border border-zinc-300 bg-white text-ink placeholder:text-zinc-400 px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:border-emer-500 focus:ring-2 focus:ring-emer-100 transition" />
                <p class="mt-1 text-xs text-zinc-600">{{ __('Helps us find the right page if multiple are admin-shared.') }}</p>
            </div>

            <div>
                <label for="request-contact-email" class="block text-sm font-medium text-ink mb-1.5">{{ __('Contact email') }} <span class="text-red-600">*</span></label>
                <input id="request-contact-email" type="email" required
                       wire:model="requestContactEmail"
                       placeholder="you@company.com"
                       class="block w-full rounded-lg border border-zinc-300 bg-white text-ink placeholder:text-zinc-400 px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:border-emer-500 focus:ring-2 focus:ring-emer-100 transition" />
                <p class="mt-1 text-xs text-zinc-600">{{ __('We will not send marketing — we only email you if we need something to complete your setup.') }}</p>
            </div>

            <div>
                <label for="request-contact-phone" class="block text-sm font-medium text-ink mb-1.5">{{ __('WhatsApp number (optional)') }}</label>
                <input id="request-contact-phone" type="tel"
                       wire:model="requestContactPhone"
                       placeholder="+201234567890"
                       class="block w-full rounded-lg border border-zinc-300 bg-white text-ink placeholder:text-zinc-400 px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:border-emer-500 focus:ring-2 focus:ring-emer-100 transition" />
                <p class="mt-1 text-xs text-zinc-600">{{ __('Optional. We only message you if we need clarification — never marketing.') }}</p>
            </div>

            <div>
                <label for="request-notes" class="block text-sm font-medium text-ink mb-1.5">{{ __('Anything we should know? (optional)') }}</label>
                <textarea id="request-notes" rows="3"
                          wire:model="requestNotes"
                          placeholder="{{ __('e.g. multiple admins, business verification status...') }}"
                          class="block w-full rounded-lg border border-zinc-300 bg-white text-ink placeholder:text-zinc-400 px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:border-emer-500 focus:ring-2 focus:ring-emer-100 transition resize-y"></textarea>
            </div>

            {{-- Outline Cancel (not ghost — invisible on light per contrast skill failure mode 3) --}}
            <div class="flex justify-end gap-2 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                <flux:modal.close>
                    <button type="button" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-ink hover:bg-zinc-50 transition">{{ __('Cancel') }}</button>
                </flux:modal.close>
                <button type="submit" class="rounded-lg bg-emer-600 hover:bg-emer-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition">{{ __('Submit request') }}</button>
            </div>
        </form>
    </flux:modal>

    {{-- WhatsApp Embedded Signup client logic. Renders only when the config
         ID is set; without the SDK loaded the Alpine component is harmless
         (just a disabled button). --}}
    @if(! empty(config('services.meta.whatsapp_embedded_signup_config_id')) && ! empty(config('services.meta.app_id')))
    <script>
        window.waEmbeddedSignup = function (opts) {
            return {
                loading: false,
                error: '',
                sessionData: null,
                _bound: false,

                init() {
                    this._ensureSdk();
                    if (this._bound) return;
                    this._bound = true;
                    window.addEventListener('message', (ev) => {
                        // Meta sends postMessage from facebook.com during signup.
                        if (ev.origin !== 'https://www.facebook.com' && ev.origin !== 'https://web.facebook.com') return;
                        let data;
                        try { data = typeof ev.data === 'string' ? JSON.parse(ev.data) : ev.data; } catch (e) { return; }
                        if (!data || data.type !== 'WA_EMBEDDED_SIGNUP') return;
                        // Expected: { type:'WA_EMBEDDED_SIGNUP', event:'FINISH'|'CANCEL'|..., data:{waba_id,phone_number_id,business_id} }
                        if (data.event === 'FINISH' && data.data) {
                            this.sessionData = data.data;
                        } else if (data.event === 'CANCEL') {
                            this.loading = false;
                            this.error = @js(__('Signup cancelled.'));
                        } else if (data.event === 'ERROR') {
                            this.loading = false;
                            this.error = (data.data && data.data.error_message) || @js(__('Meta returned an error during signup.'));
                        }
                    });
                },

                _ensureSdk() {
                    if (window.FB) return;
                    window.fbAsyncInit = () => {
                        FB.init({ appId: opts.appId, cookie: true, xfbml: false, version: opts.graphVersion });
                    };
                    const id = 'facebook-jssdk';
                    if (document.getElementById(id)) return;
                    const js = document.createElement('script');
                    js.id = id;
                    js.src = 'https://connect.facebook.net/en_US/sdk.js';
                    js.async = true; js.defer = true; js.crossOrigin = 'anonymous';
                    document.head.appendChild(js);
                },

                launch() {
                    this.error = '';
                    this.sessionData = null;
                    if (!window.FB) {
                        this.error = @js(__('Facebook SDK is still loading. Try again in a moment.'));
                        return;
                    }
                    this.loading = true;
                    window.FB.login((response) => {
                        try {
                            if (!response || response.status !== 'connected' || !response.authResponse) {
                                this.loading = false;
                                if (!this.error) this.error = @js(__('Signup cancelled before completion.'));
                                return;
                            }
                            const code = response.authResponse.code;
                            if (!code) {
                                this.loading = false;
                                this.error = @js(__('Meta did not return a signup code.'));
                                return;
                            }
                            // Give Meta's postMessage a moment to arrive if it hasn't yet.
                            const start = Date.now();
                            const poll = () => {
                                if (this.sessionData || Date.now() - start > 4000) return this._submit(code);
                                setTimeout(poll, 150);
                            };
                            poll();
                        } catch (e) {
                            this.loading = false;
                            this.error = e.message || 'Unexpected error';
                        }
                    }, {
                        config_id: opts.configId,
                        response_type: 'code',
                        override_default_response_type: true,
                        extras: { setup: {}, featureType: '', sessionInfoVersion: 3 }
                    });
                },

                async _submit(code) {
                    try {
                        const payload = {
                            code: code,
                            waba_id: (this.sessionData && this.sessionData.waba_id) || '',
                            phone_number_id: (this.sessionData && this.sessionData.phone_number_id) || '',
                        };
                        if (!payload.waba_id) {
                            this.loading = false;
                            this.error = @js(__('Meta did not return a WhatsApp Business Account ID. Please try again.'));
                            return;
                        }
                        const res = await fetch(opts.callbackUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': opts.csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify(payload),
                            credentials: 'same-origin',
                        });
                        const body = await res.json().catch(() => ({}));
                        if (!res.ok || !body.ok) {
                            this.loading = false;
                            this.error = (body && body.message) || @js(__('Could not complete connection.'));
                            return;
                        }
                        // Hard-reload to let Livewire re-render with the new ConnectedAccount + Pages.
                        window.location.reload();
                    } catch (e) {
                        this.loading = false;
                        this.error = e.message || 'Unexpected error';
                    }
                },
            };
        };
    </script>
    @endif
</div>
