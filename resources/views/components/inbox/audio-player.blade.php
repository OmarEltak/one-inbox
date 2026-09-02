@props(['src', 'mime', 'sentAt' => null])

{{--
    Audio player that inherits its parent bubble color.

    - Play/pause is a neutral white/dark circle (no accent color at all). It
      contrasts against both light-inbound and blue-outbound bubbles without
      needing to know which side it's on.
    - Waveform bars use bg-current opacity-40 for unplayed, opacity-100 for
      played — so they pick up the bubble's text color naturally.
    - No own background. Sits flush inside the bubble.
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
    x-data="audioPlayer({{ Js::from(['src' => $src, 'mime' => $mime]) }})"
    x-init="init()"
    class="flex items-center gap-3 py-1 min-w-[220px] max-w-[320px]"
>
    <button
        type="button"
        @click="toggle()"
        :aria-label="playing ? 'Pause' : 'Play'"
        class="flex-shrink-0 grid place-items-center h-9 w-9 rounded-full bg-white/90 hover:bg-white text-zinc-800 shadow-sm cursor-pointer transition-colors"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4 translate-x-[1px]" x-show="!playing">
            <path d="M8 5v14l11-7z" />
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4" x-show="playing" x-cloak>
            <path d="M6 5h4v14H6zM14 5h4v14h-4z" />
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
    window.audioPlayer = function ({ src, mime }) {
        return {
            audio: null,
            playing: false,
            duration: 0,
            currentTime: 0,
            progress: 0,

            init() {
                this.audio = new Audio();
                if (mime) this.audio.type = mime;
                this.audio.src = src;
                this.audio.preload = 'metadata';
                this.audio.crossOrigin = 'anonymous';

                this.audio.addEventListener('loadedmetadata', () => { this.duration = this.audio.duration || 0; });
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
                this.audio.addEventListener('error', (e) => console.error('audio error', e, this.audio.error));
            },

            toggle() {
                if (!this.audio) return;
                if (this.audio.paused) {
                    document.querySelectorAll('audio').forEach(a => { if (a !== this.audio) a.pause(); });
                    this.audio.play().catch(e => console.error('audio play failed', e));
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
