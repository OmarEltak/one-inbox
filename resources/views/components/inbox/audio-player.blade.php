@props(['src', 'mime', 'sentAt' => null])

{{--
    Pure vanilla-JS audio player. No Alpine involvement — Alpine's expression
    evaluator runs BEFORE inline scripts are re-executed after a Livewire
    morph, so any `x-data="audioPlayer()"` invocation would blow up with
    "audioPlayer is not defined" for every new bubble. This version avoids
    the problem entirely:

      - The <audio> element is the source of truth (native Range + codec
        handling, seek, play/pause).
      - The custom UI (round play button, waveform bars, timer) is regular
        HTML with data-* attributes.
      - A single delegated click handler + one MutationObserver, installed
        ONCE per page load via a window flag, wires new players up as they
        appear in the DOM. No script re-execution needed on morph.

    Every player is self-contained via a unique id — the JS finds the
    matching <audio> by that id and updates the bar / timer children.
--}}

@php
    // Stable pseudo-waveform: heights hashed from URL so same asset always
    // renders same pattern, cheap visual variety without touching audio bytes.
    $seed = crc32((string) $src);
    $bars = [];
    for ($i = 0; $i < 32; $i++) {
        $seed = (int) (($seed * 1103515245 + 12345) % 2147483648);
        $bars[] = 0.35 + (($seed % 100) / 100) * 0.65;
    }
    $uid = 'ap_' . substr(md5($src), 0, 12);
@endphp

<div class="inbox-audio-player flex items-center gap-3 py-1 min-w-[220px] max-w-[320px]"
     data-player-id="{{ $uid }}">
    {{-- Hidden real <audio>. Browser handles Range, codec sniffing, playback. --}}
    <audio id="{{ $uid }}-audio" preload="metadata" class="hidden">
        <source src="{{ $src }}" @if($mime) type="{{ $mime }}" @endif />
    </audio>

    {{-- Play / pause button. `data-ap-toggle` marks it for the delegated
         click handler. The <svg> path is swapped by JS on state change. --}}
    <button type="button"
            data-ap-toggle="{{ $uid }}"
            aria-label="Play"
            class="flex-shrink-0 grid place-items-center h-9 w-9 rounded-full bg-white/90 hover:bg-white text-zinc-800 shadow-sm cursor-pointer transition-colors">
        <svg id="{{ $uid }}-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
             fill="currentColor" class="h-4 w-4 translate-x-[1px]">
            <path d="M8 5v14l11-7z" />
        </svg>
    </button>

    <div class="flex-1 flex flex-col gap-1 min-w-0">
        {{-- Waveform track. Click anywhere to seek. --}}
        <div id="{{ $uid }}-track"
             data-ap-track="{{ $uid }}"
             class="relative h-6 cursor-pointer">
            <div class="absolute inset-0 flex items-center gap-[2px]">
                @foreach($bars as $i => $h)
                    <div class="ap-bar flex-1 rounded-full bg-current opacity-40 transition-opacity"
                         data-ap-bar-index="{{ $i }}"
                         style="height: {{ round($h * 100) }}%;"></div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-between text-[10px] font-mono tabular-nums opacity-75">
            <span id="{{ $uid }}-time" data-ap-time="{{ $uid }}">0:00</span>
            @if($sentAt)
                <span>{{ $sentAt }}</span>
            @endif
        </div>
    </div>
</div>

{{-- Install the handler ONCE per page load. Guarded by a window flag so
     re-executions (Livewire morphs re-injecting this script tag) don't
     stack multiple listeners. Actual installer lives in
     resources/views/layouts/app/sidebar.blade.php (loaded once per
     authenticated page, immune to Livewire morphs). Local copy kept
     behind @if(false) as documentation. --}}
