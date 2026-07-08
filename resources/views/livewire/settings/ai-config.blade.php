<div class="p-6 w-full">
    <flux:heading size="xl" class="mb-2 !text-zinc-900 dark:!text-zinc-900">AI Configuration</flux:heading>
    <flux:text class="mb-6 !text-zinc-900 dark:!text-zinc-900">Configure AI behavior per connected page. The AI uses this info to respond to customers.</flux:text>

    @if($pages->isEmpty())
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-8 text-center">
            <flux:icon name="link-slash" class="mx-auto mb-3 text-zinc-400" variant="outline" />
            <flux:heading size="lg">No connected pages</flux:heading>
            <flux:text class="mt-2">Connect a Facebook, Instagram, WhatsApp, or Telegram page first.</flux:text>
            <flux:button :href="route('connections.index')" variant="primary" class="mt-4" wire:navigate>
                Go to Connections
            </flux:button>
        </div>
    @else
        <div class="grid gap-6" style="grid-template-columns: 16rem 1fr;">
            {{-- Left: Page Selector --}}
            <div>
                <flux:heading size="sm" class="mb-3">Pages</flux:heading>
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
                        <flux:text>Select a page from the left to configure its AI settings.</flux:text>
                    </div>
                @else
                    {{-- Force black text on all Flux labels and controls in dark mode --}}
                    <style>
                        [data-flux-label] { color: rgb(9,9,11) !important; }
                        [data-flux-control] { color: rgb(9,9,11) !important; }
                        .kw-chip input[data-flux-control] { color: white !important; border-color: white !important; }
                    </style>
                    <form wire:submit="saveConfig" class="space-y-8 [&_input]:!border-violet-400 [&_textarea]:!border-violet-400 [&_select]:!border-violet-400">
                        {{-- Active Toggle --}}
                        <div class="rounded-xl border-2 {{ $is_active ? 'border-green-500 bg-green-50 dark:bg-green-900/10' : 'border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800/50' }} p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <flux:heading size="sm" class="text-zinc-900">
                                        AI for this page:
                                        <span class="{{ $is_active ? 'text-green-600' : 'text-zinc-500' }}">
                                            {{ $is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </flux:heading>
                                    <flux:text size="sm" class="mt-0.5 text-zinc-900">
                                        @if(!$hasConfig)
                                            Fill in the form below and save to enable AI for this page.
                                        @else
                                            {{ $is_active ? 'AI will respond to messages on this page.' : 'AI is paused for this page.' }}
                                        @endif
                                    </flux:text>
                                </div>
                                <flux:switch wire:model.live="is_active" />
                            </div>
                        </div>

                        {{-- Tab strip. Splits the config into 4 focused areas so the form
                             doesn't feel like a wall of settings. --}}
                        @php
                            $tabs = [
                                'sales_goal' => ['label' => 'Sales Goal',  'icon' => 'flag'],
                                'knowledge'  => ['label' => 'Knowledge',   'icon' => 'book-open'],
                                'behavior'   => ['label' => 'Behavior',    'icon' => 'sparkles'],
                                'handoff'    => ['label' => 'Handoff',     'icon' => 'user-group'],
                            ];
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
                                    ['key' => 'info_only',    'title' => 'Info only',           'desc' => 'AI answers questions but never asks for data. Escalate only when a customer explicitly asks for a human.'],
                                    ['key' => 'capture_data', 'title' => 'Capture specific data','desc' => 'AI naturally collects the fields you select (email, phone, address, etc.), then hands off.'],
                                    ['key' => 'booking',      'title' => 'Booking',              'desc' => 'AI qualifies the customer and captures name, phone, and preferred slot.'],
                                    ['key' => 'ecommerce',    'title' => 'E-commerce order',    'desc' => 'AI takes orders end-to-end: product, quantity, shipping address, phone.'],
                                    ['key' => 'custom',       'title' => 'Custom',              'desc' => 'Define your own fields and escalation triggers. Full control.'],
                                ];
                            @endphp
                            <section>
                                <flux:heading size="lg" class="mb-1 text-zinc-900">What is the goal of this conversation?</flux:heading>
                                <flux:text size="sm" class="mb-4 text-zinc-900">Pick the preset that matches your business. The AI is told about the goal in every reply and pushes toward it naturally.</flux:text>

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
                                    <flux:heading size="lg" class="mb-1">Data to capture</flux:heading>
                                    <flux:text size="sm" class="mb-4">These fields will be extracted from the customer's messages. When all are captured, the AI stops and the conversation is marked completed.</flux:text>

                                    <div class="space-y-2">
                                        @forelse($required_capture_fields as $index => $field)
                                            <div class="flex items-center gap-2">
                                                <flux:input wire:model="required_capture_fields.{{ $index }}.label" placeholder="Label (e.g. Email address)" class="flex-1 text-zinc-900" />
                                                <flux:input wire:model="required_capture_fields.{{ $index }}.key" placeholder="key (e.g. email)" class="w-40 text-zinc-900" />
                                                <flux:select wire:model="required_capture_fields.{{ $index }}.type" class="w-32 text-zinc-900">
                                                    <flux:select.option value="text">Text</flux:select.option>
                                                    <flux:select.option value="email">Email</flux:select.option>
                                                    <flux:select.option value="phone">Phone</flux:select.option>
                                                    <flux:select.option value="address">Address</flux:select.option>
                                                </flux:select>
                                                <flux:button wire:click="removeCaptureField({{ $index }})" type="button" variant="ghost" size="sm" icon="x-mark" square />
                                            </div>
                                        @empty
                                            <flux:text size="sm" class="text-zinc-500 italic">No fields configured yet.</flux:text>
                                        @endforelse
                                    </div>

                                    <flux:button wire:click="addCaptureField" type="button" variant="outline" size="sm" icon="plus" class="mt-3">
                                        Add field
                                    </flux:button>
                                </section>
                            @endif
                        @endif

                        {{-- Tab: Knowledge --}}
                        @if($activeTab === 'knowledge')
                        {{-- Section: Business Info --}}
                        <section>
                            <flux:heading size="lg" class="mb-1 text-zinc-900">Business Info</flux:heading>
                            <flux:text size="sm" class="mb-4 text-zinc-900">Tell the AI about your business so it can answer customer questions accurately.</flux:text>

                            <div x-data="{ count: $wire.entangle('business_description').length ?? 0 }" x-init="$watch('$wire.business_description', v => count = (v ?? '').length)">
                                <flux:textarea
                                    wire:model="business_description"
                                    label="Business Description"
                                    placeholder="e.g. We are a boutique flower shop in downtown Beirut specializing in fresh arrangements, wedding decorations, and same-day delivery..."
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
                                label="Additional Instructions"
                                placeholder="e.g. Always greet in Arabic first. Never offer discounts above 10%. If someone asks about wholesale, ask for their business name and forward to the team."
                                rows="3"
                                class="mt-4 !text-zinc-900 dark:!text-zinc-900 [&_label]:!text-zinc-900 dark:[&_label]:!text-zinc-900"
                            />
                            <flux:text size="sm" class="mt-1 text-zinc-900">Custom rules the AI must always follow when responding on this page.</flux:text>
                        </section>

                        {{-- Section: Products --}}
                        <section>
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <flux:heading size="lg" class="mb-1 text-zinc-900">Products / Services</flux:heading>
                                    <flux:text size="sm" class="text-zinc-900">List what you sell so the AI can recommend and describe products.</flux:text>
                                </div>
                                <flux:button size="sm" variant="ghost" wire:click="addProduct" type="button" icon="plus" class="!bg-violet-600 !text-white hover:!bg-violet-700">
                                    Add
                                </flux:button>
                            </div>

                            @forelse($product_catalog as $i => $product)
                                <div class="flex gap-3 mb-3 items-start" wire:key="product-{{ $i }}">
                                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <flux:input wire:model="product_catalog.{{ $i }}.name" placeholder="Product name" size="sm" class="text-zinc-900" />
                                        <flux:input wire:model="product_catalog.{{ $i }}.description" placeholder="Short description" size="sm" class="text-zinc-900" />
                                        <flux:input wire:model="product_catalog.{{ $i }}.price" placeholder="Price (e.g. $25)" size="sm" class="text-zinc-900" />
                                    </div>
                                    <flux:button size="sm" variant="ghost" wire:click="removeProduct({{ $i }})" type="button" icon="x-mark" class="mt-0.5" />
                                </div>
                            @empty
                                <div class="text-sm text-zinc-900 py-2">No products added yet. Click "Add" to get started.</div>
                            @endforelse
                        </section>

                        {{-- Section: Pricing --}}
                        <section>
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <flux:heading size="lg" class="mb-1 text-zinc-900">Pricing</flux:heading>
                                    <flux:text size="sm" class="text-zinc-900">Add pricing details the AI should know about.</flux:text>
                                </div>
                                <flux:button size="sm" variant="ghost" wire:click="addPricing" type="button" icon="plus" class="!bg-violet-600 !text-white hover:!bg-violet-700">
                                    Add
                                </flux:button>
                            </div>

                            @forelse($pricing_info as $i => $pricing)
                                <div class="flex gap-3 mb-3 items-start" wire:key="pricing-{{ $i }}">
                                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <flux:input wire:model="pricing_info.{{ $i }}.item" placeholder="Item / Package" size="sm" class="text-zinc-900" />
                                        <flux:input wire:model="pricing_info.{{ $i }}.price" placeholder="Price" size="sm" class="text-zinc-900" />
                                        <flux:input wire:model="pricing_info.{{ $i }}.notes" placeholder="Notes (optional)" size="sm" class="text-zinc-900" />
                                    </div>
                                    <flux:button size="sm" variant="ghost" wire:click="removePricing({{ $i }})" type="button" icon="x-mark" class="mt-0.5" />
                                </div>
                            @empty
                                <div class="text-sm text-zinc-900 py-2">No pricing entries yet.</div>
                            @endforelse
                        </section>

                        {{-- Section: FAQ --}}
                        <section>
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <flux:heading size="lg" class="mb-1 text-zinc-900">FAQ</flux:heading>
                                    <flux:text size="sm" class="text-zinc-900">Common questions & answers the AI should know.</flux:text>
                                </div>
                                <flux:button size="sm" variant="ghost" wire:click="addFaq" type="button" icon="plus" class="!bg-violet-600 !text-white hover:!bg-violet-700">
                                    Add
                                </flux:button>
                            </div>

                            @forelse($faq as $i => $item)
                                <div class="flex gap-3 mb-3 items-start" wire:key="faq-{{ $i }}">
                                    <div class="flex-1 space-y-2">
                                        <flux:input wire:model="faq.{{ $i }}.question" placeholder="Question" size="sm" class="text-zinc-900" />
                                        <flux:textarea wire:model="faq.{{ $i }}.answer" placeholder="Answer" rows="2" resize="none" class="text-zinc-900" />
                                    </div>
                                    <flux:button size="sm" variant="ghost" wire:click="removeFaq({{ $i }})" type="button" icon="x-mark" class="mt-0.5" />
                                </div>
                            @empty
                                <div class="text-sm text-zinc-900 py-2">No FAQ entries yet.</div>
                            @endforelse
                        </section>
                        @endif {{-- /Knowledge tab --}}

                        {{-- Tab: Behavior --}}
                        @if($activeTab === 'behavior')
                        {{-- Section: Tone & Language --}}
                        <section>
                            <flux:heading size="lg" class="mb-1 text-zinc-900">Tone & Language</flux:heading>
                            <flux:text size="sm" class="mb-4 text-zinc-900">Control how the AI communicates with your customers.</flux:text>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <flux:select wire:model="tone" label="Tone" class="text-zinc-900">
                                    <flux:select.option value="friendly">Friendly</flux:select.option>
                                    <flux:select.option value="professional">Professional</flux:select.option>
                                    <flux:select.option value="casual">Casual</flux:select.option>
                                    <flux:select.option value="formal">Formal</flux:select.option>
                                </flux:select>

                                <flux:select wire:model="language" label="Language" class="text-zinc-900">
                                    <flux:select.option value="en">English</flux:select.option>
                                    <flux:select.option value="ar">Arabic</flux:select.option>
                                    <flux:select.option value="fr">French</flux:select.option>
                                    <flux:select.option value="es">Spanish</flux:select.option>
                                    <flux:select.option value="auto">Auto-detect</flux:select.option>
                                </flux:select>
                            </div>
                        </section>

                        {{-- Section: Response Timing --}}
                        <section>
                            <flux:heading size="lg" class="mb-1 text-zinc-900">Response Timing</flux:heading>
                            <flux:text size="sm" class="mb-4 text-zinc-900">Set a random delay range so responses feel natural, not instant.</flux:text>

                            <div class="mb-4 rounded-lg border border-emerald-500/30 bg-emerald-500/5 p-3.5 text-sm">
                                <div class="flex items-start gap-2.5">
                                    <flux:icon name="light-bulb" class="w-4 h-4 text-emerald-500 dark:text-emerald-400 mt-0.5 shrink-0" />
                                    <div class="text-zinc-900 leading-relaxed">
                                        <span class="font-medium">Longer delays save you AI tokens.</span>
                                        <span class="text-zinc-900">
                                            Customers often send multiple messages in a row (e.g. "Hi", "are you there", "I have a question"). With a longer delay, the AI waits for the full burst and replies to everything at once instead of firing a separate reply — and a separate token bill — for each message. We recommend at least <span class="font-medium">60 seconds</span> for cost efficiency.
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <flux:input
                                    wire:model="response_delay_min_seconds"
                                    type="number"
                                    label="Minimum delay (seconds)"
                                    min="10"
                                    max="300"
                                    class="!text-zinc-900 dark:!text-zinc-900 [&_label]:!text-zinc-900 dark:[&_label]:!text-zinc-900 [&_input]:!text-zinc-900 dark:[&_input]:!text-zinc-900"
                                />
                                <flux:input
                                    wire:model="response_delay_max_seconds"
                                    type="number"
                                    label="Maximum delay (seconds)"
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
                            <flux:heading size="lg" class="mb-1 text-zinc-900">Working Hours</flux:heading>
                            <flux:text size="sm" class="mb-4 text-zinc-900">AI will only respond during these hours. Outside of them, messages wait for humans.</flux:text>

                            <div class="mb-4 flex items-center justify-between rounded-lg border border-violet-300 p-3">
                                <div>
                                    <div class="text-sm font-medium text-zinc-900">Always on (24/7)</div>
                                    <div class="text-xs text-zinc-900">When on, the AI replies any time of day — schedule below is ignored.</div>
                                </div>
                                <flux:switch wire:model.live="is_24_7" class="[&:not([data-checked])]:!bg-zinc-500 dark:[&:not([data-checked])]:!bg-zinc-500" />
                            </div>

                            <div @class(['opacity-50 pointer-events-none' => $is_24_7])>
                                <div class="mb-4">
                                    <flux:select wire:model="timezone" label="Timezone" class="!text-zinc-900 dark:!text-zinc-900 [&_label]:!text-zinc-900 dark:[&_label]:!text-zinc-900 [&_select]:!text-zinc-900 dark:[&_select]:!text-zinc-900">
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
                                                <span class="text-sm font-medium !text-zinc-900 dark:!text-zinc-900">to</span>
                                                <flux:input wire:model="working_hours.{{ $day }}.end" type="time" size="sm" class="w-32 [&_input]:!text-zinc-900 dark:[&_input]:!text-zinc-900" />
                                            @else
                                                <span class="text-sm !text-zinc-900 dark:!text-zinc-900">Closed</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </section>
                        @endif {{-- /Behavior tab --}}

                        {{-- Tab: Handoff --}}
                        @if($activeTab === 'handoff')
                            {{-- Section: Escalation Keywords --}}
                            <section>
                                <flux:heading size="lg" class="mb-1 text-zinc-900">Escalation keywords</flux:heading>
                                <flux:text size="sm" class="mb-4 text-zinc-900">If the customer's message contains any of these words, AI stops and the conversation is marked <em>escalated</em> so a human can take over. Includes bilingual defaults (Arabic + English) from your Sales Goal preset.</flux:text>

                                <div class="flex flex-wrap gap-2 mb-3">
                                    @foreach($escalation_keywords as $index => $kw)
                                        <div wire:key="kw-{{ $index }}" class="kw-chip flex items-center gap-1 rounded-full bg-zinc-100 dark:bg-zinc-800 border border-white pl-3 pr-1 py-1">
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
                                    Add keyword
                                </flux:button>
                            </section>

                            {{-- Section: Per-contact AI reply cap --}}
                            <section>
                                <flux:heading size="lg" class="mb-1 text-zinc-900">Per-contact daily AI reply cap</flux:heading>
                                <flux:text size="sm" class="mb-4 text-zinc-900">Maximum AI replies a single contact can receive in 24 hours. Prevents one chatty (or abusive) customer from burning your AI budget. Once a contact hits this, further inbounds still land in your inbox but the AI stops replying to them until the window rolls over.</flux:text>

                                <div class="max-w-xs">
                                    <flux:input
                                        wire:model="contact_ai_reply_cap"
                                        type="number"
                                        label="Cap (5-50 replies/contact/day)"
                                        min="{{ \App\Models\AiConfig::CONTACT_CAP_MIN }}"
                                        max="{{ \App\Models\AiConfig::CONTACT_CAP_MAX }}"
                                        class="text-zinc-900"
                                    />
                                    @error('contact_ai_reply_cap') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                                </div>
                            </section>
                        @endif {{-- /Handoff tab --}}

                        {{-- Save --}}
                        <div class="flex items-center gap-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                                {{ $hasConfig ? 'Save Changes' : 'Create AI Config' }}
                            </flux:button>

                            <div wire:loading wire:target="saveConfig">
                                <flux:text size="sm" class="text-zinc-500">Saving...</flux:text>
                            </div>

                            <x-action-message on="config-saved" class="text-green-600">
                                Saved successfully.
                            </x-action-message>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>
