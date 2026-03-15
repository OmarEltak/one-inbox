<div class="max-w-6xl mx-auto p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Webhook Logs</flux:heading>
            <flux:text class="mt-1">Incoming webhook events from connected platforms.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:select wire:model.live="platform" class="w-40">
                <option value="">All Platforms</option>
                <option value="facebook">Facebook</option>
                <option value="instagram">Instagram</option>
                <option value="whatsapp">WhatsApp</option>
                <option value="telegram">Telegram</option>
            </flux:select>
            <flux:select wire:model.live="status" class="w-36">
                <option value="">All Status</option>
                <option value="processed">Processed</option>
                <option value="failed">Failed</option>
                <option value="pending">Pending</option>
            </flux:select>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Platform</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Event</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Received</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($logs as $log)
                    <tr class="bg-white hover:bg-zinc-50 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                        <td class="px-4 py-3">
                            <flux:badge size="sm" color="{{ match($log->platform) {
                                'facebook'  => 'blue',
                                'instagram' => 'pink',
                                'whatsapp'  => 'green',
                                'telegram'  => 'sky',
                                default     => 'zinc',
                            } }}">{{ ucfirst($log->platform) }}</flux:badge>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $log->event_type ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if($log->processed)
                                <flux:badge size="sm" color="green">Processed</flux:badge>
                            @elseif($log->error)
                                <flux:badge size="sm" color="red">Failed</flux:badge>
                            @else
                                <flux:badge size="sm" color="yellow">Pending</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 text-xs">{{ $log->created_at->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right">
                            <flux:button size="sm" variant="ghost" wire:click="viewLog({{ $log->id }})">View</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-zinc-400">No webhook logs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $logs->links() }}</div>

    {{-- Log Detail Modal --}}
    <flux:modal :show="$viewingId !== null" @close="$wire.closeLog()" class="w-full max-w-3xl">
        @if($this->log)
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">Webhook Event #{{ $this->log->id }}</flux:heading>
                    <flux:badge color="{{ $this->log->processed ? 'green' : ($this->log->error ? 'red' : 'yellow') }}">
                        {{ $this->log->processed ? 'Processed' : ($this->log->error ? 'Failed' : 'Pending') }}
                    </flux:badge>
                </div>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400">Platform:</span>
                        <span class="ml-2 font-medium">{{ ucfirst($this->log->platform) }}</span>
                    </div>
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400">Event Type:</span>
                        <span class="ml-2 font-mono text-xs">{{ $this->log->event_type ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400">Received:</span>
                        <span class="ml-2">{{ $this->log->created_at->format('Y-m-d H:i:s') }}</span>
                    </div>
                    @if($this->log->processed_at)
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400">Processed At:</span>
                            <span class="ml-2">{{ $this->log->processed_at->format('Y-m-d H:i:s') }}</span>
                        </div>
                    @endif
                </div>

                @if($this->log->error)
                    <div>
                        <flux:label>Error</flux:label>
                        <div class="mt-1 rounded-md bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">
                            {{ $this->log->error }}
                        </div>
                    </div>
                @endif

                <div>
                    <flux:label>Payload</flux:label>
                    <pre class="mt-1 max-h-96 overflow-auto rounded-md bg-zinc-100 p-3 text-xs text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">{{ json_encode($this->log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>

                <div class="flex justify-end pt-2">
                    <flux:button variant="ghost" wire:click="closeLog">Close</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
