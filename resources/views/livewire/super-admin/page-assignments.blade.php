<div class="p-6">
    <div class="mb-6">
        <flux:heading size="xl">Page Assignments</flux:heading>
        <flux:text class="mt-1">Move pages connected through the OT AI account into the right customer workspace.</flux:text>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
            <flux:text class="text-green-700 dark:text-green-400">{{ session('success') }}</flux:text>
        </div>
    @endif

    <div class="mb-4 flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[220px]">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search pages..." icon="magnifying-glass" />
        </div>
        <div class="w-40">
            <flux:select wire:model.live="platformFilter">
                <option value="">All platforms</option>
                <option value="facebook">Facebook</option>
                <option value="instagram">Instagram</option>
                <option value="whatsapp">WhatsApp</option>
                <option value="telegram">Telegram</option>
                <option value="email">Email</option>
            </flux:select>
        </div>
    </div>

    @if($this->pages->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 p-12 text-center">
            <flux:icon name="rectangle-stack" class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-3" />
            <flux:text class="text-zinc-500">No pages found. Connect a Facebook/Instagram/WhatsApp account with OT AI to import pages.</flux:text>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr class="text-left text-xs uppercase tracking-wide text-zinc-500">
                        <th class="px-4 py-3">Page</th>
                        <th class="px-4 py-3">Platform</th>
                        <th class="px-4 py-3">Currently In</th>
                        <th class="px-4 py-3">Assign To</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->pages as $page)
                        @php $inHolding = in_array($page->team_id, $this->holdingTeamIds, true); @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    @if($page->avatar)
                                        <img src="{{ $page->avatar }}" class="size-8 rounded-full object-cover flex-shrink-0" alt="" />
                                    @else
                                        <div class="size-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex-shrink-0"></div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100 truncate">{{ $page->name }}</div>
                                        <div class="text-xs text-zinc-500 truncate">{{ $page->platform_page_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge color="blue" size="sm">{{ ucfirst($page->platform) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-zinc-700 dark:text-zinc-300">{{ $page->team?->name ?? '—' }}</span>
                                    @if($inHolding)
                                        <flux:badge color="amber" size="sm">Holding</flux:badge>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <flux:select wire:model="assignments.{{ $page->id }}">
                                    @foreach($this->customerTeams as $team)
                                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                                    @endforeach
                                    @if($this->customerTeams->isEmpty())
                                        <option value="">No customers yet</option>
                                    @endif
                                </flux:select>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <flux:button wire:click="assign({{ $page->id }})" size="sm" variant="primary" icon="arrow-right">
                                    Move
                                </flux:button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
