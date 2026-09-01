@props(['onUploaded'])

{{--
    Two-state voice recorder.

    IDLE:  a mic icon button matching the other composer icons (paperclip,
           emoji, quick replies).

    RECORDING: fills the composer row and REPLACES it (via a portal to a
           sibling in the parent form) — cancel X on the left, pulsing red
           dot + mm:ss timer in the middle, purple send button on the right.
           Matches WhatsApp / Messenger UX.

    Uploads via POST /api/media/upload → dispatches the given Livewire method
    with the returned MediaAsset ULID so the parent component creates + sends
    the outbound message.
--}}

<div x-data="voiceRecorder({ onUploaded: {{ Js::from($onUploaded) }} })"
     x-cloak
     class="contents">

    {{-- Idle: plain icon button that visually matches its siblings --}}
    <button type="button" x-show="!recording && !uploading"
            @click="start()"
            title="{{ __('Record voice note') }}"
            class="flex-shrink-0 self-end text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-pointer p-1 mb-1">
        <flux:icon.microphone class="h-5 w-5" />
    </button>

    {{-- Uploading spinner (brief, between stop and Livewire dispatch) --}}
    <div x-show="uploading" class="flex items-center gap-2 self-end p-1 mb-1 text-xs text-zinc-500">
        <flux:icon name="arrow-path" class="h-4 w-4 animate-spin" />
        <span>{{ __('Sending…') }}</span>
    </div>

    {{-- Recording overlay — absolutely positioned to cover the whole composer row --}}
    <template x-teleport="body">
        <div x-show="recording"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="fixed bottom-4 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 rounded-full border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-2.5 shadow-lg min-w-[280px]">

            <button type="button" @click="cancel()"
                    title="{{ __('Cancel') }}"
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
    </template>
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
