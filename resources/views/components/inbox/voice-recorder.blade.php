@props(['onUploaded'])

{{--
    TEMPORARY minimal state. The stuck "Recording…" pill was removed per user
    request — it left zombie instances across Livewire re-renders. When we
    revisit voice recording UX, restore a two-state design here (mic idle →
    recording overlay) with a proper cleanup hook (`Livewire.on('morph.updating', ...)`)
    so state resets on every server round-trip.

    For now: single mic icon that starts recording on click, uploads on second
    click, and shows a discreet "Sending…" while POST /api/media/upload runs.
--}}

<div x-data="voiceRecorder({ onUploaded: {{ Js::from($onUploaded) }} })"
     class="flex-shrink-0 self-end mb-1">
    <button
        type="button"
        @click="toggle()"
        :title="recording ? 'Stop &amp; send' : (uploading ? 'Sending…' : 'Record voice note')"
        class="p-1 cursor-pointer transition-colors"
        :class="recording ? 'text-red-500 animate-pulse' : (uploading ? 'text-zinc-300' : 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300')"
        :disabled="uploading"
    >
        <flux:icon.microphone class="h-5 w-5" />
    </button>
</div>

<script>
if (typeof window.voiceRecorder === 'undefined') {
    window.voiceRecorder = function ({ onUploaded }) {
        return {
            recording: false,
            uploading: false,
            mediaRecorder: null,
            chunks: [],
            stream: null,

            toggle() {
                if (this.uploading) return;
                this.recording ? this.stop() : this.start();
            },

            async start() {
                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    this.mediaRecorder = new MediaRecorder(this.stream, { mimeType: 'audio/webm;codecs=opus' });
                    this.chunks = [];
                    this.recording = true;

                    this.mediaRecorder.ondataavailable = (e) => this.chunks.push(e.data);
                    this.mediaRecorder.onstop = () => this.upload();
                    this.mediaRecorder.start();
                } catch (e) {
                    console.error('Voice recorder start failed', e);
                    alert('Microphone access denied.');
                }
            },

            stop() {
                if (!this.recording) return;
                this.recording = false;
                if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                    this.mediaRecorder.stop();
                }
            },

            releaseStream() {
                if (this.stream) {
                    this.stream.getTracks().forEach(t => t.stop());
                    this.stream = null;
                }
            },

            async upload() {
                this.releaseStream();
                if (this.chunks.length === 0) return;

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
