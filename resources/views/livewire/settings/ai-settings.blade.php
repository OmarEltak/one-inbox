<div class="p-6 max-w-3xl" x-data="{ tab: 'general' }">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-zinc-900">{{ __('AI Settings') }}</h1>
        <p class="mt-1 text-sm text-white/40">{{ __('Control the AI sales responder for your entire team.') }}</p>
    </div>

    {{-- Tab Bar --}}
    <div class="flex gap-0 mb-6" style="border-bottom: 1px solid rgba(255,255,255,0.07);">
        @foreach(['general' => __('General'), 'quick-replies' => __('Quick Replies'), 'rules' => __('Rules & Limits'), 'advanced' => __('Advanced')] as $key => $label)
            <button
                @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}'
                    ? 'border-b-2 text-[#C27AFF]'
                    : 'text-white/35 hover:text-white/60'"
                :style="tab === '{{ $key }}' ? 'border-color: #7C3AED;' : ''"
                class="px-4 py-2.5 text-sm font-medium transition-colors cursor-pointer -mb-px"
            >{{ $label }}</button>
        @endforeach
    </div>

    {{-- General Tab --}}
    <div x-show="tab === 'general'" class="space-y-5">

        {{-- AI Kill Switch --}}
        <div class="rounded-2xl p-6 {{ $aiEnabled ? '' : '' }}"
             style="{{ $aiEnabled ? 'background: rgba(0,212,146,0.05); border: 2px solid rgba(0,212,146,0.3);' : 'background: rgba(244,63,94,0.05); border: 2px solid rgba(244,63,94,0.25);' }}">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-white/80">
                        {{ __('AI Auto-Response') }}
                        <span class="ml-2 text-base {{ $aiEnabled ? 'text-green-400' : 'text-red-400' }}">
                            {{ $aiEnabled ? __('ON') : __('OFF') }}
                        </span>
                    </h3>
                    <p class="mt-1 text-sm text-white/40">
                        @if($aiEnabled)
                            {{ __('AI is actively responding to all incoming messages across all platforms.') }}
                        @else
                            {{ __('AI is paused. Messages are received but only humans can respond. Lead scoring still runs.') }}
                        @endif
                    </p>
                    @if(!$aiEnabled && auth()->user()->currentTeam->ai_disabled_at)
                        <p class="mt-2 text-sm text-red-400">
                            {{ __('Disabled') }} {{ auth()->user()->currentTeam->ai_disabled_at->diffForHumans() }}
                        </p>
                    @endif
                </div>
                <flux:button
                    wire:click="toggleAi"
                    wire:loading.attr="disabled"
                    :variant="$aiEnabled ? 'danger' : 'primary'"
                    size="sm"
                >
                    {{ $aiEnabled ? __('Turn OFF') : __('Turn ON') }}
                </flux:button>
            </div>
        </div>

        {{-- Assistant Identity --}}
        <div class="aio-card rounded-2xl p-5">
            <h3 class="text-sm font-semibold text-white/80 mb-4">{{ __('Assistant Identity') }}</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">{{ __('Assistant Name') }}</label>
                    <div class="w-full rounded-xl text-sm text-white/40 px-3 py-2">
                        {{ __('AI Assistant') }}
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">{{ __('AI Model') }}</label>
                    <div class="w-full rounded-xl text-sm text-white/40 px-3 py-2">
                        {{ __('AI Assistant') }}
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">{{ __('Language') }}</label>
                    <div class="w-full rounded-xl text-sm text-white/40 px-3 py-2">
                        {{ __('Auto-detect') }}
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">{{ __('Tone') }}</label>
                    <div class="w-full rounded-xl text-sm text-white/40 px-3 py-2">
                        {{ __('Professional & Friendly') }}
                    </div>
                </div>
            </div>
            <p class="mt-3 text-xs text-white/40">{{ __('Configure detailed AI behavior in') }} <a href="{{ route('settings.ai.config') }}" wire:navigate class="text-[#3b82f6] hover:underline">{{ __('AI Config') }}</a>.</p>
        </div>

        {{-- How it works --}}
        <div class="aio-card rounded-2xl p-5">
            <h3 class="text-sm font-semibold text-white/80 mb-3">{{ __('How it works') }}</h3>
            <div class="space-y-2 text-sm text-white/40">
                <p><span class="text-green-400 font-medium">{{ __('When AI is ON:') }}</span> {{ __('The AI automatically responds to all new messages on all connected pages with a natural delay (30s - 3min).') }}</p>
                <p><span class="text-red-400 font-medium">{{ __('When AI is OFF:') }}</span> {{ __('Messages still arrive in your inbox normally. Your team responds manually. Lead scoring continues running.') }}</p>
                <p><span class="text-yellow-400 font-medium">{{ __('Emergency:') }}</span> {{ __('If the AI behaves unexpectedly, turn it off here immediately.') }}</p>
            </div>
        </div>
    </div>

    {{-- Quick Replies Tab --}}
    <div x-show="tab === 'quick-replies'" class="space-y-4">
        @livewire('settings.quick-replies')
    </div>

    {{-- Rules & Limits Tab --}}
    <div x-show="tab === 'rules'" class="space-y-4">
        <div class="aio-card rounded-2xl p-5">
            <h3 class="text-sm font-semibold text-white/80 mb-2">{{ __('Rules & Limits') }}</h3>
            <p class="text-sm text-white/40">{{ __('Configure when the AI should respond, daily limits, and escalation rules.') }}</p>
            <div class="mt-4 rounded-lg border border-[#1e2536] bg-[#0d1117] p-4 text-xs text-white/40">
                {{ __('Advanced rule configuration is available in') }} <a href="{{ route('settings.ai.config') }}" wire:navigate class="text-[#3b82f6] hover:underline">{{ __('AI Config') }}</a>.
            </div>
        </div>
    </div>

    {{-- Advanced Tab --}}
    <div x-show="tab === 'advanced'" class="space-y-4">
        <div class="aio-card rounded-2xl p-5">
            <h3 class="text-sm font-semibold text-white/80 mb-2">{{ __('Advanced Settings') }}</h3>
            <p class="text-sm text-white/40">{{ __('Custom prompts, model parameters, and fine-tuning options.') }}</p>
            <div class="mt-4">
                <a href="{{ route('settings.ai.config') }}" wire:navigate>
                    <flux:button variant="primary" size="sm" icon="cog-6-tooth">{{ __('Open AI Config') }}</flux:button>
                </a>
            </div>
        </div>
    </div>
</div>
