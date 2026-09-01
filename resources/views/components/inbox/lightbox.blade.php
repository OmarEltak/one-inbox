<div
    x-data="{ open: false, url: '', alt: '' }"
    @inbox-lightbox.window="open = true; url = $event.detail.url; alt = $event.detail.alt"
    @keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    x-transition.opacity
    class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4"
    @click.self="open = false"
    style="display: none;"
>
    <button type="button" @click="open = false"
        class="absolute top-4 right-4 text-white/80 hover:text-white text-3xl leading-none">&times;</button>
    <img :src="url" :alt="alt" class="max-h-[90vh] max-w-[90vw] object-contain rounded shadow-2xl" />
</div>
