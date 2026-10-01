@props(['message'])

@php
    $asset = $message->mediaAsset;
    $kind  = $asset?->kind;
@endphp

@if($kind === 'image')
    <button
        type="button"
        x-data
        @click="$dispatch('inbox-lightbox', { url: @js($message->media_url), alt: @js($asset->original_filename ?? 'Image') })"
        class="block max-w-xs overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700 hover:opacity-90 transition"
    >
        <img
            src="{{ $message->media_url }}"
            alt="{{ $asset->original_filename ?? 'Image from customer' }}"
            loading="lazy"
            class="max-w-full h-auto max-h-64 object-cover"
        />
    </button>
    @if($message->content && $message->content !== '[image]')
        <p class="mt-1 text-sm">{{ $message->content }}</p>
    @endif

@elseif($kind === 'audio')
    <div class="max-w-xs">
        <x-inbox.audio-player
            :src="$message->media_url"
            :mime="$asset->mime_type"
            :sentAt="optional($message->platform_sent_at ?? $message->created_at)->format('g:i A')"
        />
        @if($message->content && ! in_array($message->content, ['[voice note]', '[audio]', '[media unavailable]'], true))
            <p class="mt-1 text-xs italic text-zinc-500 dark:text-zinc-400">
                <span class="font-semibold">Transcript:</span> {{ $message->content }}
            </p>
        @elseif($message->content === '[media unavailable]')
            <p class="mt-1 text-xs italic text-red-500">Media could not be loaded.</p>
        @endif
    </div>

@elseif($kind === 'video')
    <div class="max-w-xs">
        <video controls preload="metadata" class="w-full rounded-lg max-h-64">
            <source src="{{ $message->media_url }}" type="{{ $asset->mime_type }}">
        </video>
    </div>

@elseif($kind === 'document')
    {{-- Colors follow the bubble's own text color: a fixed light hover
         (bg-zinc-50) turned the white filename on blue outbound bubbles
         into white-on-white. --}}
    <a href="{{ $message->media_url }}" target="_blank" rel="noopener"
       class="flex max-w-full min-w-48 items-center gap-3 rounded-xl bg-black/15 px-3 py-2.5 transition-colors hover:bg-black/25 focus-visible:outline-2 focus-visible:outline-current">
        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-white/25">
            <flux:icon.document class="size-5" />
        </span>
        <span class="truncate text-sm font-medium">{{ $asset->original_filename ?? __('Document') }}</span>
        <flux:icon.arrow-down-tray class="size-4 shrink-0 opacity-80" />
    </a>

@else
    <p class="text-sm">{{ $message->content }}</p>
@endif
