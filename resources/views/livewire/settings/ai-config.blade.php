<div class="p-3 sm:p-6 w-full min-w-0">
    <flux:heading size="xl" class="mb-2 !text-zinc-900 dark:!text-zinc-900">{{ __('AI Configuration') }}</flux:heading>
    <flux:text class="mb-6 !text-zinc-900 dark:!text-zinc-900">{{ __('Configure AI behavior per connected page. The AI uses this info to respond to customers.') }}</flux:text>

    @if($pages->isEmpty())
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-8 text-center">
            <flux:icon name="link-slash" class="mx-auto mb-3 text-zinc-400" variant="outline" />
            <flux:heading size="lg">{{ __('No connected pages') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Connect a Facebook, Instagram, WhatsApp, or Telegram page first.') }}</flux:text>
            <flux:button :href="route('connections.index')" variant="primary" class="mt-4" wire:navigate>
                {{ __('Go to Connections') }}
            </flux:button>
        </div>
    @else
        <div class="grid gap-6 md:[grid-template-columns:16rem_1fr] grid-cols-1 min-w-0">
            {{-- Left: Page Selector --}}
            <div>
                <flux:heading size="sm" class="mb-3">{{ __('Pages') }}</flux:heading>
                <div class="space-y-1">
                    @foreach($pages as $page)
                        <button
                            wire:click="selectPage({{ $page->id }})"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-left transition-colors
                                {{ $selectedPageId === $page->id
                                    ? 'bg-violet-700 text-white ring-2 ring-violet-300'
                                    : 'bg-violet-600 text-white hover:bg-violet-700' }}"
                        >
                            <flux:icon
                                :name="match($page->platform) {
                                    'instagram' => 'camera',
                                    'whatsapp' => 'phone',
                                    'telegram' => 'paper-airplane',
                                    default => 'chat-bubble-left-right',
                                }"
                                class="size-4 shrink-0"
                            />
                            <span class="truncate text-sm font-medium">{{ $page->name }}</span>
                            @if($page->aiConfig && $page->aiConfig->is_active)
                                <span class="ml-auto size-2 rounded-full bg-green-500 shrink-0"></span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Right: Config Form --}}
            <div class="min-w-0">
                @if(!$selectedPageId)
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-8 text-center">
                        <flux:text>{{ __('Select a page from the left to configure its AI settings.') }}</flux:text>
                    </div>
                @else
                    {{-- Force black text on all Flux labels and controls in dark mode --}}
                    <style>
                        [data-flux-label] { color: rgb(9,9,11) !important; }
                        [data-flux-control] { color: rgb(9,9,11) !important; }
                        .kw-chip input[data-flux-control] { color: white !important; border-color: white !important; }
                        /* Topic keyword chips sit on a white card, so the global white-text
                           rule above would make them invisible. Force dark text here. */
                        .topic-kw-chip input[data-flux-control] { color: rgb(9,9,11) !important; border-color: rgb(228,228,231) !important; }
                        /* Flux switch has nearly-transparent bg when off; make it visibly
                           grey against light backgrounds so users can see the OFF state. */
                        [data-flux-switch]:not([aria-checked="true"]) { background-color: rgb(212,212,216) !important; }
                    </style>
                    <form wire:submit="saveConfig" class="space-y-8 [&_input]:!border-violet-400 [&_textarea]:!border-violet-400 [&_select]:!border-violet-400">
                        {{-- Active Toggle --}}
                        <div class="rounded-xl border-2 {{ $is_active ? 'border-green-500 bg-green-50 dark:bg-green-900/10' : 'border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800/50' }} p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <flux:heading size="sm" class="text-zinc-900">
                                        {{ __('AI for this page:') }}
                                        <span class="{{ $is_active ? 'text-green-600' : 'text-zinc-500' }}">
                                            {{ $is_active ? __('Active') : __('Inactive') }}
                                        </span>
                                    </flux:heading>
                                    <flux:text size="sm" class="mt-0.5 text-zinc-900">
                                        @if(!$hasConfig)
                                            {{ __('Fill in the form below and save to enable AI for this page.') }}
                                        @else
                                            {{ $is_active ? __('AI will respond to messages on this page.') : __('AI is paused for this page.') }}
                                        @endif
                                    </flux:text>
                                </div>
                                <flux:switch wire:model.live="is_active" />
                            </div>
                        </div>

                        {{-- Tab strip. Splits the config into 4 focused areas so the form
                             doesn't feel like a wall of settings. --}}
                        @php
                            $selectedPage = $pages->firstWhere('id', $selectedPageId);
                            $tabs = [
                                'sales_goal' => ['label' => __('Sales Goal'),  'icon' => 'flag'],
                                'knowledge'  => ['label' => __('Knowledge'),   'icon' => 'book-open'],
                                'behavior'   => ['label' => __('Behavior'),    'icon' => 'sparkles'],
                                'handoff'    => ['label' => __('Handoff'),     'icon' => 'user-group'],
                            ];
                            if ($selectedPage && in_array($selectedPage->platform, ['facebook', 'instagram'], true)) {
                                $tabs['comments'] = ['label' => __('Comments'), 'icon' => 'chat-bubble-oval-left-ellipsis'];
                            }
                        @endphp
                        <div class="flex flex-wrap gap-1.5 rounded-xl border border-zinc-200 dark:border-zinc-700 p-1.5 bg-zinc-50 dark:bg-zinc-900/40">
                            @foreach($tabs as $tabKey => $tabMeta)
                                <button
                                    type="button"
                                    wire:click="setTab('{{ $tabKey }}')"
                                    class="flex items-center gap-2 px-3.5 py-2 rounded-lg text-sm font-medium transition-colors
                                        {{ $activeTab === $tabKey
                                            ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-800 dark:text-white'
                                            : 'text-zinc-900 hover:text-zinc-900 hover:bg-white/50' }}"
                                >
                                    <flux:icon :name="$tabMeta['icon']" class="w-4 h-4" />
                                    {{ $tabMeta['label'] }}
                                </button>
                            @endforeach
                        </div>

                        {{-- Tab: Sales Goal --}}
                        @if($activeTab === 'sales_goal')
                            @php
                                $presets = [
                                    ['key' => 'info_only',    'title' => __('Info only'),            'desc' => __('AI answers questions but never asks for data. Escalate only when a customer explicitly asks for a human.')],
                                    ['key' => 'capture_data', 'title' => __('Capture specific data'), 'desc' => __('AI naturally collects the fields you select (email, phone, address, etc.), then hands off.')],
                                    ['key' => 'booking',      'title' => __('Booking'),               'desc' => __('AI qualifies the customer and captures name, phone, and preferred slot.')],
                                    ['key' => 'ecommerce',    'title' => __('E-commerce order'),     'desc' => __('AI takes orders end-to-end: product, quantity, shipping address, phone.')],
                                    ['key' => 'custom',       'title' => __('Custom'),               'desc' => __('Define your own fields and escalation triggers. Full control.')],
                                ];
                            @endphp
                            <section>
                                <flux:heading size="lg" class="mb-1 text-zinc-900">{{ __('What is the goal of this conversation?') }}</flux:heading>
                                <flux:text size="sm" class="mb-4 text-zinc-900">{{ __('Pick the preset that matches your business. The AI is told about the goal in every reply and pushes toward it naturally.') }}</flux:text>

                                <div class="grid gap-3 md:grid-cols-2">
                                    @foreach($presets as $preset)
                                        <button
                                            type="button"
                                            wire:click="applySalesGoalPreset('{{ $preset['key'] }}')"
                                            class="text-left p-4 rounded-xl border-2 transition-colors
                                                {{ $sales_goal_preset === $preset['key']
                                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30'
                                                    : 'border-violet-300 hover:border-violet-400' }}"
                                        >
                                            <div class="flex items-start justify-between gap-2 mb-1">
                                                <span class="font-medium !text-zinc-900 dark:!text-zinc-900">{{ $preset['title'] }}</span>
                                                @if($sales_goal_preset === $preset['key'])
                                                    <flux:icon name="check-circle" class="w-5 h-5 text-blue-500 shrink-0" />
                                                @endif
                                            </div>
                                            <p class="text-xs text-zinc-900 leading-relaxed">{{ $preset['desc'] }}</p>
                                        </button>
                                    @endforeach
                                </div>
                            </section>

                            @if($sales_goal_preset !== 'info_only')
                                <section>
                                    <flux:heading size="lg" class="mb-1">{{ __('Data to capture') }}</flux:heading>
                                    <flux:text size="sm" class="mb-4">{{ __('These fields will be extracted from the customer\'s messages. When all are captured, the AI stops and the conversation is marked completed.') }}</flux:text>

                                    <div class="space-y-2">
                                        @forelse($required_capture_fields as $index => $field)
                                            <div class="flex items-center gap-2">
                                                <flux:input wire:model="required_capture_fields.{{ $index }}.label" placeholder="{{ __('Label (e.g. Email address)') }}" class="flex-1 text-zinc-900" />
                                                <flux:input wire:model="required_capture_fields.{{ $index }}.key" placeholder="{{ __('key (e.g. email)') }}" class="w-40 text-zinc-900" />
                                                <flux:select wire:model="required_capture_fields.{{ $index }}.type" class="w-32 text-zinc-900">
                                                    <flux:select.option value="text">{{ __('Text') }}</flux:select.option>
                                                    <flux:select.option value="email">{{ __('Email') }}</flux:select.option>
                                                    <flux:select.option value="phone">{{ __('Phone') }}</flux:select.option>
                                                    <flux:select.option value="address">{{ __('Address') }}</flux:select.option>
                                                </flux:select>
                                                <flux:button wire:click="removeCaptureField({{ $index }})" type="button" variant="ghost" size="sm" icon="x-mark" square />
                                            </div>
                                        @empty
                                            <flux:text size="sm" class="text-zinc-500 italic">{{ __('No fields configured yet.') }}</flux:text>
                                        @endforelse
                                    </div>

                                    <flux:button wire:click="addCaptureField" type="button" variant="outline" size="sm" icon="plus" class="mt-3">
                                        {{ __('Add field') }}
                                    </flux:button>
                                </section>
                            @endif
                        @endif

                        {{-- Tab: Knowledge --}}
                        @if($activeTab === 'knowledge')
                        {{-- Section: Business Info --}}
                        <section>
                            <flux:heading size="lg" class="mb-1 text-zinc-900">{{ __('Business Info') }}</flux:heading>
                            <flux:text size="sm" class="mb-4 text-zinc-900">{{ __('Tell the AI about your business so it can answer customer questions accurately.') }}</flux:text>

                            <div x-data="{ count: $wire.entangle('business_description').length ?? 0 }" x-init="$watch('$wire.business_description', v => count = (v ?? '').length)">
                                <flux:textarea
                                    wire:model="business_description"
                                    label="{{ __('Business Description') }}"
                                    placeholder="{{ __('e.g. We are a boutique flower shop in downtown Beirut specializing in fresh arrangements, wedding decorations, and same-day delivery...') }}"
                                    rows="3"
                                    maxlength="1500"
                                    class="text-zinc-900"
                                />
                                <div class="flex justify-between mt-1">
                                    @error('business_description') <p class="text-red-500 text-sm">{{ $message }}</p> @else <span></span> @enderror
                                    <p class="text-xs text-zinc-900" :class="count > 1400 ? 'text-amber-500' : ''" x-text="count + ' / 1500'"></p>
                                </div>
                            </div>

                            <flux:textarea
                                wire:model="additional_instructions"
                                label="{{ __('Additional Instructions') }}"
                                placeholder="{{ __('e.g. Always greet in Arabic first. Never offer discounts above 10%. If someone asks about wholesale, ask for their business name and forward to the team.') }}"
                                rows="3"
                                class="mt-4 !text-zinc-900 dark:!text-zinc-900 [&_label]:!text-zinc-900 dark:[&_label]:!text-zinc-900"
                            />
                            <flux:text size="sm" class="mt-1 text-zinc-900">{{ __('Custom rules the AI must always follow when responding on this page.') }}</flux:text>
                        </section>

                        {{-- Section: Products --}}
                        <section>
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <flux:heading size="lg" class="mb-1 text-zinc-900">{{ __('Products / Services') }}</flux:heading>
                                    <flux:text size="sm" class="text-zinc-900">{{ __('List what you sell so the AI can recommend and describe products.') }}</flux:text>
                                </div>
                                <flux:button size="sm" variant="ghost" wire:click="addProduct" type="button" icon="plus" class="!bg-violet-600 !text-white hover:!bg-violet-700">
                                    {{ __('Add') }}
                                </flux:button>
                            </div>

                            @forelse($product_catalog as $i => $product)
                                <div class="flex gap-3 mb-3 items-start" wire:key="product-{{ $i }}">
                                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <flux:input wire:model="product_catalog.{{ $i }}.name" placeholder="{{ __('Product name') }}" size="sm" class="text-zinc-900" />
                                        <flux:input wire:model="product_catalog.{{ $i }}.description" placeholder="{{ __('Short description') }}" size="sm" class="text-zinc-900" />
                                        <flux:input wire:model="product_catalog.{{ $i }}.price" placeholder="{{ __('Price (e.g. $25)') }}" size="sm" class="text-zinc-900" />
                                    </div>
                                    <flux:button size="sm" variant="ghost" wire:click="removeProduct({{ $i }})" type="button" icon="x-mark" class="mt-0.5" />
                                </div>
                            @empty
                                <div class="text-sm text-zinc-900 py-2">{{ __('No products added yet. Click "Add" to get started.') }}</div>
                            @endforelse
                        </section>

                        {{-- Section: Pricing --}}
                        <section>
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <flux:heading size="lg" class="mb-1 text-zinc-900">{{ __('Pricing') }}</flux:heading>
                                    <flux:text size="sm" class="text-zinc-900">{{ __('Add pricing details the AI should know about.') }}</flux:text>
                                </div>
                                <flux:button size="sm" variant="ghost" wire:click="addPricing" type="button" icon="plus" class="!bg-violet-600 !text-white hover:!bg-violet-700">
                                    {{ __('Add') }}
                                </flux:button>
                            </div>

                            @forelse($pricing_info as $i => $pricing)
                                <div class="flex gap-3 mb-3 items-start" wire:key="pricing-{{ $i }}">
                                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <flux:input wire:model="pricing_info.{{ $i }}.item" placeholder="{{ __('Item / Package') }}" size="sm" class="text-zinc-900" />
                                        <flux:input wire:model="pricing_info.{{ $i }}.price" placeholder="{{ __('Price') }}" size="sm" class="text-zinc-900" />
                                        <flux:input wire:model="pricing_info.{{ $i }}.notes" placeholder="{{ __('Notes (optional)') }}" size="sm" class="text-zinc-900" />
                                    </div>
                                    <flux:button size="sm" variant="ghost" wire:click="removePricing({{ $i }})" type="button" icon="x-mark" class="mt-0.5" />
                                </div>
                            @empty
                                <div class="text-sm text-zinc-900 py-2">{{ __('No pricing entries yet.') }}</div>
                            @endforelse
                        </section>

                        {{-- Section: FAQ --}}
                        <section>
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <flux:heading size="lg" class="mb-1 text-zinc-900">{{ __('FAQ') }}</flux:heading>
                                    <flux:text size="sm" class="text-zinc-900">{{ __('Common questions & answers the AI should know.') }}</flux:text>
                                </div>
                                <flux:button size="sm" variant="ghost" wire:click="addFaq" type="button" icon="plus" class="!bg-violet-600 !text-white hover:!bg-violet-700">
                                    {{ __('Add') }}
                                </flux:button>
                            </div>

                            @forelse($faq as $i => $item)
                                <div class="flex gap-3 mb-3 items-start" wire:key="faq-{{ $i }}">
                                    <div class="flex-1 space-y-2">
                                        <flux:input wire:model="faq.{{ $i }}.question" placeholder="{{ __('Question') }}" size="sm" class="text-zinc-900" />
                                        <flux:textarea wire:model="faq.{{ $i }}.answer" placeholder="{{ __('Answer') }}" rows="2" resize="none" class="text-zinc-900" />
                                    </div>
                                    <flux:button size="sm" variant="ghost" wire:click="removeFaq({{ $i }})" type="button" icon="x-mark" class="mt-0.5" />
                                </div>
                            @empty
                                <div class="text-sm text-zinc-900 py-2">{{ __('No FAQ entries yet.') }}</div>
                            @endforelse
                        </section>
                        @endif {{-- /Knowledge tab --}}

                        {{-- Tab: Behavior --}}
                        @if($activeTab === 'behavior')
                        {{-- Section: Tone & Language --}}
                        <section>
                            <flux:heading size="lg" class="mb-1 text-zinc-900">{{ __('Tone & Language') }}</flux:heading>
                            <flux:text size="sm" class="mb-4 text-zinc-900">{{ __('Control how the AI communicates with your customers.') }}</flux:text>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <flux:select wire:model="tone" label="{{ __('Tone') }}" class="text-zinc-900">
                                    <flux:select.option value="friendly">{{ __('Friendly') }}</flux:select.option>
                                    <flux:select.option value="professional">{{ __('Professional') }}</flux:select.option>
                                    <flux:select.option value="casual">{{ __('Casual') }}</flux:select.option>
                                    <flux:select.option value="formal">{{ __('Formal') }}</flux:select.option>
                                </flux:select>

                                <flux:select wire:model="language" label="{{ __('Language') }}" class="text-zinc-900">
                                    <flux:select.option value="en">{{ __('English') }}</flux:select.option>
                                    <flux:select.option value="ar">{{ __('Arabic') }}</flux:select.option>
                                    <flux:select.option value="fr">{{ __('French') }}</flux:select.option>
                                    <flux:select.option value="es">{{ __('Spanish') }}</flux:select.option>
                                    <flux:select.option value="auto">{{ __('Auto-detect') }}</flux:select.option>
                                </flux:select>
                            </div>
                        </section>

                        {{-- Section: Response Timing --}}
                        <section>
                            <flux:heading size="lg" class="mb-1 text-zinc-900">{{ __('Response Timing') }}</flux:heading>
                            <flux:text size="sm" class="mb-4 text-zinc-900">{{ __('Set a random delay range so responses feel natural, not instant.') }}</flux:text>

                            <div class="mb-4 rounded-lg border border-emerald-500/30 bg-emerald-500/5 p-3.5 text-sm">
                                <div class="flex items-start gap-2.5">
                                    <flux:icon name="light-bulb" class="w-4 h-4 text-emerald-500 dark:text-emerald-400 mt-0.5 shrink-0" />
                                    <div class="text-zinc-900 leading-relaxed">
                                        <span class="font-medium">{{ __('Longer delays save you AI tokens.') }}</span>
                                        <span class="text-zinc-900">
                                            {{ __('Customers often send multiple messages in a row (e.g. "Hi", "are you there", "I have a question"). With a longer delay, the AI waits for the full burst and replies to everything at once instead of firing a separate reply — and a separate token bill — for each message. We recommend at least') }} <span class="font-medium">{{ __('60 seconds') }}</span> {{ __('for cost efficiency.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <flux:input
                                    wire:model="response_delay_min_seconds"
                                    type="number"
                                    label="{{ __('Minimum delay (seconds)') }}"
                                    min="10"
                                    max="300"
                                    class="!text-zinc-900 dark:!text-zinc-900 [&_label]:!text-zinc-900 dark:[&_label]:!text-zinc-900 [&_input]:!text-zinc-900 dark:[&_input]:!text-zinc-900"
                                />
                                <flux:input
                                    wire:model="response_delay_max_seconds"
                                    type="number"
                                    label="{{ __('Maximum delay (seconds)') }}"
                                    min="10"
                                    max="600"
                                    class="!text-zinc-900 dark:!text-zinc-900 [&_label]:!text-zinc-900 dark:[&_label]:!text-zinc-900 [&_input]:!text-zinc-900 dark:[&_input]:!text-zinc-900"
                                />
                            </div>
                            @error('response_delay_min_seconds') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            @error('response_delay_max_seconds') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </section>

                        {{-- Section: Working Hours --}}
                        <section>
                            <flux:heading size="lg" class="mb-1 text-zinc-900">{{ __('Working Hours') }}</flux:heading>
                            <flux:text size="sm" class="mb-4 text-zinc-900">{{ __('AI will only respond during these hours. Outside of them, messages wait for humans.') }}</flux:text>

                            <div class="mb-4 flex items-center justify-between rounded-lg border border-violet-300 p-3">
                                <div>
                                    <div class="text-sm font-medium text-zinc-900">{{ __('Always on (24/7)') }}</div>
                                    <div class="text-xs text-zinc-900">{{ __('When on, the AI replies any time of day — schedule below is ignored.') }}</div>
                                </div>
                                <flux:switch wire:model.live="is_24_7" class="[&:not([data-checked])]:!bg-zinc-500 dark:[&:not([data-checked])]:!bg-zinc-500" />
                            </div>

                            <div @class(['opacity-50 pointer-events-none' => $is_24_7])>
                                <div class="mb-4">
                                    <flux:select wire:model="timezone" label="{{ __('Timezone') }}" class="!text-zinc-900 dark:!text-zinc-900 [&_label]:!text-zinc-900 dark:[&_label]:!text-zinc-900 [&_select]:!text-zinc-900 dark:[&_select]:!text-zinc-900">
                                        @foreach(['UTC', 'Asia/Beirut', 'Asia/Dubai', 'Asia/Riyadh', 'Europe/London', 'Europe/Paris', 'America/New_York', 'America/Chicago', 'America/Los_Angeles'] as $tz)
                                            <flux:select.option value="{{ $tz }}">{{ $tz }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </div>

                                <div class="space-y-2">
                                    @foreach(['monday' => 'Mon', 'tuesday' => 'Tue', 'wednesday' => 'Wed', 'thursday' => 'Thu', 'friday' => 'Fri', 'saturday' => 'Sat', 'sunday' => 'Sun'] as $day => $label)
                                        <div class="flex items-center gap-3">
                                            <div class="w-10">
                                                <flux:switch wire:model.live="working_hours.{{ $day }}.enabled" class="[&:not([data-checked])]:!bg-zinc-500 dark:[&:not([data-checked])]:!bg-zinc-500" />
                                            </div>
                                            <span class="w-10 text-sm font-medium !text-zinc-900 dark:!text-zinc-900">{{ $label }}</span>
                                            @if($working_hours[$day]['enabled'] ?? false)
                                                <flux:input wire:model="working_hours.{{ $day }}.start" type="time" size="sm" class="w-32 [&_input]:!text-zinc-900 dark:[&_input]:!text-zinc-900" />
                                                <span class="text-sm font-medium !text-zinc-900 dark:!text-zinc-900">{{ __('to') }}</span>
                                                <flux:input wire:model="working_hours.{{ $day }}.end" type="time" size="sm" class="w-32 [&_input]:!text-zinc-900 dark:[&_input]:!text-zinc-900" />
                                            @else
                                                <span class="text-sm !text-zinc-900 dark:!text-zinc-900">{{ __('Closed') }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </section>
                        @endif {{-- /Behavior tab --}}

                        {{-- Tab: Handoff --}}
                        @if($activeTab === 'handoff')
                            {{-- Intro: what handoff means, in one place, in plain English. --}}
                            <section class="rounded-lg border border-violet-200 bg-violet-50 p-4">
                                <flux:heading size="sm" class="mb-1 text-violet-900">{{ __('What is handoff?') }}</flux:heading>
                                <flux:text size="sm" class="text-violet-900">{{ __('Handoff means: the AI stops replying and the conversation is flagged as "Escalated" in your inbox so a human on your team takes over. The customer never sees an apology or an error — they just stop getting AI replies until you (or a teammate) jumps in. Any rule you turn on below will trigger a handoff automatically.') }}</flux:text>
                            </section>

                            {{-- Section: Escalation Keywords --}}
                            <section>
                                <flux:heading size="lg" class="mb-1 text-zinc-900">{{ __('Escalation keywords') }}</flux:heading>
                                <flux:text size="sm" class="mb-4 text-zinc-900">{{ __('If the customer\'s message contains any of these words, the AI stops and a human takes over. Matching is case-insensitive and works on partial words too (e.g. "refund" matches "refunds"). Bilingual defaults (Arabic + English) come from your Sales Goal preset — feel free to add or remove.') }}</flux:text>

                                <div class="flex flex-wrap gap-2 mb-3">
                                    @foreach($escalation_keywords as $index => $kw)
                                        <div wire:key="kw-{{ $index }}" class="kw-chip flex items-center gap-1 rounded-full bg-zinc-100 dark:bg-zinc-800 border border-white pl-3 pr-1 py-1" style="--color-violet-400: white;">
                                            <flux:input
                                                wire:model.blur="escalation_keywords.{{ $index }}"
                                                size="xs"
                                                class="!w-32 !bg-transparent !p-0 !text-sm"
                                            />
                                            <button type="button" wire:click="removeEscalationKeyword({{ $index }})" class="w-5 h-5 flex items-center justify-center rounded-full text-white hover:bg-white/20">
                                                <flux:icon name="x-mark" class="w-3 h-3" />
                                            </button>
                                        </div>
                                    @endforeach
                                </div>

                                <flux:button wire:click="addEscalationKeyword" type="button" variant="outline" size="sm" icon="plus">
                                    {{ __('Add keyword') }}
                                </flux:button>
                            </section>

                            {{-- Section: Media handoff --}}
                            <section>
                                <flux:heading size="lg" class="mb-1 text-zinc-900">{{ __('Hand off when the customer sends media') }}</flux:heading>
                                <flux:text size="sm" class="mb-4 text-zinc-900">{{ __('When ON: if the customer sends a photo, video, voice note, audio clip, sticker, or document / file, the AI will not reply and the conversation is escalated to a human. Use this when your product actually needs a person to look at what the customer sent — for example: an ID card, a prescription photo, a damaged-item picture, or a voice message. When OFF: the AI keeps replying based on any text the customer sent alongside the media (and ignores the media itself).') }}</flux:text>

                                <label class="flex items-start gap-3 rounded-lg border border-zinc-200 p-3 cursor-pointer hover:bg-zinc-50">
                                    <flux:switch wire:model.live="escalate_on_media" class="mt-0.5" />
                                    <div>
                                        <div class="text-sm font-medium text-zinc-900">{{ __('Escalate on any image, video, audio, or file') }}</div>
                                        <div class="text-xs text-zinc-600 mt-0.5">{{ __('All-or-nothing: covers every non-text attachment across Facebook, Instagram, WhatsApp, Telegram, and email.') }}</div>
                                    </div>
                                </label>
                            </section>

                            {{-- Section: Topic-based handoff --}}
                            <section>
                                <flux:heading size="lg" class="mb-1 text-zinc-900">{{ __('Hand off on specific topics') }}</flux:heading>
                                <flux:text size="sm" class="mb-4 text-zinc-900">{{ __('Group keywords under a topic label (e.g. "Medical", "Legal", "Financial"). If the customer\'s message contains ANY keyword from a topic, the AI stops and a human takes over. The topic label shows up in the escalation reason in your inbox, so you know why the AI backed off. Use this for questions that are risky for AI to answer — medical advice, legal questions, refund disputes — anything you\'d rather a human handle.') }}</flux:text>

                                <div class="rounded-md bg-amber-50 border border-amber-200 px-3 py-2 mb-4">
                                    <flux:text size="xs" class="text-amber-900">{{ __('Tip: keyword matching only finds the exact words you list. "Prescription" won\'t match a customer who writes "meds my doctor gave me". Add multiple variants (English + Arabic + slang) per topic to catch more phrasings.') }}</flux:text>
                                </div>

                                <div class="space-y-3 mb-3">
                                    @foreach($escalation_topics as $topicIndex => $topic)
                                        <div wire:key="topic-{{ $topicIndex }}" class="rounded-lg border border-zinc-200 p-3 bg-white">
                                            <div class="flex items-center gap-2 mb-2">
                                                <flux:input
                                                    wire:model.blur="escalation_topics.{{ $topicIndex }}.label"
                                                    placeholder="{{ __('Topic label (e.g. Medical)') }}"
                                                    class="flex-1 text-zinc-900"
                                                    size="sm"
                                                />
                                                <flux:button
                                                    wire:click="removeEscalationTopic({{ $topicIndex }})"
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    icon="trash"
                                                >
                                                    {{ __('Remove topic') }}
                                                </flux:button>
                                            </div>

                                            <flux:text size="xs" class="mb-2 text-zinc-600">{{ __('Keywords that trigger handoff for this topic:') }}</flux:text>

                                            <div class="flex flex-wrap gap-2 mb-2">
                                                @foreach(($topic['keywords'] ?? []) as $kwIndex => $kw)
                                                    <div wire:key="topic-{{ $topicIndex }}-kw-{{ $kwIndex }}" class="topic-kw-chip flex items-center gap-1 rounded-full bg-zinc-100 border border-zinc-300 pl-3 pr-1 py-1">
                                                        <flux:input
                                                            wire:model.blur="escalation_topics.{{ $topicIndex }}.keywords.{{ $kwIndex }}"
                                                            size="xs"
                                                            class="!w-32 !bg-transparent !p-0 !text-sm"
                                                        />
                                                        <button type="button" wire:click="removeTopicKeyword({{ $topicIndex }}, {{ $kwIndex }})" class="w-5 h-5 flex items-center justify-center rounded-full text-zinc-500 hover:bg-zinc-300">
                                                            <flux:icon name="x-mark" class="w-3 h-3" />
                                                        </button>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <flux:button
                                                wire:click="addTopicKeyword({{ $topicIndex }})"
                                                type="button"
                                                variant="outline"
                                                size="xs"
                                                icon="plus"
                                            >
                                                {{ __('Add keyword to this topic') }}
                                            </flux:button>
                                        </div>
                                    @endforeach
                                </div>

                                <flux:button wire:click="addEscalationTopic" type="button" variant="outline" size="sm" icon="plus">
                                    {{ __('Add a topic') }}
                                </flux:button>
                            </section>

                            {{-- Section: Per-contact AI reply cap --}}
                            <section>
                                <flux:heading size="lg" class="mb-1 text-zinc-900">{{ __('Per-contact daily AI reply cap') }}</flux:heading>
                                <flux:text size="sm" class="mb-4 text-zinc-900">{{ __('The maximum number of AI replies a single customer can receive in any 24-hour window. Once a customer hits this number, the AI stops replying to them (their messages still arrive in your inbox — the AI just stays quiet) until 24 hours have passed since their first reply of the day. This protects you from one chatty or abusive customer draining your AI budget. Recommended: 20 for most businesses; lower it if you sell high-touch products where humans should take over quickly.') }}</flux:text>

                                <div class="max-w-xs">
                                    <flux:input
                                        wire:model="contact_ai_reply_cap"
                                        type="number"
                                        label="{{ __('Cap (5-50 replies/contact/day)') }}"
                                        min="{{ \App\Models\AiConfig::CONTACT_CAP_MIN }}"
                                        max="{{ \App\Models\AiConfig::CONTACT_CAP_MAX }}"
                                        class="text-zinc-900"
                                    />
                                    @error('contact_ai_reply_cap') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                                </div>
                            </section>
                        @endif {{-- /Handoff tab --}}

                        {{-- Tab: Comments (Facebook & Instagram only) --}}
                        @if($activeTab === 'comments')
                            {{-- Coming-soon banner: feature activates once Meta App Review
                                 approves pages_manage_engagement + instagram_manage_comments. --}}
                            <section class="rounded-lg border border-violet-200 bg-violet-50 p-4">
                                <flux:heading size="sm" class="mb-1 text-violet-900">{{ __('Coming soon — save your settings now') }}</flux:heading>
                                <flux:text size="sm" class="text-violet-900">{{ __('Comment features activate once Meta approves our Instagram & Facebook comment permissions (in App Review). Save your config here — it applies automatically the moment approval lands. Nothing you configure runs until then.') }}</flux:text>
                            </section>

                            {{-- Master switch --}}
                            <div class="rounded-xl border-2 {{ $comment_enabled ? 'border-green-500 bg-green-50 dark:bg-green-900/10' : 'border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800/50' }} p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <flux:heading size="sm" class="text-zinc-900">
                                            {{ __('Comment AI for this page:') }}
                                            <span class="{{ $comment_enabled ? 'text-green-600' : 'text-zinc-500' }}">
                                                {{ $comment_enabled ? __('Enabled') : __('Off') }}
                                            </span>
                                        </flux:heading>
                                        <flux:text size="sm" class="mt-0.5 text-zinc-900">{{ __('Turn this on to let the AI reply to comments on your posts, and optionally DM the commenter.') }}</flux:text>
                                    </div>
                                    <flux:switch wire:model.live="comment_enabled" />
                                </div>
                            </div>

                            @if($comment_enabled)
                                {{-- Public reply mode --}}
                                <section>
                                    <flux:heading size="lg" class="mb-1 text-zinc-900">{{ __('When should the AI reply publicly?') }}</flux:heading>
                                    <flux:text size="sm" class="mb-4 text-zinc-900">{{ __('Choose which comments the AI should reply to in public, right under the post.') }}</flux:text>

                                    @php
                                        $replyModes = [
                                            ['key' => \App\Models\AiConfig::COMMENT_REPLY_OFF,                      'title' => __('Off'),                          'desc' => __('AI never replies to comments publicly.')],
                                            ['key' => \App\Models\AiConfig::COMMENT_REPLY_ALL,                      'title' => __('All comments'),                 'desc' => __('AI replies to every comment on your posts.')],
                                            ['key' => \App\Models\AiConfig::COMMENT_REPLY_QUESTIONS_AND_COMPLAINTS, 'title' => __('Questions & complaints only'), 'desc' => __('Recommended: AI only replies when the comment asks something or expresses a problem.')],
                                            ['key' => \App\Models\AiConfig::COMMENT_REPLY_CUSTOM_KEYWORDS,          'title' => __('Custom keywords'),              'desc' => __('AI only replies to comments containing keywords you list.')],
                                        ];
                                    @endphp
                                    <div class="grid gap-3 md:grid-cols-2">
                                        @foreach($replyModes as $mode)
                                            <button
                                                type="button"
                                                wire:click="$set('comment_reply_mode', '{{ $mode['key'] }}')"
                                                class="text-left p-4 rounded-xl border-2 transition-colors
                                                    {{ $comment_reply_mode === $mode['key']
                                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30'
                                                        : 'border-violet-300 hover:border-violet-400' }}"
                                            >
                                                <div class="flex items-start justify-between gap-2 mb-1">
                                                    <span class="font-medium !text-zinc-900 dark:!text-zinc-900">{{ $mode['title'] }}</span>
                                                    @if($comment_reply_mode === $mode['key'])
                                                        <flux:icon name="check-circle" class="w-5 h-5 text-blue-500 shrink-0" />
                                                    @endif
                                                </div>
                                                <p class="text-xs text-zinc-900 leading-relaxed">{{ $mode['desc'] }}</p>
                                            </button>
                                        @endforeach
                                    </div>

                                    @if($comment_reply_mode === \App\Models\AiConfig::COMMENT_REPLY_CUSTOM_KEYWORDS)
                                        <div class="mt-4">
                                            <flux:text size="sm" class="mb-2 text-zinc-900">{{ __('Reply only when the comment contains any of these keywords:') }}</flux:text>
                                            <div class="flex flex-wrap gap-2 mb-3">
                                                @foreach($comment_reply_keywords as $index => $kw)
                                                    <div wire:key="crk-{{ $index }}" class="kw-chip flex items-center gap-1 rounded-full bg-zinc-100 dark:bg-zinc-800 border border-white pl-3 pr-1 py-1">
                                                        <flux:input wire:model.blur="comment_reply_keywords.{{ $index }}" size="xs" class="!w-32 !bg-transparent !p-0 !text-sm" />
                                                        <button type="button" wire:click="removeCommentReplyKeyword({{ $index }})" class="w-5 h-5 flex items-center justify-center rounded-full text-white hover:bg-white/20">
                                                            <flux:icon name="x-mark" class="w-3 h-3" />
                                                        </button>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <flux:button wire:click="addCommentReplyKeyword" type="button" variant="outline" size="sm" icon="plus">
                                                {{ __('Add keyword') }}
                                            </flux:button>
                                        </div>
                                    @endif
                                </section>

                                {{-- DM behavior --}}
                                <section>
                                    <flux:heading size="lg" class="mb-1 text-zinc-900">{{ __('Should the AI DM the commenter?') }}</flux:heading>
                                    <flux:text size="sm" class="mb-4 text-zinc-900">{{ __('The AI can open a private message with the person who commented. Meta allows one AI-sent DM per comment: 24 hours on Facebook, 7 days on Instagram. After that the customer must reply for the conversation to continue.') }}</flux:text>

                                    @php
                                        $dmModes = [
                                            ['key' => \App\Models\AiConfig::COMMENT_DM_OFF,                'title' => __('Off'),                       'desc' => __('AI never DMs commenters. Public reply only.')],
                                            ['key' => \App\Models\AiConfig::COMMENT_DM_ALWAYS,             'title' => __('Always DM after replying'),  'desc' => __('Every time the AI replies publicly, it also opens a DM with the commenter.')],
                                            ['key' => \App\Models\AiConfig::COMMENT_DM_ON_PURCHASE_INTENT, 'title' => __('DM only on purchase intent'),'desc' => __('DM only when the comment contains a buying-intent keyword you list below.')],
                                        ];
                                    @endphp
                                    <div class="grid gap-3 md:grid-cols-3">
                                        @foreach($dmModes as $mode)
                                            <button
                                                type="button"
                                                wire:click="$set('comment_dm_mode', '{{ $mode['key'] }}')"
                                                class="text-left p-4 rounded-xl border-2 transition-colors
                                                    {{ $comment_dm_mode === $mode['key']
                                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30'
                                                        : 'border-violet-300 hover:border-violet-400' }}"
                                            >
                                                <div class="flex items-start justify-between gap-2 mb-1">
                                                    <span class="font-medium !text-zinc-900 dark:!text-zinc-900">{{ $mode['title'] }}</span>
                                                    @if($comment_dm_mode === $mode['key'])
                                                        <flux:icon name="check-circle" class="w-5 h-5 text-blue-500 shrink-0" />
                                                    @endif
                                                </div>
                                                <p class="text-xs text-zinc-900 leading-relaxed">{{ $mode['desc'] }}</p>
                                            </button>
                                        @endforeach
                                    </div>

                                    @if($comment_dm_mode === \App\Models\AiConfig::COMMENT_DM_ON_PURCHASE_INTENT)
                                        <div class="mt-4">
                                            <flux:text size="sm" class="mb-2 text-zinc-900">{{ __('Purchase-intent keywords (comment must contain any of these to trigger a DM):') }}</flux:text>
                                            <div class="flex flex-wrap gap-2 mb-3">
                                                @foreach($comment_dm_keywords as $index => $kw)
                                                    <div wire:key="cdk-{{ $index }}" class="kw-chip flex items-center gap-1 rounded-full bg-zinc-100 dark:bg-zinc-800 border border-white pl-3 pr-1 py-1">
                                                        <flux:input wire:model.blur="comment_dm_keywords.{{ $index }}" size="xs" class="!w-32 !bg-transparent !p-0 !text-sm" />
                                                        <button type="button" wire:click="removeCommentDmKeyword({{ $index }})" class="w-5 h-5 flex items-center justify-center rounded-full text-white hover:bg-white/20">
                                                            <flux:icon name="x-mark" class="w-3 h-3" />
                                                        </button>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <flux:button wire:click="addCommentDmKeyword" type="button" variant="outline" size="sm" icon="plus">
                                                {{ __('Add keyword') }}
                                            </flux:button>
                                        </div>
                                    @endif
                                </section>

                                {{-- Reply persona nudge --}}
                                <section>
                                    <flux:heading size="lg" class="mb-1 text-zinc-900">{{ __('Reply style rules') }}</flux:heading>
                                    <flux:text size="sm" class="mb-4 text-zinc-900">{{ __('Optional. Custom rules for how the AI writes replies specifically on comments — shorter and more careful than DMs, since comments are public.') }}</flux:text>
                                    <div x-data="{ count: $wire.entangle('comment_reply_instructions').length ?? 0 }" x-init="$watch('$wire.comment_reply_instructions', v => count = (v ?? '').length)">
                                        <flux:textarea
                                            wire:model="comment_reply_instructions"
                                            placeholder="{{ __('e.g. Keep replies to 1–2 sentences. Always thank the commenter first. Never quote prices — invite them to DM.') }}"
                                            rows="3"
                                            maxlength="500"
                                            class="text-zinc-900"
                                        />
                                        <div class="flex justify-end mt-1">
                                            <p class="text-xs text-zinc-900" :class="count > 450 ? 'text-amber-500' : ''" x-text="count + ' / 500'"></p>
                                        </div>
                                    </div>
                                </section>

                                {{-- Scope --}}
                                <section>
                                    <flux:heading size="lg" class="mb-1 text-zinc-900">{{ __('Which posts does this apply to?') }}</flux:heading>
                                    <flux:text size="sm" class="mb-4 text-zinc-900">{{ __('Choose whether the AI acts on comments from every post, or only from posts made after you enable this feature.') }}</flux:text>

                                    @php
                                        $scopes = [
                                            ['key' => \App\Models\AiConfig::COMMENT_SCOPE_FUTURE_ONLY, 'title' => __('Future posts only'), 'desc' => __('Recommended: safer for existing viral posts with lots of comments.')],
                                            ['key' => \App\Models\AiConfig::COMMENT_SCOPE_ALL_POSTS,   'title' => __('All posts'),          'desc' => __('Includes historical posts. Use with care on accounts with a big backlog.')],
                                        ];
                                    @endphp
                                    <div class="grid gap-3 md:grid-cols-2">
                                        @foreach($scopes as $scope)
                                            <button
                                                type="button"
                                                wire:click="$set('comment_scope', '{{ $scope['key'] }}')"
                                                class="text-left p-4 rounded-xl border-2 transition-colors
                                                    {{ $comment_scope === $scope['key']
                                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30'
                                                        : 'border-violet-300 hover:border-violet-400' }}"
                                            >
                                                <div class="flex items-start justify-between gap-2 mb-1">
                                                    <span class="font-medium !text-zinc-900 dark:!text-zinc-900">{{ $scope['title'] }}</span>
                                                    @if($comment_scope === $scope['key'])
                                                        <flux:icon name="check-circle" class="w-5 h-5 text-blue-500 shrink-0" />
                                                    @endif
                                                </div>
                                                <p class="text-xs text-zinc-900 leading-relaxed">{{ $scope['desc'] }}</p>
                                            </button>
                                        @endforeach
                                    </div>
                                </section>

                                {{-- Rate limit --}}
                                <section>
                                    <flux:heading size="lg" class="mb-1 text-zinc-900">{{ __('Per-post daily reply cap') }}</flux:heading>
                                    <flux:text size="sm" class="mb-4 text-zinc-900">{{ __('The most AI replies allowed on a single post in any 24-hour window. Prevents a viral post from draining your AI budget. Recommended: 20.') }}</flux:text>
                                    <div class="max-w-xs">
                                        <flux:input
                                            wire:model="comment_max_replies_per_post_per_day"
                                            type="number"
                                            label="{{ __('Cap (1-100 replies/post/day)') }}"
                                            min="{{ \App\Models\AiConfig::COMMENT_MAX_REPLIES_PER_POST_MIN }}"
                                            max="{{ \App\Models\AiConfig::COMMENT_MAX_REPLIES_PER_POST_MAX }}"
                                            class="text-zinc-900"
                                        />
                                        @error('comment_max_replies_per_post_per_day') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </section>
                            @endif
                        @endif {{-- /Comments tab --}}

                        {{-- Save --}}
                        <div class="flex items-center gap-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                                {{ $hasConfig ? __('Save Changes') : __('Create AI Config') }}
                            </flux:button>

                            <div wire:loading wire:target="saveConfig">
                                <flux:text size="sm" class="text-zinc-500">{{ __('Saving...') }}</flux:text>
                            </div>

                            <x-action-message on="config-saved" class="text-green-600">
                                {{ __('Saved successfully.') }}
                            </x-action-message>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>
