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
    <a href="{{ $message->media_url }}" target="_blank" rel="noopener"
       class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 dark:border-zinc-700 px-3 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800">
        <flux:icon.document class="size-5" />
        <span class="text-sm">{{ $asset->original_filename ?? 'Document' }}</span>
    </a>

@else
    <p class="text-sm">{{ $message->content }}</p>
@endif