@if(false)
<script>
(function () {
    if (window.__inboxAudioPlayerInstalled) return;
    window.__inboxAudioPlayerInstalled = true;

    function fmt(sec) {
        if (!sec || !isFinite(sec)) return '0:00';
        var m = Math.floor(sec / 60);
        var s = Math.floor(sec % 60);
        return m + ':' + (s < 10 ? '0' + s : s);
    }

    function playIcon(el, playing) {
        var svg = el;
        if (!svg) return;
        var path = svg.querySelector('path');
        if (!path) return;
        if (playing) {
            path.setAttribute('d', 'M6 5h4v14H6zM14 5h4v14h-4z');
            svg.classList.remove('translate-x-[1px]');
        } else {
            path.setAttribute('d', 'M8 5v14l11-7z');
            svg.classList.add('translate-x-[1px]');
        }
    }

    function updateBars(playerEl, progress) {
        // 32 bars, opacity 100 up to progress, 40 after.
        var bars = playerEl.querySelectorAll('.ap-bar');
        bars.forEach(function (bar, i) {
            var threshold = i / bars.length;
            if (progress > threshold) {
                bar.classList.remove('opacity-40');
                bar.classList.add('opacity-100');
            } else {
                bar.classList.remove('opacity-100');
                bar.classList.add('opacity-40');
            }
        });
    }

    function wirePlayer(playerEl) {
        if (playerEl.__apWired) return;
        playerEl.__apWired = true;

        var uid   = playerEl.getAttribute('data-player-id');
        var audio = document.getElementById(uid + '-audio');
        var icon  = document.getElementById(uid + '-icon');
        var time  = document.getElementById(uid + '-time');
        var track = document.getElementById(uid + '-track');
        if (!audio) return;

        audio.addEventListener('play',  function () { playIcon(icon, true); });
        audio.addEventListener('pause', function () { playIcon(icon, false); });
        audio.addEventListener('ended', function () {
            playIcon(icon, false);
            if (time) time.textContent = fmt(audio.duration);
            updateBars(playerEl, 1);
        });
        audio.addEventListener('loadedmetadata', function () {
            if (time && audio.paused) time.textContent = fmt(audio.duration);
        });
        audio.addEventListener('timeupdate', function () {
            if (time) time.textContent = fmt(audio.currentTime);
            var prog = audio.duration > 0 ? audio.currentTime / audio.duration : 0;
            updateBars(playerEl, prog);
        });

        if (track) {
            track.addEventListener('click', function (ev) {
                if (!audio.duration) return;
                var r = track.getBoundingClientRect();
                var ratio = Math.max(0, Math.min(1, (ev.clientX - r.left) / r.width));
                audio.currentTime = ratio * audio.duration;
            });
        }
    }

    // Delegated play/pause click — works even for players Livewire injects later.
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest('[data-ap-toggle]');
        if (!btn) return;
        ev.preventDefault();

        var uid   = btn.getAttribute('data-ap-toggle');
        var audio = document.getElementById(uid + '-audio');
        if (!audio) return;

        // Ensure the containing player has its listeners wired.
        var playerEl = btn.closest('.inbox-audio-player');
        if (playerEl) wirePlayer(playerEl);

        if (audio.paused) {
            // WhatsApp behavior — pause every other audio on page.
            document.querySelectorAll('audio').forEach(function (a) {
                if (a !== audio) a.pause();
            });
            audio.play().catch(function (e) {
                console.error('[audio-player] play failed', e, audio.currentSrc);
            });
        } else {
            audio.pause();
        }
    });

    // Auto-wire any players that appear later via Livewire morph or wire:navigate.
    var mo = new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
            m.addedNodes.forEach(function (node) {
                if (node.nodeType !== 1) return;
                if (node.matches && node.matches('.inbox-audio-player')) {
                    wirePlayer(node);
                }
                if (node.querySelectorAll) {
                    node.querySelectorAll('.inbox-audio-player').forEach(wirePlayer);
                }
            });
        });
    });
    mo.observe(document.body, { childList: true, subtree: true });

    // Wire whatever's already on the page at install time.
    document.querySelectorAll('.inbox-audio-player').forEach(wirePlayer);
})();
</script>
@endif
