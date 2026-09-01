@props(['src', 'mime', 'sentAt' => null])

{{--
    WhatsApp-style audio player.

    Round play/pause button, faux waveform bars (32 of them, deterministic
    heights per URL so the bar pattern is stable across renders), progress
    fill following the audio position, and a mm:ss timer that switches from
    "0:00 / duration" (before play) to "current / duration" (during) to
    "duration" (after end). Time-of-send is rendered small in the corner.

    Uses HTML5 Audio API directly (Alpine) — no native <audio controls>
    chrome, which is what looked ugly in the inbox.
--}}

@php
    // Stable pseudo-waveform: 32 bars with heights 0.35..1.0 derived from the
    // URL hash. So the SAME asset always renders the SAME bar pattern, but
    // different assets look different. Cheap visual variety without touching
    // the audio bytes.
    $seed = crc32((string) $src);
    $bars = [];
    for ($i = 0; $i < 32; $i++) {
        $seed = (int) (($seed * 1103515245 + 12345) % 2147483648);
        $bars[] = 0.35 + (($seed % 100) / 100) * 0.65; // 0.35..1.0
    }
@endphp

<div
    x-data="audioPlayer({{ Js::from(['src' => $src, 'mime' => $mime]) }})"
    class="flex items-center gap-3 rounded-2xl bg-zinc-100 dark:bg-zinc-800 px-3 py-2.5 min-w-[220px] max-w-[320px]"
>
    {{-- Play / Pause --}}
    <button
        type="button"
        @click="toggle()"
        :aria-label="playing ? 'Pause voice note' : 'Play voice note'"
        class="flex-shrink-0 grid place-items-center h-9 w-9 rounded-full bg-white dark:bg-zinc-700 text-zinc-700 dark:text-zinc-100 shadow-sm hover:bg-zinc-50 dark:hover:bg-zinc-600 cursor-pointer transition-colors"
    >
        <svg x-show="!playing" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4 translate-x-[1px]">
            <path d="M8 5v14l11-7z" />
        </svg>
        <svg x-show="playing" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
            <path d="M6 5h4v14H6zM14 5h4v14h-4z" />
        </svg>
    </button>

    {{-- Waveform + timer --}}
    <div class="flex-1 flex flex-col gap-1 min-w-0">
        <div
            class="relative h-6 cursor-pointer"
            @click="seek($event)"
            x-ref="track"
        >
            <div class="absolute inset-0 flex items-center gap-[2px]">
                @foreach($bars as $i => $h)
                    <div
                        class="flex-1 rounded-full bg-zinc-400 dark:bg-zinc-500 transition-colors"
                        style="height: {{ round($h * 100) }}%;"
                        :class="progress > {{ $i / 32 }} ? '!bg-purple-500' : ''"
                    ></div>
                @endforeach
            </div>
        </div>
        <div class="flex items-center justify-between text-[10px] font-mono text-zinc-500 dark:text-zinc-400 tabular-nums">
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
            },

            toggle() {
                if (!this.audio) return;
                if (this.audio.paused) {
                    // Pause any other players on the page — WhatsApp behavior.
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

            destroy() {
                if (this.audio) { this.audio.pause(); this.audio.src = ''; this.audio = null; }
            },
        };
    };
}
</script>
