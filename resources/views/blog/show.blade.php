<x-layouts.marketing
    :title="$post->meta_title"
    :description="$post->meta_description"
    :canonical="route('blog.show', $post->slug)"
    :htmlLang="$post->language"
    :htmlDir="$post->is_rtl ? 'rtl' : 'ltr'"
>

@push('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BlogPosting",
    "headline": {!! json_encode($post->title) !!},
    "description": {!! json_encode($post->meta_description) !!},
    "image": [{!! json_encode(config('app.url') . '/og-image.png') !!}],
    "datePublished": "{{ $post->published_at->toIso8601String() }}",
    "dateModified": "{{ $post->updated_at->toIso8601String() }}",
    "inLanguage": {!! json_encode($post->language) !!},
    "wordCount": {{ $post->wordCount() }},
    "author": {
        "@@type": "Person",
        "name": "Omar Eltak",
        "url": "{{ url('/about') }}"
    },
    "publisher": {
        "@@type": "Organization",
        "name": "OT1-Pro",
        "url": "https://ot1-pro.com",
        "logo": {
            "@@type": "ImageObject",
            "url": "https://ot1-pro.com/logo.png"
        }
    },
    "mainEntityOfPage": {
        "@@type": "WebPage",
        "@@id": "{{ route('blog.show', $post->slug) }}"
    }
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@@type": "ListItem",
            "position": 1,
            "name": "Home",
            "item": "{{ route('home') }}"
        },
        {
            "@@type": "ListItem",
            "position": 2,
            "name": "Blog",
            "item": "{{ route('blog.index') }}"
        },
        {
            "@@type": "ListItem",
            "position": 3,
            "name": {!! json_encode($post->title) !!},
            "item": "{{ route('blog.show', $post->slug) }}"
        }
    ]
}
</script>
@endpush

