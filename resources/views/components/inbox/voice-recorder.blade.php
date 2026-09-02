@props(['onUploaded'])

{{--
    Pure vanilla-JS voice recorder. Same reasoning as audio-player.blade.php:
    Alpine's expression evaluator runs before inline scripts re-execute after a
    Livewire morph, so `x-data="voiceRecorder(...)"` reliably explodes with
    'voiceRecorder is not defined' the moment the composer re-renders. The
    mic button then stays invisible because :class evaluates to undefined and
    Alpine falls back to no classes (button has no visible color).

    Fix: no Alpine on this component. Plain <button> + data-attrs. A single
    delegated click handler + MutationObserver, installed ONCE per page load
    via a window flag, drives the recording lifecycle. State is stored per-
    button on a dataset so morphs don't confuse two adjacent recorders.
--}}

<div class="flex-shrink-0 self-end mb-1">
    <button type="button"
            data-vr-toggle
            data-vr-uploaded="{{ $onUploaded }}"
            data-vr-state="idle"
            title="{{ __('Record voice note') }}"
            class="p-1 cursor-pointer text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors">
        {{-- Mic icon: rendered server-side by Flux so no Alpine binding.
             The JS toggles color classes on state changes below. --}}
        <flux:icon.microphone class="h-5 w-5" />
    </button>
</div>

@push('scripts')
@once
<script>
(function () {
    if (window.__inboxVoiceRecorderInstalled) return;
    window.__inboxVoiceRecorderInstalled = true;

    // Per-button recording state, keyed by an auto-assigned id.
    var state = new WeakMap();

    function setState(btn, next) {
        var s = state.get(btn) || {};
        Object.assign(s, next);
        state.set(btn, s);
        applyVisualState(btn, s);
    }

    function applyVisualState(btn, s) {
        btn.dataset.vrState = s.recording ? 'recording' : (s.uploading ? 'uploading' : 'idle');

        // Reset all state classes first.
        btn.classList.remove(
            'text-zinc-400', 'hover:text-zinc-600', 'dark:hover:text-zinc-300',
            'text-red-500', 'animate-pulse',
            'text-zinc-300'
        );

        if (s.recording) {
            btn.classList.add('text-red-500', 'animate-pulse');
            btn.title = 'Stop & send';
            btn.disabled = false;
        } else if (s.uploading) {
            btn.classList.add('text-zinc-300');
            btn.title = 'Sending…';
            btn.disabled = true;
        } else {
            btn.classList.add('text-zinc-400', 'hover:text-zinc-600', 'dark:hover:text-zinc-300');
            btn.title = 'Record voice note';
            btn.disabled = false;
        }
    }

    function releaseStream(s) {
        if (s.stream) {
            s.stream.getTracks().forEach(function (t) { t.stop(); });
            s.stream = null;
        }
    }

    async function startRecording(btn) {
        try {
            var stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            var mr = new MediaRecorder(stream, { mimeType: 'audio/webm;codecs=opus' });
            var chunks = [];

            mr.ondataavailable = function (e) { chunks.push(e.data); };
            mr.onstop = function () { upload(btn, chunks); };

            setState(btn, {
                recording: true, uploading: false,
                mediaRecorder: mr, stream: stream, chunks: chunks,
            });
            mr.start();
        } catch (e) {
            console.error('[voice-recorder] getUserMedia failed', e);
            alert('Microphone access denied.');
        }
    }

    function stopRecording(btn) {
        var s = state.get(btn);
        if (!s || !s.recording) return;
        setState(btn, { recording: false });
        if (s.mediaRecorder && s.mediaRecorder.state !== 'inactive') {
            s.mediaRecorder.stop(); // fires onstop → upload()
        }
    }

    async function upload(btn, chunks) {
        var s = state.get(btn) || {};
        releaseStream(s);

        if (!chunks || chunks.length === 0) {
            setState(btn, { uploading: false });
            return;
        }

        setState(btn, { uploading: true });

        try {
            var blob = new Blob(chunks, { type: 'audio/webm' });
            var form = new FormData();
            form.append('file', blob, 'voice.webm');
            form.append('kind', 'audio');

            var csrf = document.querySelector('meta[name="csrf-token"]');
            var headers = csrf ? { 'X-CSRF-TOKEN': csrf.content } : {};

            var res = await fetch('/api/media/upload', {
                method: 'POST',
                body: form,
                headers: headers,
                credentials: 'same-origin',
            });

            if (!res.ok) {
                alert('Voice note upload failed.');
                return;
            }

            var asset = await res.json();
            var wireMethod = btn.getAttribute('data-vr-uploaded') || 'sendWithMedia';

            if (window.Livewire && typeof window.Livewire.dispatch === 'function') {
                window.Livewire.dispatch(wireMethod, { mediaAssetId: asset.id });
            } else {
                console.error('[voice-recorder] Livewire not available');
            }
        } catch (e) {
            console.error('[voice-recorder] upload failed', e);
            alert('Voice note upload failed.');
        } finally {
            setState(btn, { uploading: false, chunks: [] });
        }
    }

    // Delegated click — works for buttons that appear after page load.
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest('[data-vr-toggle]');
        if (!btn) return;
        ev.preventDefault();

        var s = state.get(btn) || {};
        if (s.uploading) return;
        if (s.recording) {
            stopRecording(btn);
        } else {
            startRecording(btn);
        }
    });

    // Ensure any recorder buttons already on the page start in the visible
    // idle state (in case classes got stripped by a previous Alpine failure).
    function initButton(btn) {
        if (btn.__vrInit) return;
        btn.__vrInit = true;
        applyVisualState(btn, {});
    }

    var mo = new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
            m.addedNodes.forEach(function (node) {
                if (node.nodeType !== 1) return;
                if (node.matches && node.matches('[data-vr-toggle]')) initButton(node);
                if (node.querySelectorAll) {
                    node.querySelectorAll('[data-vr-toggle]').forEach(initButton);
                }
            });
        });
    });
    mo.observe(document.body, { childList: true, subtree: true });

    document.querySelectorAll('[data-vr-toggle]').forEach(initButton);
})();
</script>
@endonce
@endpush
