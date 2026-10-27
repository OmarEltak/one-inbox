{{--
    Floating reconnect nudge — shown on every authenticated page.
    Mirrors the structure of the completion-toast on /connections (fixed
    bottom-end stack, pointer-events: none wrapper so the open main-content
    stays interactive). Amber-900/50 is contrast-safe per contrast-guardrails.
--}}
@php $broken = $this->brokenPages; @endphp

<div wire:key="disconnected-pages-notice-root">
@if(count($broken) > 0)
    <div
        class="fixed bottom-5 end-5 z-[55] flex flex-col gap-2 max-w-sm"
        style="pointer-events: none;"
        wire:key="disconnected-pages-notice-list"
    >
        @foreach($broken as $row)
            <div
                wire:key="disconnected-page-{{ $row['page_id'] }}"
                style="pointer-events: auto;"
                class="rounded-xl border border-amber-300 bg-white p-4 shadow-lg flex items-start gap-3"
                role="alert"
                dir="auto"
            >
                <div class="flex-shrink-0 size-9 rounded-full bg-amber-100 flex items-center justify-center">
                    <svg class="size-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-zinc-900">
                        {{ __(':name needs to be reconnected', ['name' => $row['name']]) }}
                    </p>
                    <p class="mt-1 text-xs text-zinc-600 line-clamp-3">
                        {{ $row['message'] }}
                    </p>
                    <div class="mt-3 flex items-center gap-2">
                        <a
                            href="{{ route('connections.index') }}"
                            wire:navigate.hover
                            class="inline-flex items-center gap-1 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700 transition-colors"
                        >
                            {{ __('Reconnect') }}
                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </a>
                        <button
                            type="button"
                            wire:click="acknowledge({{ $row['page_id'] }})"
                            class="rounded-lg px-2 py-1.5 text-xs text-zinc-500 hover:text-zinc-700 cursor-pointer"
                        >
                            {{ __('Dismiss') }}
                        </button>
                    </div>
                </div>
                <button
                    type="button"
                    wire:click="acknowledge({{ $row['page_id'] }})"
                    aria-label="{{ __('Dismiss') }}"
                    class="flex-shrink-0 -m-1 p-1 text-zinc-400 hover:text-zinc-700 cursor-pointer"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endforeach
    </div>
@endif
</div>

