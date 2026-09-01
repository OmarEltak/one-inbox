@props(['onUploaded'])

<div x-data="voiceRecorder({ onUploaded: {{ Js::from($onUploaded) }} })" class="inline-flex items-center gap-2">
    <button type="button" x-show="!recording" @mousedown="start" @touchstart.prevent="start"
        class="p-2 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800">
        <flux:icon.microphone class="size-5" />
    </button>
    <div x-show="recording" class="flex items-center gap-2">
        <span class="size-3 rounded-full bg-red-500 animate-pulse"></span>
        <span x-text="elapsed + 's'" class="text-sm font-mono"></span>
        <button type="button" @click="stop" class="px-3 py-1 rounded bg-red-500 text-white text-sm">Send</button>
        <button type="button" @click="cancel" class="px-3 py-1 rounded bg-zinc-200 dark:bg-zinc-700 text-sm">Cancel</button>
    </div>
</div>

<script>
function voiceRecorder({ onUploaded }) {
    return {
        recording: false,
        elapsed: 0,
        mediaRecorder: null,
        chunks: [],
        interval: null,

        async start() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                this.mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm;codecs=opus' });
                this.chunks = [];
                this.elapsed = 0;
                this.recording = true;

                this.mediaRecorder.ondataavailable = (e) => this.chunks.push(e.data);
                this.mediaRecorder.onstop = () => this.upload(stream);

                this.mediaRecorder.start();
                this.interval = setInterval(() => this.elapsed++, 1000);
            } catch (e) {
                alert('Microphone access denied.');
            }
        },

        stop() {
            if (this.mediaRecorder && this.recording) {
                this.mediaRecorder.stop();
                clearInterval(this.interval);
                this.recording = false;
            }
        },

        cancel() {
            if (this.mediaRecorder && this.recording) {
                this.mediaRecorder.stream.getTracks().forEach(t => t.stop());
                clearInterval(this.interval);
                this.recording = false;
                this.chunks = [];
            }
        },

        async upload(stream) {
            stream.getTracks().forEach(t => t.stop());

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
            // Dispatch to Livewire — component reads it in `sendWithMedia`.
            window.Livewire.dispatch(onUploaded, { mediaAssetId: asset.id });
        },

        async uploadImage(file) {
            if (!file) return null;
            const form = new FormData();
            form.append('file', file);
            form.append('kind', 'image');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('/api/media/upload', {
                method: 'POST',
                body: form,
                headers: csrf ? { 'X-CSRF-TOKEN': csrf } : {},
                credentials: 'same-origin',
            });
            if (!res.ok) { alert('Image upload failed.'); return null; }
            const asset = await res.json();
            return asset.id;
        },
    };
}

// Global helper for the image picker <input type="file"> shortcut.
window.inboxUploadImage = async function (file) {
    if (!file) return null;
    const form = new FormData();
    form.append('file', file);
    form.append('kind', 'image');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const res = await fetch('/api/media/upload', {
        method: 'POST',
        body: form,
        headers: csrf ? { 'X-CSRF-TOKEN': csrf } : {},
        credentials: 'same-origin',
    });
    if (!res.ok) { alert('Image upload failed.'); return null; }
    const asset = await res.json();
    return asset.id;
};
</script>
