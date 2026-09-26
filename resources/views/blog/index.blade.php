<x-layouts.brand-marketing
    :title="__('Stories — WhatsApp, Instagram & AI Sales Guides | OT1-Pro')"
    :description="__('Practical guides on WhatsApp marketing, Instagram DM automation, AI sales bots, and social media customer service. Written by the OT1-Pro founder.')"
>

@push('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Blog",
    "@@id": "{{ route('blog.index') }}",
    "name": "OT1-Pro Blog",
    "description": "Practical guides on WhatsApp marketing, Instagram DM automation, AI sales bots, and social media customer service.",
    "url": "{{ route('blog.index') }}",
    "publisher": {
        "@@type": "Organization",
        "name": "OT1-Pro",
        "url": "https://ot1-pro.com",
        "logo": {
            "@@type": "ImageObject",
            "url": "https://ot1-pro.com/logo.png"
        }
    },
    "blogPost": [
        @foreach($posts->take(10) as $i => $post)
        {
            "@@type": "BlogPosting",
            "headline": {!! json_encode($post->title) !!},
            "url": "{{ route('blog.show', $post->slug) }}",
            "datePublished": "{{ $post->published_at->toIso8601String() }}",
            "author": {"@@type": "Organization", "name": {!! json_encode($post->author) !!}}
        }@if(! $loop->last),@endif
        @endforeach
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        {"@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}"},
        {"@@type": "ListItem", "position": 2, "name": "Blog", "item": "{{ route('blog.index') }}"}
    ]
}
</script>
@endpush

    {{-- Hero (editorial intro over cream, clears fixed nav) --}}
    <section class="bg-cream pt-32 pb-16 md:pt-40 md:pb-24 border-b border-line">
        <div class="max-w-4xl mx-auto px-6 text-center">
            <div class="text-xs uppercase tracking-[0.2em] text-ink/50 mb-6">{{ __('OT1-Pro Journal') }}</div>
            <h1 class="serif text-5xl md:text-7xl leading-[1.05] text-ink">
                {{ __('Stories from the') }} <span class="serif-it text-emer-700">{{ __('closing inbox') }}</span>.
            </h1>
            <p class="mt-8 max-w-2xl mx-auto text-lg leading-relaxed text-ink/70">
                {{ __('Practical guides on WhatsApp marketing, Instagram DM automation, AI sales, and social media customer service — written from real customer conversations, not corporate playbooks.') }}
            </p>
        </div>
    </section>

    {{-- Language filter (only when posts exist in more than one language) --}}
    @if(count($languages) > 1)
    <div class="bg-cream border-b border-line">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center gap-2 flex-wrap">
            <a href="{{ route('blog.index') }}"
               class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors {{ is_null($activeLang) ? 'bg-ink text-cream' : 'text-ink/70 hover:bg-cream2' }}">
                {{ __('All') }}
            </a>
            @foreach($languages as $lang)
            <a href="{{ route('blog.index', ['lang' => $lang]) }}"
               class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors {{ $activeLang === $lang ? 'bg-ink text-cream' : 'text-ink/70 hover:bg-cream2' }}">
                {{ match($lang) {
                    'ar' => 'Arabic',
                    'en' => 'English',
                    'fr' => 'French',
                    'es' => 'Spanish',
                    'de' => 'German',
                    default => strtoupper($lang),
                } }}
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Posts --}}
    <section class="bg-cream py-16 lg:py-24">
        <div class="max-w-6xl mx-auto px-6">

            @if($posts->isEmpty())
                <div class="py-24 text-center">
                    <p class="text-ink/60">{{ __('No articles yet. Check back soon.') }}</p>
                </div>
            @else
                {{-- Featured post (first) --}}
                @php $featured = $posts->first(); @endphp
                <a href="{{ route('blog.show', $featured->slug) }}"
                   class="group block mb-16 rounded-3xl border border-line bg-white p-8 md:p-12 transition-all hover:border-emer-500 hover:shadow-lg">
                    <div class="flex flex-wrap items-center gap-3 mb-6 text-xs uppercase tracking-[0.18em] text-emer-700">
                        <span class="font-semibold">{{ __('Featured') }}</span>
                        <span class="text-ink/30">·</span>
                        <span class="text-ink/60">{{ $featured->category }}</span>
                    </div>
                    <h2 class="serif text-3xl md:text-5xl leading-tight text-ink group-hover:text-emer-700 transition-colors">
                        {{ $featured->title }}
                    </h2>
                    <p class="mt-6 max-w-3xl text-lg leading-relaxed text-ink/70">{{ $featured->excerpt }}</p>
                    <div class="mt-8 flex items-center gap-4 text-sm text-ink/60">
                        <span class="font-medium text-ink/80">{{ $featured->author }}</span>
                        <span class="text-ink/30">·</span>
                        <span>{{ $featured->published_at->format('M j, Y') }}</span>
                        <span class="text-ink/30">·</span>
                        <span>{{ $featured->reading_time }}</span>
                    </div>
                </a>

                {{-- Recent posts grid --}}
                @if($posts->count() > 1)
                    <div class="text-xs uppercase tracking-[0.2em] text-ink/50 mb-8">{{ __('Recent stories') }}</div>
                    <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($posts->slice(1) as $post)
                        <article class="group flex flex-col">
                            <a href="{{ route('blog.show', $post->slug) }}" class="flex flex-1 flex-col">
                                <div class="text-xs uppercase tracking-[0.18em] text-emer-700 font-semibold mb-3">
                                    {{ $post->category }}
                                </div>
                                <h3 class="serif text-2xl md:text-[26px] leading-snug text-ink group-hover:text-emer-700 transition-colors">
                                    {{ $post->title }}
                                </h3>
                                <p class="mt-4 flex-1 text-[15px] leading-relaxed text-ink/70 line-clamp-3">{{ $post->excerpt }}</p>
                                <div class="mt-5 pt-4 border-t border-line flex items-center justify-between text-xs text-ink/60">
                                    <span class="font-medium text-ink/80">{{ $post->author }}</span>
                                    <div class="flex items-center gap-2">
                                        <span>{{ $post->reading_time }}</span>
                                        <span class="text-ink/30">·</span>
                                        <span>{{ $post->published_at->format('M j, Y') }}</span>
                                    </div>
                                </div>
                            </a>
                        </article>
                        @endforeach
                    </div>
                @endif

                {{-- Pagination --}}
                @if($posts->hasPages())
                <div class="mt-16">
                    {{ $posts->links() }}
                </div>
                @endif
            @endif

        </div>
    </section>

    {{-- CTA (ink surface, cream text — matches homepage closing CTA) --}}
    <section class="bg-ink text-cream py-20">
        <div class="max-w-3xl mx-auto px-6 text-center">
            <h2 class="serif text-4xl md:text-5xl leading-tight">
                {{ __('Ready to close from your') }} <span class="serif-it text-emer-400">{{ __('one inbox') }}</span>?
            </h2>
            <p class="mt-6 text-lg leading-relaxed text-cream/70">
                {{ __('Connect WhatsApp, Instagram, Facebook & Telegram in one inbox with AI that sells for you.') }}
            </p>
            <a href="{{ route('register') }}"
               class="mt-8 inline-flex items-center gap-2 rounded-full bg-emer-500 hover:bg-emer-400 px-8 py-3.5 text-sm font-semibold text-ink transition-colors">
                {{ __('Get started free') }}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </section>

</x-layouts.brand-marketing>
