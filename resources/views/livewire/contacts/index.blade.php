<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Contacts</flux:heading>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3 mb-6">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search contacts..." icon="magnifying-glass" class="max-w-xs" />

        <flux:select wire:model.live="statusFilter" size="sm" class="w-32">
            <option value="all">All Status</option>
            <option value="new">New</option>
            <option value="warm">Warm</option>
            <option value="hot">Hot</option>
            <option value="cold">Cold</option>
            <option value="converted">Converted</option>
            <option value="lost">Lost</option>
        </flux:select>
    </div>

    {{-- Contacts Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-zinc-600 dark:text-zinc-400">Contact</th>
                    <th class="text-left px-4 py-3 font-medium text-zinc-600 dark:text-zinc-400">Platforms</th>
                    <th class="text-center px-4 py-3 font-medium text-zinc-600 dark:text-zinc-400">Lead Score</th>
                    <th class="text-left px-4 py-3 font-medium text-zinc-600 dark:text-zinc-400">Status</th>
                    <th class="text-left px-4 py-3 font-medium text-zinc-600 dark:text-zinc-400">Last Interaction</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse($this->contacts as $contact)
                    <tr
                        wire:click="selectContact({{ $contact->id }})"
                        class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 cursor-pointer transition-colors"
                    >
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <flux:avatar :name="$contact->name ?? 'Unknown'" size="sm" />
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $contact->name ?? 'Unknown' }}</div>
                                    @if($contact->email)
                                        <div class="text-xs text-zinc-500">{{ $contact->email }}</div>
                                    @endif
                                    @if($contact->phone)
                                        <div class="text-xs text-zinc-500">{{ $contact->phone }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($contact->platforms as $platform)
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-white text-xs {{ match($platform->platform) {
                                        'facebook' => 'bg-blue-500',
                                        'instagram' => 'bg-pink-500',
                                        'whatsapp' => 'bg-green-500',
                                        'telegram' => 'bg-cyan-500',
                                        default => 'bg-gray-400',
                                    } }}" title="{{ $platform->platform_name }}">
                                        {{ strtoupper(substr($platform->platform, 0, 1)) }}
                                        @if($platform->platform_name)
                                            <span class="max-w-20 truncate">{{ $platform->platform_name }}</span>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full text-sm font-bold {{ match(true) {
                                $contact->lead_score >= 86 => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                $contact->lead_score >= 71 => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                                $contact->lead_score >= 51 => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                $contact->lead_score >= 26 => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                default => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                            } }}">
                                {{ $contact->lead_score }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge size="sm" :color="match($contact->lead_status) {
                                'hot' => 'orange',
                                'warm' => 'yellow',
                                'cold' => 'blue',
                                'converted' => 'green',
                                'lost' => 'red',
                                default => 'zinc',
                            }">
                                {{ ucfirst($contact->lead_status) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-zinc-500">
                            {{ $contact->last_interaction_at?->diffForHumans() ?? 'Never' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-zinc-500">
                            No contacts found. They'll appear here when people message your connected pages.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if(method_exists($this->contacts, 'links'))
        <div class="mt-4">
            {{ $this->contacts->links() }}
        </div>
    @endif

    {{-- Contact Detail Modal --}}
    @if($this->selectedContact)
        @php $sc = $this->selectedContact; @endphp
        <flux:modal name="contact-detail" class="md:w-[36rem] max-w-[36rem]" wire:model="showContactModal" @close="$wire.closeContact()">
            <div class="space-y-5">
                {{-- Header --}}
                <div class="flex items-center gap-4">
                    <flux:avatar :name="$sc->name ?? 'Unknown'" size="lg" />
                    <div class="flex-1 min-w-0">
                        <flux:heading size="lg">{{ $sc->name ?? 'Unknown' }}</flux:heading>
                        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1">
                            @if($sc->email)
                                <span class="text-sm text-zinc-500 flex items-center gap-1">
                                    <flux:icon name="envelope" class="w-3.5 h-3.5" /> {{ $sc->email }}
                                </span>
                            @endif
                            @if($sc->phone)
                                <span class="text-sm text-zinc-500 flex items-center gap-1">
                                    <flux:icon name="phone" class="w-3.5 h-3.5" /> {{ $sc->phone }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Score + Status + Platforms --}}
                <div class="flex items-center gap-3 flex-wrap">
                    {{-- Score circle --}}
                    <span class="inline-flex items-center justify-center w-14 h-14 rounded-full text-lg font-bold {{ match(true) {
                        $sc->lead_score >= 86 => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                        $sc->lead_score >= 71 => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                        $sc->lead_score >= 51 => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                        $sc->lead_score >= 26 => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                        default => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                    } }}">
                        {{ $sc->lead_score }}
                    </span>

                    <div>
                        <flux:badge size="sm" :color="match($sc->lead_status) {
                            'hot' => 'orange',
                            'warm' => 'yellow',
                            'cold' => 'blue',
                            'converted' => 'green',
                            'lost' => 'red',
                            default => 'zinc',
                        }">
                            {{ ucfirst($sc->lead_status) }}
                        </flux:badge>
                        <div class="flex gap-1 mt-1.5">
                            @foreach($sc->platforms as $platform)
                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-white text-xs {{ match($platform->platform) {
                                    'facebook' => 'bg-blue-500',
                                    'instagram' => 'bg-pink-500',
                                    'whatsapp' => 'bg-green-500',
                                    'telegram' => 'bg-cyan-500',
                                    default => 'bg-gray-400',
                                } }}">
                                    {{ strtoupper(substr($platform->platform, 0, 1)) }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    {{-- Link to inbox --}}
                    @if($sc->conversations->isNotEmpty())
                        <a
                            href="{{ route('inbox') }}"
                            class="ml-auto text-sm text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1"
                        >
                            <flux:icon name="chat-bubble-left-right" class="w-4 h-4" />
                            {{ $sc->conversations->count() }} {{ Str::plural('conversation', $sc->conversations->count()) }}
                        </a>
                    @endif
                </div>

                {{-- Score Event History --}}
                <div>
                    <flux:heading size="sm" class="mb-2">Score History</flux:heading>
                    <div class="max-h-72 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                        @forelse($sc->scoreEvents as $event)
                            <div class="px-3 py-2.5 border-b border-zinc-100 dark:border-zinc-700/50 last:border-0 flex items-start gap-3">
                                <span class="text-sm font-bold mt-0.5 flex-shrink-0 w-10 text-right {{ $event->score_change >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $event->score_change >= 0 ? '+' : '' }}{{ $event->score_change }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $event->reason }}</p>
                                    <span class="text-xs text-zinc-400">{{ $event->created_at->diffForHumans() }} &middot; {{ ucfirst($event->event_type) }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="px-3 py-6 text-center text-sm text-zinc-400">
                                No score events recorded yet.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
