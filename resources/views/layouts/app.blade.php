<x-layouts::app.sidebar :title="$title ?? null">
    @if($fullWidth ?? false)
        <div class="[grid-area:main]" data-flux-main style="padding:0; height:100dvh; overflow:hidden;">
            {{ $slot }}
        </div>
    @else
        <flux:main>
            {{ $slot }}
        </flux:main>
    @endif
</x-layouts::app.sidebar>
