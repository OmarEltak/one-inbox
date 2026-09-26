<x-layouts.brand-marketing
    :title="$post->meta_title"
    :description="$post->meta_description"
    :canonical="route('blog.show', $post->slug)"
    :htmlLang="$post->language"
    :htmlDir="$post->is_rtl ? 'rtl' : 'ltr'"
    :solidNav="true"
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

@push('head')
<style>
    /* Article prose overrides: serif headings, emerald accents on cream. */
    .brand-prose {
        font-family: 'Inter', system-ui, sans-serif;
        color: #0a1f1c;
        font-size: 1.125rem;      /* 18px */
        line-height: 1.75;
    }
    .brand-prose > * + * { margin-top: 1.35em; }
    .brand-prose h2 {
        font-family: 'Instrument Serif', Georgia, serif;
        font-weight: 400;
        letter-spacing: -0.02em;
        font-size: 2.25rem;
        line-height: 1.15;
        margin-top: 2.5em;
        margin-bottom: 0.6em;
        scroll-margin-top: 6rem;
        color: #0a1f1c;
    }
    .brand-prose h3 {
        font-family: 'Instrument Serif', Georgia, serif;
        font-weight: 400;
        letter-spacing: -0.015em;
        font-size: 1.6rem;
        line-height: 1.25;
        margin-top: 2em;
        margin-bottom: 0.5em;
        color: #0a1f1c;
    }
    .brand-prose h4 { font-weight: 600; font-size: 1.15rem; margin-top: 1.8em; margin-bottom: 0.5em; color: #0a1f1c; }
    .brand-prose p, .brand-prose li { color: rgba(10,31,28,0.82); }
    .brand-prose a {
        color: #047857;
        text-decoration: none;
        border-bottom: 1px solid rgba(4,120,87,0.35);
        transition: border-color 0.2s, color 0.2s;
    }
    .brand-prose a:hover { color: #064e3b; border-bottom-color: #064e3b; }
    .brand-prose strong { color: #0a1f1c; font-weight: 600; }
    .brand-prose ul, .brand-prose ol { padding-left: 1.5em; }
    .brand-prose ul { list-style: disc; }
    .brand-prose ol { list-style: decimal; }
    .brand-prose li + li { margin-top: 0.5em; }
    .brand-prose blockquote {
        border-left: 3px solid #10b981;
        padding: 0.25em 1.25em;
        margin-left: 0;
        font-style: italic;
        color: rgba(10,31,28,0.72);
    }
    .brand-prose code {
        background: #ecfdf5;
        color: #047857;
        padding: 0.15em 0.4em;
        border-radius: 4px;
        font-size: 0.92em;
    }
    .brand-prose pre {
        background: #0a1f1c;
        color: #faf7f2;
        padding: 1.25em;
        border-radius: 12px;
        overflow-x: auto;
        font-size: 0.9em;
        line-height: 1.6;
    }
    .brand-prose pre code { background: transparent; color: inherit; padding: 0; }
    .brand-prose table {
        width: 100%;
        border-collapse: collapse;
        margin: 1.5em 0;
        font-size: 0.95em;
    }
    .brand-prose th, .brand-prose td {
        border-bottom: 1px solid #e6dfd0;
        padding: 0.75em 1em;
        text-align: left;
    }
    .brand-prose th { font-weight: 600; color: #0a1f1c; background: #faf7f2; }
    .brand-prose img { border-radius: 12px; }
    .brand-prose hr { border: 0; border-top: 1px solid #e6dfd0; margin: 3em 0; }
    [dir="rtl"] .brand-prose ul,
    [dir="rtl"] .brand-prose ol { padding-left: 0; padding-right: 1.5em; }
    [dir="rtl"] .brand-prose blockquote { border-left: 0; border-right: 3px solid #10b981; }
</style>
@endpush

    {{-- Reading progress bar (top of viewport) --}}
    <div class="fixed left-0 top-0 z-[60] h-[3px] w-full bg-transparent" aria-hidden="true">
        <div id="reading-progress-bar" class="h-full w-0 bg-emer-500 transition-[width] duration-100 ease-linear"></div>
    </div>

    {{-- Breadcrumb (clears fixed nav) --}}
    <div class="bg-cream pt-28 md:pt-32">
        <div class="max-w-3xl mx-auto px-6 pb-4">
            <nav class="flex items-center gap-2 text-sm text-ink/60">
                <a href="{{ route('home') }}" class="u-link hover:text-ink">{{ __('Home') }}</a>
                <span class="text-ink/30">/</span>
                <a href="{{ route('blog.index') }}" class="u-link hover:text-ink">{{ __('Stories') }}</a>
                <span class="text-ink/30">/</span>
                <span class="text-ink/80">{{ $post->category }}</span>
            </nav>
        </div>
    </div>

    {{-- Article --}}
    <article class="bg-cream pb-16 lg:pb-24">
        <div class="mx-auto grid max-w-6xl grid-cols-1 gap-10 px-6 lg:grid-cols-[minmax(0,1fr)_240px]">

            <div class="mx-auto w-full max-w-2xl lg:mx-0">

                {{-- Header --}}
                <header class="mb-10 pt-4">
                    <div class="text-xs uppercase tracking-[0.2em] text-emer-700 font-semibold mb-5">
                        {{ $post->category }}
                    </div>
                    <h1 class="serif text-4xl md:text-5xl lg:text-6xl leading-[1.08] text-ink">{{ $post->title }}</h1>
                    <div class="mt-8 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-ink/60">
                        <span>{{ __('By') }} <strong class="font-semibold text-ink">{{ $post->author }}</strong></span>
                        <span class="text-ink/30">·</span>
                        <span>{{ $post->published_at->format('F j, Y') }}</span>
                        <span class="text-ink/30">·</span>
                        <span>{{ $post->reading_time }}</span>
                    </div>
                </header>

                {{-- Quick answer box (featured-snippet magnet + attention hook) --}}
                <aside class="mb-12 rounded-2xl border border-line bg-white p-6 md:p-7 relative overflow-hidden" @if($post->is_rtl) dir="rtl" @endif>
                    <div class="absolute top-0 bottom-0 {{ $post->is_rtl ? 'right-0' : 'left-0' }} w-1 bg-emer-500"></div>
                    <div class="flex items-start gap-3">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-emer-100">
                            <svg class="size-4 text-emer-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emer-700">{{ __('Quick answer') }}</p>
                            <p class="mt-2 text-[17px] leading-relaxed text-ink/80">{{ $post->excerpt }}</p>
                        </div>
                    </div>
                </aside>

                {{-- Inline TOC (mobile-first, revealed by JS when 3+ H2s exist) --}}
                <nav id="toc-inline" class="mb-12 hidden rounded-xl border border-line bg-white p-5" aria-label="{{ __('Table of contents') }}" @if($post->is_rtl) dir="rtl" @endif>
                    <div class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-ink/60">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                        {{ __('In this article') }}
                    </div>
                    <ol id="toc-inline-list" class="space-y-2 text-sm"></ol>
                </nav>

                {{-- Content --}}
                <div id="post-content" class="brand-prose" @if($post->is_rtl) dir="rtl" @endif>
                    {!! $post->content !!}
                </div>

                {{-- CTA (ink surface — matches homepage closing CTA) --}}
                <div class="mt-16 rounded-3xl bg-ink text-cream p-8 md:p-10 text-center">
                    <h2 class="serif text-3xl md:text-4xl leading-tight">
                        {{ __('Ready to try') }} <span class="serif-it text-emer-400">OT1-Pro</span>?
                    </h2>
                    <p class="mt-4 text-cream/70 leading-relaxed">{{ __('Connect WhatsApp, Instagram, Facebook & Telegram with AI that sells for you.') }}</p>
                    <a href="{{ route('register') }}"
                       class="mt-6 inline-flex items-center gap-2 rounded-full bg-emer-500 hover:bg-emer-400 px-7 py-3 text-sm font-semibold text-ink transition-colors">
                        {{ __('Get started free') }}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

            </div>

            {{-- Sticky sidebar TOC (desktop only) --}}
            <aside class="hidden lg:block">
                <div class="sticky top-28">
                    <nav id="toc-sidebar" aria-label="{{ __('Table of contents') }}">
                        <div class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-ink/60">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                            {{ __('On this page') }}
                        </div>
                        <ol id="toc-sidebar-list" class="border-l border-line text-sm"></ol>
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
                        ? 'block text-ink/70 hover:text-emer-700 transition-colors'
                        : 'block text-ink/80 hover:text-emer-700 transition-colors';
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
                        li.classList.remove('text-emer-700');
                        li.classList.add('border-transparent');
                        li.style.borderLeftColor = '';
                        const a = li.querySelector('a');
                        if (a) a.classList.remove('font-semibold', 'text-emer-700');
                    });
                };
                const setActive = (id) => {
                    clearActive();
                    const li = linkMap.get(id);
                    if (!li) return;
                    li.classList.remove('border-transparent');
                    li.style.borderLeftColor = '#059669';
                    const a = li.querySelector('a');
                    if (a) a.classList.add('font-semibold', 'text-emer-700');
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

    {{-- Related Posts (PRIORITY_SLUGS + category picks from BlogController::show) --}}
    @if($related->isNotEmpty())
    <section class="border-t border-line bg-cream2 py-16 lg:py-20">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-xs uppercase tracking-[0.2em] text-ink/50 mb-3">{{ __('Keep reading') }}</div>
            <h2 class="serif text-3xl md:text-4xl leading-tight text-ink mb-10">{{ __('Related stories') }}</h2>
            <div class="grid gap-8 sm:grid-cols-3">
                @foreach($related as $rel)
                <a href="{{ route('blog.show', $rel->slug) }}" class="group flex flex-col">
                    <div class="text-xs uppercase tracking-[0.18em] text-emer-700 font-semibold mb-3">{{ $rel->category }}</div>
                    <h3 class="serif text-xl md:text-2xl leading-snug text-ink group-hover:text-emer-700 transition-colors">{{ $rel->title }}</h3>
                    <p class="mt-4 pt-4 border-t border-line text-xs text-ink/60">{{ $rel->reading_time }} · {{ $rel->published_at->format('M j, Y') }}</p>
                </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

</x-layouts.brand-marketing>
