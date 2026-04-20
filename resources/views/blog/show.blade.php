<x-layouts.marketing
    :title="$post->meta_title"
    :description="$post->meta_description"
    :canonical="route('blog.show', $post->slug)"
>

@push('schema')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": "{{ addslashes($post->title) }}",
    "description": "{{ addslashes($post->meta_description) }}",
    "author": {
        "@type": "Organization",
        "name": "{{ addslashes($post->author) }}"
    },
    "publisher": {
        "@type": "Organization",
        "name": "One Inbox",
        "url": "https://ot1-pro.com"
    },
    "datePublished": "{{ $post->published_at->toIso8601String() }}",
    "dateModified": "{{ $post->updated_at->toIso8601String() }}",
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "{{ route('blog.show', $post->slug) }}"
    }
}
</script>
@endpush

    {{-- Breadcrumb --}}
    <div class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900/50">
        <div class="mx-auto max-w-3xl px-6 py-4">
            <nav class="flex items-center gap-2 text-sm text-zinc-500">
                <a href="{{ route('home') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('Home') }}</a>
                <span>/</span>
                <a href="{{ route('blog.index') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('Blog') }}</a>
                <span>/</span>
                <span class="text-zinc-700 dark:text-zinc-300">{{ $post->category }}</span>
            </nav>
        </div>
    </div>

    {{-- Article --}}
    <article class="py-12 lg:py-20">
        <div class="mx-auto max-w-3xl px-6">

            {{-- Header --}}
            <header class="mb-10">
                <span class="inline-block rounded-full bg-purple-100 px-3 py-1 text-xs font-medium text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">
                    {{ $post->category }}
                </span>
                <h1 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">{{ $post->title }}</h1>
                <p class="mt-5 text-lg text-zinc-600 dark:text-zinc-400">{{ $post->excerpt }}</p>
                <div class="mt-6 flex items-center gap-4 text-sm text-zinc-500">
                    <span>{{ __('By') }} <strong class="text-zinc-700 dark:text-zinc-300">{{ $post->author }}</strong></span>
                    <span>·</span>
                    <span>{{ $post->published_at->format('F j, Y') }}</span>
                    <span>·</span>
                    <span>{{ $post->reading_time }}</span>
                </div>
            </header>

            {{-- Content --}}
            <div class="prose prose-zinc max-w-none dark:prose-invert
                prose-headings:font-bold prose-headings:tracking-tight
                prose-a:text-purple-600 prose-a:no-underline hover:prose-a:underline
                prose-code:text-purple-600 prose-code:bg-purple-50 prose-code:px-1 prose-code:rounded
                dark:prose-a:text-purple-400 dark:prose-code:bg-purple-950/50 dark:prose-code:text-purple-300">
                {!! $post->content !!}
            </div>

            {{-- CTA Box --}}
            <div class="mt-16 rounded-2xl bg-gradient-to-br from-purple-600 to-blue-600 p-8 text-center text-white">
                <h2 class="text-2xl font-bold">{{ __('Ready to try One Inbox?') }}</h2>
                <p class="mt-2 text-purple-100">{{ __('Connect WhatsApp, Instagram, Facebook & Telegram with AI that sells for you.') }}</p>
                <a href="{{ route('register') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 font-semibold text-purple-700 transition-all hover:bg-purple-50">
                    {{ __('Get Started Free') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
            </div>

        </div>
    </article>

    {{-- Related Posts --}}
    @if($related->isNotEmpty())
    <section class="border-t border-zinc-200 bg-zinc-50 py-16 dark:border-zinc-800 dark:bg-zinc-900/50">
        <div class="mx-auto max-w-6xl px-6">
            <h2 class="mb-8 text-2xl font-bold">{{ __('Related articles') }}</h2>
            <div class="grid gap-6 sm:grid-cols-3">
                @foreach($related as $rel)
                <a href="{{ route('blog.show', $rel->slug) }}" class="group rounded-xl border border-zinc-200 bg-white p-5 transition-all hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-xs font-medium text-purple-600 dark:text-purple-400">{{ $rel->category }}</span>
                    <h3 class="mt-2 font-semibold leading-snug group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">{{ $rel->title }}</h3>
                    <p class="mt-2 text-xs text-zinc-500">{{ $rel->reading_time }} · {{ $rel->published_at->format('M j, Y') }}</p>
                </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

</x-layouts.marketing>
