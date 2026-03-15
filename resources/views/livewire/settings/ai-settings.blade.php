<div class="p-6 max-w-2xl">
    <flux:heading size="xl" class="mb-2">AI Settings</flux:heading>
    <flux:text class="mb-8">Control the AI sales responder for your entire team.</flux:text>

    {{-- Kill Switch --}}
    <div class="rounded-xl border-2 {{ $aiEnabled ? 'border-green-500 bg-green-50 dark:bg-green-900/10' : 'border-red-500 bg-red-50 dark:bg-red-900/10' }} p-6 mb-8">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="lg">
                    AI Auto-Response
                    <span class="ml-2 {{ $aiEnabled ? 'text-green-600' : 'text-red-600' }}">
                        {{ $aiEnabled ? 'ON' : 'OFF' }}
                    </span>
                </flux:heading>
                <flux:text class="mt-1">
                    @if($aiEnabled)
                        AI is actively responding to all incoming messages across all platforms.
                    @else
                        AI is paused. Messages are received but only humans can respond. Lead scoring still runs in the background.
                    @endif
                </flux:text>
                @if(!$aiEnabled && auth()->user()->currentTeam->ai_disabled_at)
                    <flux:text size="sm" class="mt-2 text-red-500">
                        Disabled {{ auth()->user()->currentTeam->ai_disabled_at->diffForHumans() }}
                    </flux:text>
                @endif
            </div>
            <flux:button
                wire:click="toggleAi"
                wire:loading.attr="disabled"
                :variant="$aiEnabled ? 'danger' : 'primary'"
                size="sm"
            >
                {{ $aiEnabled ? 'Turn OFF' : 'Turn ON' }}
            </flux:button>
        </div>
    </div>

    {{-- Info --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 space-y-3">
        <flux:heading size="sm">How it works</flux:heading>
        <div class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
            <p><strong>When AI is ON:</strong> The AI automatically responds to all new messages on all connected pages with a natural delay (30s - 3min).</p>
            <p><strong>When AI is OFF:</strong> Messages still arrive in your inbox normally. Your team responds manually. Lead scoring and analysis continue running in the background.</p>
            <p><strong>Emergency:</strong> If the AI behaves unexpectedly, turn it off here. Your agents can report issues to you, and you can contact the developer. Once the issue is resolved, turn it back on.</p>
        </div>
    </div>
</div>
