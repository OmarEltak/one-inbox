<div class="p-6 space-y-5">
    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ __('Contacts') }}</h1>
            <p class="mt-1 text-sm text-white/40">{{ __('Manage your leads across all connected pages.') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <button class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-white/60 hover:text-white/80 transition-colors"
                    style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                {{ __('Export') }}
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3">
        <div class="flex items-center gap-2 rounded-xl px-3 py-2 flex-1 max-w-xs"
             style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07);">
            <svg class="size-4 text-white/25 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('Search contacts...') }}"
                   class="bg-transparent text-sm text-white/70 placeholder-white/25 outline-none w-full" />
        </div>
        <flux:select wire:model.live="statusFilter" size="sm" class="w-36">
            <option value="all">{{ __('All Statuses') }}</option>
            <option value="new">{{ __('New') }}</option>
            <option value="warm">{{ __('Warm') }}</option>
            <option value="hot">{{ __('Hot') }}</option>
            <option value="cold">{{ __('Cold') }}</option>
            <option value="converted">{{ __('Converted') }}</option>
            <option value="lost">{{ __('Lost') }}</option>
        </flux:select>
    </div>

    {{-- Contacts Table --}}
    <div class="overflow-x-auto rounded-2xl aio-card">
        <table class="w-full text-sm aio-table">
            <thead>
                <tr>
                    <th class="text-left px-4 py-3">Contact</th>
                    <th class="text-left px-4 py-3">Pages</th>
                    <th class="text-left px-4 py-3">Lead Score</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Last Interaction</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->contacts as $contact)
                    @php
                        // Get unique pages this contact has conversed through
                        $contactPages = $contact->conversations
                            ->pluck('page')
                            ->filter()
                            ->unique('id')
                            ->values();
                    @endphp
                    <tr
                        wire:click="selectContact({{ $contact->id }})"
                        class="cursor-pointer"
                    >
                        {{-- Contact name + avatar --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="size-9 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                                     style="background: linear-gradient(135deg, #3b82f6, #8b5cf6);">
                                    {{ strtoupper(substr($contact->name ?? 'U', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-[#f1f5f9]">{{ $contact->name ?? 'Unknown' }}</p>
                                    @if($contact->email)
                                        <p class="text-xs text-[#64748b]">{{ $contact->email }}</p>
                                    @elseif($contact->phone)
                                        <p class="text-xs text-[#64748b]">{{ $contact->phone }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Pages (which page they messaged through) --}}
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1.5">
                                @if($contactPages->isNotEmpty())
                                    @foreach($contactPages->take(3) as $page)
                                        <div class="aio-page-chip">
                                            <span class="size-2 rounded-full flex-shrink-0 {{ match($page->platform) {
                                                'facebook'  => 'bg-blue-400',
                                                'instagram' => 'bg-pink-400',
                                                'whatsapp'  => 'bg-green-400',
                                                'telegram'  => 'bg-cyan-400',
                                                'tiktok'    => 'bg-red-400',
                                                'snapchat'  => 'bg-yellow-400',
                                                'email'     => 'bg-orange-400',
                                                default     => 'bg-gray-400',
                                            } }}"></span>
                                            <span class="name">{{ $page->name }}</span>
                                        </div>
                                    @endforeach
                                    @if($contactPages->count() > 3)
                                        <span class="text-xs text-[#64748b]">+{{ $contactPages->count() - 3 }}</span>
                                    @endif
                                @else
                                    {{-- Fallback to platform type if no page info --}}
                                    @foreach($contact->platforms as $platform)
                                        <div class="aio-page-chip">
                                            <span class="size-2 rounded-full flex-shrink-0 {{ match($platform->platform) {
                                                'facebook'  => 'bg-blue-400',
                                                'instagram' => 'bg-pink-400',
                                                'whatsapp'  => 'bg-green-400',
                                                'telegram'  => 'bg-cyan-400',
                                                'tiktok'    => 'bg-red-400',
                                                'snapchat'  => 'bg-yellow-400',
                                                'email'     => 'bg-orange-400',
                                                default     => 'bg-gray-400',
                                            } }}"></span>
                                            <span class="name">{{ $platform->platform_name ?: ucfirst($platform->platform) }}</span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </td>

                        {{-- Lead Score: progress bar + number --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3 min-w-[120px]">
                                <div class="flex-1 bg-[#1e2d44] rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full transition-all {{ match(true) {
                                        $contact->lead_score >= 86 => 'bg-red-400',
                                        $contact->lead_score >= 71 => 'bg-orange-400',
                                        $contact->lead_score >= 51 => 'bg-yellow-400',
                                        $contact->lead_score >= 26 => 'bg-blue-400',
                                        default => 'bg-slate-500',
                                    } }}" style="width: {{ min($contact->lead_score, 100) }}%"></div>
                                </div>
                                <span class="text-sm font-semibold text-[#f1f5f9] w-7 text-right tabular-nums">{{ $contact->lead_score }}</span>
                            </div>
                        </td>

                        {{-- Status badge --}}
                        <td class="px-4 py-3">
                            <span class="aio-badge aio-badge-{{ $contact->lead_status }}">
                                {{ ucfirst($contact->lead_status) }}
                            </span>
                        </td>

                        {{-- Last interaction --}}
                        <td class="px-4 py-3 text-xs text-[#64748b]">
                            {{ $contact->last_interaction_at?->diffForHumans() ?? 'Never' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-14 text-center text-[#64748b]">
                            <svg class="mx-auto mb-3 size-8 text-[#1e2d44]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            No contacts found. They appear here when people message your connected pages.
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
                    <div class="size-14 rounded-full flex items-center justify-center text-white text-xl font-bold flex-shrink-0"
                         style="background: linear-gradient(135deg, #3b82f6, #8b5cf6);">
                        {{ strtoupper(substr($sc->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg font-bold text-[#f1f5f9] truncate">{{ $sc->name ?? 'Unknown' }}</h3>
                        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1">
                            @if($sc->email)
                                <span class="text-sm text-[#64748b] flex items-center gap-1">
                                    <flux:icon name="envelope" class="w-3.5 h-3.5" /> {{ $sc->email }}
                                </span>
                            @endif
                            @if($sc->phone)
                                <span class="text-sm text-[#64748b] flex items-center gap-1">
                                    <flux:icon name="phone" class="w-3.5 h-3.5" /> {{ $sc->phone }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Score + Status + Platforms --}}
                <div class="flex items-center gap-3 flex-wrap">
                    <div class="flex items-center justify-center size-14 rounded-full border-2 text-lg font-bold {{ match(true) {
                        $sc->lead_score >= 86 => 'border-red-500/40 text-red-400',
                        $sc->lead_score >= 71 => 'border-orange-500/40 text-orange-400',
                        $sc->lead_score >= 51 => 'border-yellow-500/40 text-yellow-400',
                        $sc->lead_score >= 26 => 'border-blue-500/40 text-blue-400',
                        default => 'border-slate-700 text-slate-400',
                    } }}">
                        {{ $sc->lead_score }}
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="aio-badge aio-badge-{{ $sc->lead_status }}">
                                {{ ucfirst($sc->lead_status) }}
                            </span>
                            @if($sc->lead_status !== 'converted')
                                <button wire:click="setLeadStatus({{ $sc->id }}, 'converted')"
                                    class="cursor-pointer rounded-full bg-green-500/20 px-2.5 py-0.5 text-xs font-medium text-green-400 hover:bg-green-500/30 transition-colors">
                                    Mark Converted
                                </button>
                            @endif
                            @if($sc->lead_status !== 'lost')
                                <button wire:click="setLeadStatus({{ $sc->id }}, 'lost')"
                                    class="cursor-pointer rounded-full bg-red-500/20 px-2.5 py-0.5 text-xs font-medium text-red-400 hover:bg-red-500/30 transition-colors">
                                    Mark Lost
                                </button>
                            @endif
                        </div>
                        <div class="flex gap-1.5 flex-wrap">
                            @foreach($sc->platforms as $platform)
                                <div class="aio-page-chip">
                                    <span class="size-2 rounded-full flex-shrink-0 {{ match($platform->platform) {
                                        'facebook'  => 'bg-blue-400',
                                        'instagram' => 'bg-pink-400',
                                        'whatsapp'  => 'bg-green-400',
                                        'telegram'  => 'bg-cyan-400',
                                        default     => 'bg-gray-400',
                                    } }}"></span>
                                    <span class="name">{{ $platform->platform_name ?: ucfirst($platform->platform) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if($sc->conversations->isNotEmpty())
                        <a href="{{ route('inbox') }}" wire:navigate class="ml-auto text-sm text-[#3b82f6] hover:text-blue-400 flex items-center gap-1">
                            <flux:icon name="chat-bubble-left-right" class="w-4 h-4" />
                            {{ $sc->conversations->count() }} {{ Str::plural('conversation', $sc->conversations->count()) }}
                        </a>
                    @endif
                </div>

                {{-- Score Event History --}}
                <div>
                    <p class="text-sm font-semibold text-[#f1f5f9] mb-2">Score History</p>
                    @if($sc->scoreEvents->isEmpty())
                        <p class="text-sm text-[#64748b]">No score events yet.</p>
                    @else
                        <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                            @foreach($sc->scoreEvents->take(10) as $event)
                                <div class="flex items-start gap-2 text-xs">
                                    <span class="flex-shrink-0 font-mono font-bold pt-0.5 {{ $event->score_change >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                        {{ $event->score_change >= 0 ? '+' : '' }}{{ $event->score_change }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-[#f1f5f9]">{{ $event->reason }}</p>
                                        <p class="text-[#64748b]">{{ $event->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </flux:modal>
    @endif
</div>
