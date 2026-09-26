<div class="p-6">
    <div class="mb-6">
        <flux:heading size="xl" class="text-zinc-900">{{ __('Page Assignments') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-900">{{ __('Move pages connected through the OT AI account into the right customer workspace.') }}</flux:text>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
            <p class="text-sm font-medium text-green-900 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    <div class="mb-4 flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[220px]">
            <div class="relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search pages..."
                    class="block w-full rounded-lg border border-zinc-300 bg-white text-zinc-900 placeholder:text-zinc-400 pl-9 pr-3 py-2.5 text-sm shadow-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition" />
            </div>
        </div>
        <div class="w-40">
            <flux:select wire:model.live="platformFilter" class="text-zinc-900">
                <option value="">{{ __('All platforms') }}</option>
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
            <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('No pages found. Connect a Facebook/Instagram/WhatsApp account with OT AI to import pages.') }}</p>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr class="text-left text-xs uppercase tracking-wide text-zinc-500">
                        <th class="px-4 py-3">{{ __('Page') }}</th>
                        <th class="px-4 py-3">{{ __('Platform') }}</th>
                        <th class="px-4 py-3">{{ __('Currently In') }}</th>
                        <th class="px-4 py-3">{{ __('Assign To') }}</th>
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
                                <span class="inline-flex items-center rounded-md bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-900 ring-1 ring-blue-200">{{ ucfirst($page->platform) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-zinc-700 dark:text-zinc-300">{{ $page->team?->name ?? '—' }}</span>
                                    @if($inHolding)
                                        <span class="inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-900 ring-1 ring-amber-200">Holding</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <flux:select wire:model="assignments.{{ $page->id }}">
                                    @foreach($this->customerTeams as $team)
                                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                                    @endforeach
                                    @if($this->customerTeams->isEmpty())
                                        <option value="">{{ __('No customers yet') }}</option>
                                    @endif
                                </flux:select>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <flux:button wire:click="assign({{ $page->id }})" size="sm" variant="primary" icon="arrow-right">
                                    {{ __('Move') }}
                                </flux:button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
