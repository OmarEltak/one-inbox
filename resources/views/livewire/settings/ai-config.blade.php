<div class="p-6 w-full">
    <flux:heading size="xl" class="mb-2">AI Configuration</flux:heading>
    <flux:text class="mb-6">Configure AI behavior per connected page. The AI uses this info to respond to customers.</flux:text>

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
                                    ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900'
                                    : 'hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300' }}"
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
                    <form wire:submit="saveConfig" class="space-y-8">
                        {{-- Active Toggle --}}
                        <div class="rounded-xl border-2 {{ $is_active ? 'border-green-500 bg-green-50 dark:bg-green-900/10' : 'border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800/50' }} p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <flux:heading size="sm">
                                        AI for this page:
                                        <span class="{{ $is_active ? 'text-green-600' : 'text-zinc-500' }}">
                                            {{ $is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </flux:heading>
                                    <flux:text size="sm" class="mt-0.5">
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

                        {{-- Section: Business Info --}}
                        <section>
                            <flux:heading size="lg" class="mb-1">Business Info</flux:heading>
                            <flux:text size="sm" class="mb-4">Tell the AI about your business so it can answer customer questions accurately.</flux:text>

                            <div x-data="{ count: $wire.entangle('business_description').length ?? 0 }" x-init="$watch('$wire.business_description', v => count = (v ?? '').length)">
                                <flux:textarea
                                    wire:model="business_description"
                                    label="Business Description"
                                    placeholder="e.g. We are a boutique flower shop in downtown Beirut specializing in fresh arrangements, wedding decorations, and same-day delivery..."
                                    rows="3"
                                    maxlength="1500"
                                />
                                <div class="flex justify-between mt-1">
                                    @error('business_description') <p class="text-red-500 text-sm">{{ $message }}</p> @else <span></span> @enderror
                                    <p class="text-xs text-zinc-400" :class="count > 1400 ? 'text-amber-500' : ''" x-text="count + ' / 1500'"></p>
                                </div>
                            </div>

                            <flux:textarea
                                wire:model="additional_instructions"
                                label="Additional Instructions"
                                placeholder="e.g. Always greet in Arabic first. Never offer discounts above 10%. If someone asks about wholesale, ask for their business name and forward to the team."
                                rows="3"
                                class="mt-4"
                            />
                            <flux:text size="sm" class="mt-1 text-zinc-500">Custom rules the AI must always follow when responding on this page.</flux:text>
                        </section>

                        {{-- Section: Products --}}
                        <section>
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <flux:heading size="lg" class="mb-1">Products / Services</flux:heading>
                                    <flux:text size="sm">List what you sell so the AI can recommend and describe products.</flux:text>
                                </div>
                                <flux:button size="sm" variant="ghost" wire:click="addProduct" type="button" icon="plus">
                                    Add
                                </flux:button>
                            </div>

                            @forelse($product_catalog as $i => $product)
                                <div class="flex gap-3 mb-3 items-start" wire:key="product-{{ $i }}">
                                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <flux:input wire:model="product_catalog.{{ $i }}.name" placeholder="Product name" size="sm" />
                                        <flux:input wire:model="product_catalog.{{ $i }}.description" placeholder="Short description" size="sm" />
                                        <flux:input wire:model="product_catalog.{{ $i }}.price" placeholder="Price (e.g. $25)" size="sm" />
                                    </div>
                                    <flux:button size="sm" variant="ghost" wire:click="removeProduct({{ $i }})" type="button" icon="x-mark" class="mt-0.5" />
                                </div>
                            @empty
                                <div class="text-sm text-zinc-400 dark:text-zinc-500 py-2">No products added yet. Click "Add" to get started.</div>
                            @endforelse
                        </section>

                        {{-- Section: Pricing --}}
                        <section>
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <flux:heading size="lg" class="mb-1">Pricing</flux:heading>
                                    <flux:text size="sm">Add pricing details the AI should know about.</flux:text>
                                </div>
                                <flux:button size="sm" variant="ghost" wire:click="addPricing" type="button" icon="plus">
                                    Add
                                </flux:button>
                            </div>

                            @forelse($pricing_info as $i => $pricing)
                                <div class="flex gap-3 mb-3 items-start" wire:key="pricing-{{ $i }}">
                                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <flux:input wire:model="pricing_info.{{ $i }}.item" placeholder="Item / Package" size="sm" />
                                        <flux:input wire:model="pricing_info.{{ $i }}.price" placeholder="Price" size="sm" />
                                        <flux:input wire:model="pricing_info.{{ $i }}.notes" placeholder="Notes (optional)" size="sm" />
                                    </div>
                                    <flux:button size="sm" variant="ghost" wire:click="removePricing({{ $i }})" type="button" icon="x-mark" class="mt-0.5" />
                                </div>
                            @empty
                                <div class="text-sm text-zinc-400 dark:text-zinc-500 py-2">No pricing entries yet.</div>
                            @endforelse
                        </section>

                        {{-- Section: FAQ --}}
                        <section>
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <flux:heading size="lg" class="mb-1">FAQ</flux:heading>
                                    <flux:text size="sm">Common questions & answers the AI should know.</flux:text>
                                </div>
                                <flux:button size="sm" variant="ghost" wire:click="addFaq" type="button" icon="plus">
                                    Add
                                </flux:button>
                            </div>

                            @forelse($faq as $i => $item)
                                <div class="flex gap-3 mb-3 items-start" wire:key="faq-{{ $i }}">
                                    <div class="flex-1 space-y-2">
                                        <flux:input wire:model="faq.{{ $i }}.question" placeholder="Question" size="sm" />
                                        <flux:textarea wire:model="faq.{{ $i }}.answer" placeholder="Answer" rows="2" resize="none" />
                                    </div>
                                    <flux:button size="sm" variant="ghost" wire:click="removeFaq({{ $i }})" type="button" icon="x-mark" class="mt-0.5" />
                                </div>
                            @empty
                                <div class="text-sm text-zinc-400 dark:text-zinc-500 py-2">No FAQ entries yet.</div>
                            @endforelse
                        </section>

                        {{-- Section: Tone & Language --}}
                        <section>
                            <flux:heading size="lg" class="mb-1">Tone & Language</flux:heading>
                            <flux:text size="sm" class="mb-4">Control how the AI communicates with your customers.</flux:text>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <flux:select wire:model="tone" label="Tone">
                                    <flux:select.option value="friendly">Friendly</flux:select.option>
                                    <flux:select.option value="professional">Professional</flux:select.option>
                                    <flux:select.option value="casual">Casual</flux:select.option>
                                    <flux:select.option value="formal">Formal</flux:select.option>
                                </flux:select>

                                <flux:select wire:model="language" label="Language">
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
                            <flux:heading size="lg" class="mb-1">Response Timing</flux:heading>
                            <flux:text size="sm" class="mb-4">Set a random delay range so responses feel natural, not instant.</flux:text>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <flux:input
                                    wire:model="response_delay_min_seconds"
                                    type="number"
                                    label="Minimum delay (seconds)"
                                    min="10"
                                    max="300"
                                />
                                <flux:input
                                    wire:model="response_delay_max_seconds"
                                    type="number"
                                    label="Maximum delay (seconds)"
                                    min="10"
                                    max="600"
                                />
                            </div>
                            @error('response_delay_min_seconds') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            @error('response_delay_max_seconds') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </section>

                        {{-- Section: Working Hours --}}
                        <section>
                            <flux:heading size="lg" class="mb-1">Working Hours</flux:heading>
                            <flux:text size="sm" class="mb-4">AI will only respond during these hours. Outside of them, messages wait for humans.</flux:text>

                            <div class="mb-4">
                                <flux:select wire:model="timezone" label="Timezone">
                                    @foreach(['UTC', 'Asia/Beirut', 'Asia/Dubai', 'Asia/Riyadh', 'Europe/London', 'Europe/Paris', 'America/New_York', 'America/Chicago', 'America/Los_Angeles'] as $tz)
                                        <flux:select.option value="{{ $tz }}">{{ $tz }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <div class="space-y-2">
                                @foreach(['monday' => 'Mon', 'tuesday' => 'Tue', 'wednesday' => 'Wed', 'thursday' => 'Thu', 'friday' => 'Fri', 'saturday' => 'Sat', 'sunday' => 'Sun'] as $day => $label)
                                    <div class="flex items-center gap-3">
                                        <div class="w-10">
                                            <flux:switch wire:model.live="working_hours.{{ $day }}.enabled" />
                                        </div>
                                        <span class="w-10 text-sm font-medium {{ ($working_hours[$day]['enabled'] ?? false) ? 'text-zinc-900 dark:text-zinc-100' : 'text-zinc-400' }}">{{ $label }}</span>
                                        @if($working_hours[$day]['enabled'] ?? false)
                                            <flux:input wire:model="working_hours.{{ $day }}.start" type="time" size="sm" class="w-32" />
                                            <span class="text-zinc-400">to</span>
                                            <flux:input wire:model="working_hours.{{ $day }}.end" type="time" size="sm" class="w-32" />
                                        @else
                                            <span class="text-sm text-zinc-400">Closed</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </section>

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
