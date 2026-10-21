{{-- Comments visibility screen.

     Shows every public comment the AI has engaged with across this team's
     active FB/IG pages. Failed public replies (graph_error set,
     graph_reply_id null) MUST render as the error state they are —
     DO NOT hide them. See app/Livewire/Comments/Index.php and
     database/migrations/2026_09_01_100100_create_comments_table.php. --}}

@php
    $platformColors = [
        'facebook'  => ['dot' => 'bg-blue-500',  'tint' => '#1877F2'],
        'instagram' => ['dot' => 'bg-pink-500',  'tint' => '#E1306C'],
        'whatsapp'  => ['dot' => 'bg-green-500', 'tint' => '#25D366'],
        'telegram'  => ['dot' => 'bg-cyan-500',  'tint' => '#0088CC'],
    ];

    $chips = [
        \App\Livewire\Comments\Index::CHIP_ALL            => __('All'),
        \App\Livewire\Comments\Index::CHIP_PUBLIC_SUCCESS => __('Public reply success'),
        \App\Livewire\Comments\Index::CHIP_PUBLIC_FAILED  => __('Public reply failed'),
        \App\Livewire\Comments\Index::CHIP_DM_SENT        => __('DM sent'),
        \App\Livewire\Comments\Index::CHIP_DM_ONLY        => __('DM only (no public reply)'),
        \App\Livewire\Comments\Index::CHIP_FILTERED       => __('Filtered out'),
    ];

    $decisionLabels = [
        'replied'                => __('Replied'),
        'dm_only'                => __('DM only'),
        'rate_limited'           => __('Rate limited'),
        'filtered_off'           => __('Filtered — AI off'),
        'filtered_mode'          => __('Filtered — mode'),
        'filtered_scope'         => __('Filtered — scope'),
        'filtered_keyword'       => __('Filtered — keyword'),
        'filtered_working_hours' => __('Filtered — outside working hours'),
        'filtered_self'          => __('Filtered — self comment'),
        'filtered_reply'         => __('Filtered — reply thread'),
        'error_graph_api'        => __('Error — Meta Graph API'),
        'error_ai'               => __('Error — AI provider'),
    ];
@endphp