@push('meta')
<meta property="og:type" content="article">
<meta property="og:url" content="{{ route('blog.show', $post->slug) }}">
<meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}">
<meta property="article:modified_time" content="{{ $post->updated_at->toIso8601String() }}">
<meta property="article:author" content="Omar Eltak">
<meta name="twitter:image" content="{{ config('app.url') }}/og-image.png">
@endpush

    {{-- Reading progress bar (top of viewport) --}}
    <div class="fixed left-0 top-0 z-50 h-1 w-full bg-transparent" aria-hidden="true">
        <div id="reading-progress-bar" class="h-full w-0 bg-gradient-to-r from-indigo-500 via-blue-500 to-indigo-600 transition-[width] duration-100 ease-linear"></div>
    </div>

    {{-- Breadcrumb --}}
    <div class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-200 dark:bg-white">
        <div class="mx-auto max-w-3xl px-6 py-4">
            <nav class="flex items-center gap-2 text-sm text-zinc-500">
                <a href="{{ route('home') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('Home') }}</a>
                <span>/</span>
                <a href="{{ route('blog.index') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('Blog') }}</a>
                <span>/</span>
                <span class="text-zinc-700 dark:text-zinc-700">{{ $post->category }}</span>
            </nav>
        </div>
    </div>

    {{-- Article --}}
    <article class="py-12 lg:py-20">
        <div class="mx-auto grid max-w-6xl grid-cols-1 gap-10 px-6 lg:grid-cols-[minmax(0,1fr)_240px]">

            <div class="mx-auto w-full max-w-3xl lg:mx-0">

                {{-- Header --}}
                <header class="mb-8">
                    <span class="inline-block rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-700">
                        {{ $post->category }}
                    </span>
                    <h1 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">{{ $post->title }}</h1>
                    <p class="mt-5 text-lg text-zinc-600 dark:text-zinc-600">{{ $post->excerpt }}</p>
                    <div class="mt-6 flex items-center gap-4 text-sm text-zinc-500">
                        <span>{{ __('By') }} <strong class="text-zinc-700 dark:text-zinc-700">{{ $post->author }}</strong></span>
                        <span>·</span>
                        <span>{{ $post->published_at->format('F j, Y') }}</span>
                        <span>·</span>
                        <span>{{ $post->reading_time }}</span>
                    </div>
                </header>

                {{-- Quick answer box (featured-snippet magnet + attention hook) --}}
                <aside class="mb-10 rounded-2xl border-l-4 border-indigo-500 bg-gradient-to-br from-indigo-50 to-blue-50 p-6 shadow-sm dark:from-indigo-50 dark:to-blue-50" @if($post->is_rtl) dir="rtl" @endif>
                    <div class="flex items-start gap-3">
                        <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-white">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">{{ __('Quick answer') }}</p>
                            <p class="mt-1 text-base leading-relaxed text-zinc-800">{{ $post->excerpt }}</p>
                        </div>
                    </div>
                </aside>

                {{-- Inline TOC (mobile + desktop first pass) --}}
                <nav id="toc-inline" class="mb-10 hidden rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-200 dark:bg-white" aria-label="{{ __('Table of contents') }}" @if($post->is_rtl) dir="rtl" @endif>
                    <div class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                        {{ __('In this article') }}
                    </div>
                    <ol id="toc-inline-list" class="space-y-2 text-sm"></ol>
                </nav>

                {{-- Content --}}
                <div id="post-content" class="prose prose-zinc max-w-none dark:prose-invert
                    prose-headings:font-bold prose-headings:tracking-tight prose-headings:scroll-mt-24
                    prose-a:text-indigo-600 prose-a:no-underline hover:prose-a:underline
                    prose-code:text-indigo-600 prose-code:bg-indigo-50 prose-code:px-1 prose-code:rounded
                    dark:prose-a:text-indigo-400 dark:prose-code:bg-indigo-50/50 dark:prose-code:text-indigo-700"
                    @if($post->is_rtl) dir="rtl" @endif>
                    {!! $post->content !!}
                </div>

                {{-- CTA Box --}}
                <div class="mt-16 rounded-2xl bg-gradient-to-br from-indigo-600 to-blue-600 p-8 text-center text-white">
                    <h2 class="text-2xl font-bold">{{ __('Ready to try OT1-Pro?') }}</h2>
                    <p class="mt-2 text-indigo-100">{{ __('Connect WhatsApp, Instagram, Facebook & Telegram with AI that sells for you.') }}</p>
                    <a href="{{ route('register') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 font-semibold text-indigo-700 transition-all hover:bg-indigo-50">
                        {{ __('Get Started Free') }}
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </a>
                </div>

            </div>

            {{-- Sticky sidebar TOC (desktop only) --}}
            <aside class="hidden lg:block">
                <div class="sticky top-24">
                    <nav id="toc-sidebar" aria-label="{{ __('Table of contents') }}">
                        <div class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                            {{ __('On this page') }}
                        </div>
                        <ol id="toc-sidebar-list" class="border-l border-zinc-200 text-sm dark:border-zinc-200"></ol>
                        <div class="mt-6 rounded-lg bg-gradient-to-br from-indigo-50 to-blue-50 p-4 text-xs text-zinc-700">
                            <p class="font-semibold text-indigo-700">{{ __('Skip the Meta bureaucracy') }}</p>
                            <p class="mt-1 leading-relaxed">{{ __('Managed onboarding gets your Page live in an hour, not a month.') }}</p>
                            <a href="{{ route('register') }}" class="mt-3 inline-flex items-center gap-1 font-semibold text-indigo-600 hover:text-indigo-800">
                                {{ __('Start free →') }}
                            </a>
                        </div>
                    </nav>
                </div>
            </aside>

        </div>
    </article>

    <script>
        (function () {
            'use strict';

            // ── Reading progress bar ──────────────────────────────
            const bar = document.getElementById('reading-progress-bar');
            const article = document.getElementById('post-content');
            if (bar && article) {
                const updateProgress = () => {
                    const rect = article.getBoundingClientRect();
                    const total = article.offsetHeight - window.innerHeight;
                    const scrolled = Math.min(Math.max(-rect.top, 0), total);
                    const pct = total > 0 ? (scrolled / total) * 100 : 0;
                    bar.style.width = pct + '%';
                };
                window.addEventListener('scroll', updateProgress, { passive: true });
                window.addEventListener('resize', updateProgress, { passive: true });
                updateProgress();
            }

            // ── Auto Table of Contents (scroll-spy) ───────────────
            const inlineList = document.getElementById('toc-inline-list');
            const sidebarList = document.getElementById('toc-sidebar-list');
            const inlineNav = document.getElementById('toc-inline');
            if (!article || (!inlineList && !sidebarList)) return;

            const slugify = (text) =>
                text.toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .trim()
                    .replace(/\s+/g, '-')
                    .slice(0, 60) || 'section';

            const usedIds = new Set();
            const headings = Array.from(article.querySelectorAll('h2'));
            if (headings.length < 3) return; // don't clutter short posts

            headings.forEach((h) => {
                if (!h.id) {
                    let base = slugify(h.textContent || 'section');
                    let id = base;
                    let n = 2;
                    while (usedIds.has(id) || document.getElementById(id)) {
                        id = base + '-' + (n++);
                    }
                    h.id = id;
                }
                usedIds.add(h.id);
            });

            const buildLinks = (target, decorated) => {
                target.innerHTML = '';
                headings.forEach((h) => {
                    const li = document.createElement('li');
                    li.className = decorated
                        ? '-ml-px border-l-2 border-transparent pl-4 py-1 transition-colors'
                        : '';
                    const a = document.createElement('a');
                    a.href = '#' + h.id;
                    a.textContent = h.textContent;
                    a.className = decorated
                        ? 'block text-zinc-600 hover:text-indigo-700 transition-colors'
                        : 'block text-zinc-700 hover:text-indigo-700 transition-colors';
                    a.dataset.tocLink = h.id;
                    li.appendChild(a);
                    target.appendChild(li);
                });
            };

            if (inlineList) {
                buildLinks(inlineList, false);
                if (inlineNav) inlineNav.classList.remove('hidden');
            }
            if (sidebarList) buildLinks(sidebarList, true);

            // Scroll-spy: highlight the current section link.
            if (sidebarList && 'IntersectionObserver' in window) {
                const linkMap = new Map();
                sidebarList.querySelectorAll('a[data-toc-link]').forEach((a) => {
                    linkMap.set(a.dataset.tocLink, a.closest('li'));
                });
                const clearActive = () => {
                    linkMap.forEach((li) => {
                        li.classList.remove('border-indigo-500', 'text-indigo-700');
                        li.classList.add('border-transparent');
                        const a = li.querySelector('a');
                        if (a) a.classList.remove('font-semibold', 'text-indigo-700');
                    });
                };
                const setActive = (id) => {
                    clearActive();
                    const li = linkMap.get(id);
                    if (!li) return;
                    li.classList.remove('border-transparent');
                    li.classList.add('border-indigo-500');
                    const a = li.querySelector('a');
                    if (a) a.classList.add('font-semibold', 'text-indigo-700');
                };
                const observer = new IntersectionObserver((entries) => {
                    const visible = entries
                        .filter((e) => e.isIntersecting)
                        .sort((a, b) => a.target.getBoundingClientRect().top - b.target.getBoundingClientRect().top);
                    if (visible.length > 0) {
                        setActive(visible[0].target.id);
                    }
                }, { rootMargin: '-96px 0px -70% 0px', threshold: 0 });
                headings.forEach((h) => observer.observe(h));
            }
        })();
    </script>

    {{-- Related Posts --}}
    @if($related->isNotEmpty())
    <section class="border-t border-zinc-200 bg-zinc-50 py-16 dark:border-zinc-200 dark:bg-white">
        <div class="mx-auto max-w-6xl px-6">
            <h2 class="mb-8 text-2xl font-bold">{{ __('Related articles') }}</h2>
            <div class="grid gap-6 sm:grid-cols-3">
                @foreach($related as $rel)
                <a href="{{ route('blog.show', $rel->slug) }}" class="group rounded-xl border border-zinc-200 bg-white p-5 transition-all hover:shadow-md dark:border-zinc-200 dark:bg-white">
                    <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400">{{ $rel->category }}</span>
                    <h3 class="mt-2 font-semibold leading-snug group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ $rel->title }}</h3>
                    <p class="mt-2 text-xs text-zinc-500">{{ $rel->reading_time }} · {{ $rel->published_at->format('M j, Y') }}</p>
                </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

</x-layouts.marketing>
