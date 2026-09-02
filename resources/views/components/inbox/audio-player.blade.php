@props(['src', 'mime', 'sentAt' => null])

{{--
    Audio player using an in-DOM <audio> element with an explicit <source>
    so the browser handles Range requests + codec sniffing natively. Building
    an Audio() object in JS and setting `.type` (not a real HTMLAudioElement
    property) was silently causing playback to fail on WhatsApp's audio/mp4
    voice notes — Chrome accepted the src but never decoded the stream.

    Also: BOTH play/pause SVGs used to flash simultaneously before Alpine
    hydrated (each x-show only kicks in after init). Fixed by inverting the
    logic — one SVG, x-cloak on the wrapper, icon path swapped reactively.
--}}

@php
    $seed = crc32((string) $src);
    $bars = [];
    for ($i = 0; $i < 32; $i++) {
        $seed = (int) (($seed * 1103515245 + 12345) % 2147483648);
        $bars[] = 0.35 + (($seed % 100) / 100) * 0.65;
    }
@endphp

<div
    x-data="audioPlayer()"
    x-init="init($refs.audio)"
    x-cloak
    class="flex items-center gap-3 py-1 min-w-[220px] max-w-[320px]"
>
    {{-- The actual <audio> element — hidden but fully functional. Browser
         handles Range, codec sniffing, and playback natively. --}}
    <audio x-ref="audio" preload="metadata" class="hidden">
        <source src="{{ $src }}" @if($mime) type="{{ $mime }}" @endif />
    </audio>

    <button
        type="button"
        @click="toggle()"
        :aria-label="playing ? 'Pause' : 'Play'"
        class="flex-shrink-0 grid place-items-center h-9 w-9 rounded-full bg-white/90 hover:bg-white text-zinc-800 shadow-sm cursor-pointer transition-colors"
    >
        {{-- Single SVG whose path is swapped reactively. Avoids the "both
             icons visible before Alpine hydrates" flash we saw with two
             x-show'd SVGs. --}}
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4" :class="playing ? '' : 'translate-x-[1px]'">
            <path :d="playing ? 'M6 5h4v14H6zM14 5h4v14h-4z' : 'M8 5v14l11-7z'" />
        </svg>
    </button>

    <div class="flex-1 flex flex-col gap-1 min-w-0">
        <div
            class="relative h-6 cursor-pointer"
            @click="seek($event)"
            x-ref="track"
        >
            <div class="absolute inset-0 flex items-center gap-[2px]">
                @foreach($bars as $i => $h)
                    <div
                        class="flex-1 rounded-full bg-current opacity-40"
                        style="height: {{ round($h * 100) }}%;"
                        :class="progress > {{ $i / 32 }} ? '!opacity-100' : ''"
                    ></div>
                @endforeach
            </div>
        </div>
        <div class="flex items-center justify-between text-[10px] font-mono tabular-nums opacity-75">
            <span x-text="formatTime(playing || currentTime > 0 ? currentTime : duration)"></span>
            @if($sentAt)
                <span>{{ $sentAt }}</span>
            @endif
        </div>
    </div>
</div>

<script>
if (typeof window.audioPlayer === 'undefined') {
    window.audioPlayer = function () {
        return {
            audio: null,
            playing: false,
            duration: 0,
            currentTime: 0,
            progress: 0,

            init(audioEl) {
                this.audio = audioEl;

                // Use the browser's own <audio> element — it handles Range
                // requests, codec detection, and progressive download without
                // any of the "silent failure" quirks the JS Audio() constructor
                // exhibits on WhatsApp audio/mp4 voice notes.
                this.audio.addEventListener('loadedmetadata', () => {
                    this.duration = isFinite(this.audio.duration) ? this.audio.duration : 0;
                });
                this.audio.addEventListener('durationchange', () => {
                    if (isFinite(this.audio.duration)) this.duration = this.audio.duration;
                });
                this.audio.addEventListener('timeupdate', () => {
                    this.currentTime = this.audio.currentTime;
                    this.progress = this.duration > 0 ? this.currentTime / this.duration : 0;
                });
                this.audio.addEventListener('play',  () => { this.playing = true; });
                this.audio.addEventListener('pause', () => { this.playing = false; });
                this.audio.addEventListener('ended', () => {
                    this.playing = false;
                    this.currentTime = this.duration;
                    this.progress = 1;
                });
                this.audio.addEventListener('error', () => {
                    console.error('[audio-player] error', this.audio.error, this.audio.currentSrc);
                });
            },

            toggle() {
                if (!this.audio) return;
                if (this.audio.paused) {
                    // Pause every other audio element on the page — WhatsApp behavior.
                    document.querySelectorAll('audio').forEach(a => { if (a !== this.audio) a.pause(); });
                    const p = this.audio.play();
                    if (p && typeof p.catch === 'function') {
                        p.catch(e => console.error('[audio-player] play failed', e));
                    }
                } else {
                    this.audio.pause();
                }
            },

            seek(ev) {
                if (!this.audio || !this.duration) return;
                const r = this.$refs.track.getBoundingClientRect();
                const ratio = Math.max(0, Math.min(1, (ev.clientX - r.left) / r.width));
                this.audio.currentTime = ratio * this.duration;
            },

            formatTime(sec) {
                if (!sec || !isFinite(sec)) return '0:00';
                const m = Math.floor(sec / 60);
                const s = Math.floor(sec % 60);
                return `${m}:${s.toString().padStart(2, '0')}`;
            },
        };
    };
}
</script>