<div class="p-6 space-y-5">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-zinc-900 text-start">{{ __('Comments') }}</h1>
            <p class="mt-1 text-sm text-zinc-500 text-start">
                {{ __('Everything the AI has said publicly on your pages — plus the ones it decided not to reply to.') }}
            </p>
        </div>
        <a href="{{ route('settings.ai') }}" wire:navigate
           class="inline-flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-medium text-zinc-600 hover:text-zinc-800 border border-zinc-300 bg-white hover:bg-zinc-50 transition-colors">
            <flux:icon name="cog-6-tooth" class="size-4" />
            {{ __('AI comment settings') }}
        </a>
    </div>

    {{-- Filter chips --}}
    <div class="flex flex-wrap gap-2">
        @foreach($chips as $value => $label)
            <button type="button"
                    wire:click="setChip('{{ $value }}')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors cursor-pointer
                           {{ $chip === $value
                              ? 'bg-emerald-600 text-white border-emerald-600'
                              : 'bg-white text-zinc-600 border-zinc-300 hover:bg-zinc-50 hover:text-zinc-800' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Page + decision filters --}}
    <div class="flex flex-wrap gap-3">
        <flux:select wire:model.live="pageFilter" size="sm" class="w-56">
            <option value="all">{{ __('All pages') }}</option>
            @foreach($this->teamPages as $teamPage)
                <option value="{{ $teamPage->id }}">
                    {{ $teamPage->name }} — {{ ucfirst($teamPage->platform) }}
                </option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="decisionFilter" size="sm" class="w-56">
            <option value="all">{{ __('All decisions') }}</option>
            @foreach($this->availableDecisions as $decisionValue)
                <option value="{{ $decisionValue }}">
                    {{ $decisionLabels[$decisionValue] ?? $decisionValue }}
                </option>
            @endforeach
        </flux:select>

        @if($chip !== \App\Livewire\Comments\Index::CHIP_ALL || $pageFilter !== 'all' || $decisionFilter !== 'all')
            <button type="button" wire:click="clearFilters"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium text-zinc-500 hover:text-zinc-700 cursor-pointer">
                <flux:icon name="x-mark" class="size-3.5" />
                {{ __('Clear filters') }}
            </button>
        @endif
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-2xl aio-card">
        <table class="w-full text-sm aio-table">
            <thead>
                <tr>
                    <th class="text-start px-4 py-3">{{ __('Page') }}</th>
                    <th class="text-start px-4 py-3">{{ __('Commenter') }}</th>
                    <th class="text-start px-4 py-3">{{ __('AI public reply') }}</th>
                    <th class="text-start px-4 py-3">{{ __('DM') }}</th>
                    <th class="text-start px-4 py-3">{{ __('Received') }}</th>
                    <th class="text-end px-4 py-3">{{ __('Link') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->comments as $comment)
                    @php
                        $page       = $comment->page;
                        $platform   = $page?->platform ?? 'facebook';
                        $colors     = $platformColors[$platform] ?? ['dot' => 'bg-zinc-400', 'tint' => '#71717a'];
                        $commentText = (string) ($comment->text ?? '');
                        $commentTextShort = mb_strlen($commentText) > 180
                            ? mb_substr($commentText, 0, 180) . '…'
                            : $commentText;
                        $hasPublicReply = ! empty($comment->graph_reply_id);
                        $hasGraphError  = ! empty($comment->graph_error);
                        $graphErrorMsg  = null;
                        if ($hasGraphError && is_array($comment->graph_error)) {
                            $graphErrorMsg = $comment->graph_error['message']
                                ?? $comment->graph_error['error']['message']
                                ?? json_encode($comment->graph_error);
                        }
                        $decisionLabel = $decisionLabels[$comment->decision] ?? $comment->decision;

                        // External link construction. FB: facebook.com/{comment_id} resolves to the comment.
                        // IG: no reliable public URL from the platform_comment_id alone; link omitted for IG.
                        $externalUrl = null;
                        if ($platform === 'facebook' && $comment->platform_comment_id) {
                            $externalUrl = 'https://facebook.com/' . $comment->platform_comment_id;
                        }
                    @endphp
                    <tr class="align-top">
                        {{-- Page --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="relative flex-shrink-0">
                                    @if($page?->avatar)
                                        <img src="{{ $page->avatar }}" alt="{{ $page->name }}"
                                             class="size-8 rounded-full object-cover" />
                                    @else
                                        <div class="size-8 rounded-full flex items-center justify-center text-white text-xs font-bold"
                                             style="background: {{ $colors['tint'] }};">
                                            {{ strtoupper(mb_substr($page?->name ?? '?', 0, 1)) }}
                                        </div>
                                    @endif
                                    <span class="absolute -bottom-0.5 -end-0.5 size-3 rounded-full border-2 border-white {{ $colors['dot'] }}"></span>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-zinc-900 truncate max-w-[180px]">{{ $page?->name ?? __('Unknown page') }}</p>
                                    <p class="text-[11px] text-zinc-400 uppercase tracking-wider">{{ ucfirst($platform) }}</p>
                                </div>
                            </div>
                        </td>

                        {{-- Commenter + their comment text --}}
                        <td class="px-4 py-3 max-w-[280px]">
                            <p class="font-medium text-zinc-900 truncate">{{ $comment->commenter_name ?: __('Unknown') }}</p>
                            <p class="mt-0.5 text-xs text-zinc-600 whitespace-pre-wrap break-words" dir="auto">{{ $commentTextShort }}</p>
                            @if($comment->decision)
                                <span class="inline-block mt-1.5 text-[10px] uppercase tracking-wider font-bold px-1.5 py-0.5 rounded-full
                                    {{ str_starts_with($comment->decision, 'filtered_')
                                        ? 'bg-zinc-100 text-zinc-500'
                                        : (str_starts_with($comment->decision, 'error_')
                                            ? 'bg-red-100 text-red-700'
                                            : 'bg-blue-100 text-blue-700') }}">
                                    {{ $decisionLabel }}
                                </span>
                            @endif
                        </td>

                        {{-- AI public reply (or error, or not-replied) --}}
                        <td class="px-4 py-3 max-w-[320px]">
                            @if($hasPublicReply && $comment->reply_text)
                                <div class="flex items-start gap-1.5">
                                    <flux:icon name="check-circle" class="size-4 text-emerald-600 flex-shrink-0 mt-0.5" />
                                    <p class="text-xs text-zinc-800 whitespace-pre-wrap break-words" dir="auto">{{ $comment->reply_text }}</p>
                                </div>
                            @elseif($hasGraphError)
                                <div class="flex items-start gap-1.5">
                                    <flux:icon name="exclamation-triangle" class="size-4 text-red-500 flex-shrink-0 mt-0.5" />
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-red-700">{{ __('Public reply failed') }}</p>
                                        <p class="mt-0.5 text-[11px] text-red-600 break-words" dir="auto">
                                            {{ $graphErrorMsg ?: __('Unknown Graph API error') }}
                                        </p>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-start gap-1.5">
                                    <flux:icon name="minus-circle" class="size-4 text-zinc-400 flex-shrink-0 mt-0.5" />
                                    <p class="text-xs text-zinc-500">
                                        {{ __('Not replied') }}
                                        @if($comment->decision)
                                            — <span class="text-zinc-600">{{ $decisionLabel }}</span>
                                        @endif
                                        @if($comment->decision_reason)
                                            <br><span class="text-[11px] text-zinc-400" dir="auto">{{ $comment->decision_reason }}</span>
                                        @endif
                                    </p>
                                </div>
                            @endif
                        </td>

                        {{-- DM status --}}
                        <td class="px-4 py-3">
                            @if($comment->dm_sent_at)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[11px] font-semibold">
                                    <flux:icon name="paper-airplane" class="size-3" />
                                    {{ __('DM sent') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-zinc-100 text-zinc-500 text-[11px] font-semibold">
                                    {{ __('No DM') }}
                                </span>
                            @endif
                        </td>

                        {{-- Received --}}
                        <td class="px-4 py-3">
                            <p class="text-xs text-zinc-700 whitespace-nowrap">
                                {{ $comment->received_at?->translatedFormat('M j, H:i') }}
                            </p>
                            <p class="text-[11px] text-zinc-400">
                                {{ $comment->received_at?->diffForHumans() }}
                            </p>
                        </td>

                        {{-- External link --}}
                        <td class="px-4 py-3 text-end">
                            @if($externalUrl)
                                <a href="{{ $externalUrl }}" target="_blank" rel="noopener noreferrer"
                                   title="{{ __('View on :platform', ['platform' => ucfirst($platform)]) }}"
                                   class="inline-flex items-center justify-center size-7 rounded-lg text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100 transition-colors">
                                    <flux:icon name="arrow-top-right-on-square" class="size-4" />
                                </a>
                            @else
                                <span class="inline-flex items-center justify-center size-7 text-zinc-300" title="{{ __('No public link available') }}">
                                    <flux:icon name="link-slash" class="size-4" />
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <flux:icon name="chat-bubble-left-right" class="size-10 text-zinc-300" />
                                @if($this->teamPages->isEmpty())
                                    <p class="text-sm font-medium text-zinc-700">{{ __('No active Facebook or Instagram pages') }}</p>
                                    <p class="text-xs text-zinc-500 max-w-sm">
                                        {{ __('Connect a Facebook or Instagram page to start seeing the AI\'s public comment activity here.') }}
                                    </p>
                                    <a href="{{ route('connections.index') }}" wire:navigate
                                       class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-500 transition-colors">
                                        {{ __('Go to Connections') }}
                                    </a>
                                @else
                                    <p class="text-sm font-medium text-zinc-700">{{ __('No comments match these filters yet') }}</p>
                                    <p class="text-xs text-zinc-500 max-w-sm">
                                        {{ __('When someone comments on one of your posts, the AI\'s decision will show up here.') }}
                                    </p>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($this->comments->hasPages())
        <div class="pt-2">
            {{ $this->comments->links() }}
        </div>
    @endif
</div>
