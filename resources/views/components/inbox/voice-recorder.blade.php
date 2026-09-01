@props(['onUploaded'])

{{--
    Voice recorder — three states, all rendered INLINE inside the composer
    (no x-teleport, so Livewire re-renders can't leave zombie pills in <body>).

    IDLE     : mic icon button matching the other composer icons.
    RECORDING: this component expands via slots to REPLACE the composer's
               siblings — the parent composer form uses `x-show=!$refs.rec.recording`
               to hide the other icons + input while we record. Cancel X on
               left, pulsing red dot + mm:ss timer center, purple send right.
    UPLOADING: brief spinner while the POST /api/media/upload → dispatch runs.

    Uploading via POST /api/media/upload, then dispatches `onUploaded` Livewire
    method with the returned MediaAsset ULID.
--}}

<div x-data="voiceRecorder({ onUploaded: {{ Js::from($onUploaded) }} })"
     x-ref="rec"
     wire:ignore
     class="contents">

    {{-- Idle: plain mic icon --}}
    <button type="button" x-show="!recording && !uploading"
            @click="start()"
            title="{{ __('Record voice note') }}"
            class="flex-shrink-0 self-end text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-pointer p-1 mb-1">
        <flux:icon.microphone class="h-5 w-5" />
    </button>

    {{-- Uploading: brief spinner --}}
    <div x-show="uploading" class="flex-1 flex items-center gap-2 self-end p-1 mb-1 text-xs text-zinc-500">
        <flux:icon name="arrow-path" class="h-4 w-4 animate-spin" />
        <span>{{ __('Sending voice note…') }}</span>
    </div>

    {{-- Recording overlay — flex-1 to consume the composer row --}}
    <div x-show="recording"
         class="flex-1 flex items-center gap-3 rounded-full border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 self-end mb-0">

        <button type="button" @click="cancel()"
                title="{{ __('Cancel recording') }}"
                class="flex-shrink-0 text-zinc-400 hover:text-red-500 cursor-pointer">
            <flux:icon name="x-mark" class="h-5 w-5" />
        </button>

        <div class="flex-1 flex items-center gap-3 text-sm">
            <span class="relative inline-flex h-2.5 w-2.5 flex-shrink-0">
                <span class="absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75 animate-ping"></span>
                <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-500"></span>
            </span>
            <span class="font-mono text-zinc-700 dark:text-zinc-200 tabular-nums"
                  x-text="formatElapsed()"></span>
            <span class="text-zinc-400">{{ __('Recording…') }}</span>
        </div>

        <button type="button" @click="stop()"
                title="{{ __('Send voice note') }}"
                class="flex-shrink-0 grid place-items-center h-9 w-9 rounded-full bg-purple-600 hover:bg-purple-700 text-white cursor-pointer transition-colors">
            <flux:icon name="paper-airplane" class="h-4 w-4" />
        </button>
    </div>
</div>

<script>
if (typeof window.voiceRecorder === 'undefined') {
    window.voiceRecorder = function ({ onUploaded }) {
        return {
            recording: false,
            uploading: false,
            elapsed: 0,
            mediaRecorder: null,
            chunks: [],
            interval: null,
            stream: null,

            init() {
                // Notify siblings (composer icons + textarea) so they can hide
                // while recording/uploading is active.
                this.$watch('recording', v => this.$dispatch('voice-active', { active: v || this.uploading }));
                this.$watch('uploading', v => this.$dispatch('voice-active', { active: v || this.recording }));
            },

            formatElapsed() {
                const m = Math.floor(this.elapsed / 60);
                const s = this.elapsed % 60;
                return `${m}:${s.toString().padStart(2, '0')}`;
            },

            async start() {
                if (this.recording || this.uploading) return;
                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    this.mediaRecorder = new MediaRecorder(this.stream, { mimeType: 'audio/webm;codecs=opus' });
                    this.chunks = [];
                    this.elapsed = 0;
                    this.recording = true;

                    this.mediaRecorder.ondataavailable = (e) => this.chunks.push(e.data);
                    this.mediaRecorder.onstop = () => this.upload();

                    this.mediaRecorder.start();
                    this.interval = setInterval(() => this.elapsed++, 1000);
                } catch (e) {
                    console.error('Voice recorder start failed', e);
                    alert('Microphone access denied.');
                }
            },

            stop() {
                if (!this.recording) return;
                this.recording = false;
                clearInterval(this.interval);
                if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                    this.mediaRecorder.stop();
                }
            },

            cancel() {
                this.recording = false;
                this.uploading = false;
                clearInterval(this.interval);
                if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                    this.mediaRecorder.onstop = null;
                    this.mediaRecorder.stop();
                }
                this.chunks = [];
                this.releaseStream();
            },

            releaseStream() {
                if (this.stream) {
                    this.stream.getTracks().forEach(t => t.stop());
                    this.stream = null;
                }
            },

            async upload() {
                this.releaseStream();

                if (this.chunks.length === 0 || this.elapsed < 1) {
                    this.chunks = [];
                    return;
                }

                this.uploading = true;
                try {
                    const blob = new Blob(this.chunks, { type: 'audio/webm' });
                    const form = new FormData();
                    form.append('file', blob, 'voice.webm');
                    form.append('kind', 'audio');

                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                    const res = await fetch('/api/media/upload', {
                        method: 'POST',
                        body: form,
                        headers: csrf ? { 'X-CSRF-TOKEN': csrf } : {},
                        credentials: 'same-origin',
                    });

                    if (!res.ok) {
                        alert('Voice note upload failed.');
                        return;
                    }

                    const asset = await res.json();
                    window.Livewire.dispatch(onUploaded, { mediaAssetId: asset.id });
                } finally {
                    this.uploading = false;
                    this.chunks = [];
                }
            },
        };
    };
}
</script>
