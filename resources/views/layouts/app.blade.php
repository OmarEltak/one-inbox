<x-layouts::app.sidebar :title="$title ?? null">
    @if($fullWidth ?? false)
        {{-- Full-height pages (inbox, AI chat) fill the viewport BELOW the top bar.
             A plain 100dvh stacked under the 56px header pushed the composer
             off-screen, so the page had to be scrolled to reach the input. --}}
        <div class="[grid-area:main]" data-flux-main data-full-height-main style="padding:0; height:calc(100dvh - var(--app-chrome-h, 3.5rem)); overflow:hidden;">
            {{ $slot }}
        </div>
        <script>
            (function () {
                // Measure what sits above the main area (header, quota banner).
                var fit = function () {
                    var main = document.querySelector('[data-full-height-main]');
                    if (!main) return;
                    var top = main.getBoundingClientRect().top + window.scrollY;
                    document.documentElement.style.setProperty('--app-chrome-h', Math.max(0, Math.round(top)) + 'px');
                };
                fit();
                if (!window.__fullHeightMainInstalled) {
                    window.__fullHeightMainInstalled = true;
                    window.addEventListener('resize', fit);
                    document.addEventListener('livewire:navigated', fit);
                }
            })();
        </script>
    @else
        <flux:main>
            {{ $slot }}
        </flux:main>
    @endif
</x-layouts::app.sidebar>
