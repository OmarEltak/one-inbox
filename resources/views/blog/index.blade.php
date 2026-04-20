<x-layouts.marketing
    :title="__('Blog — Social Inbox Tips, WhatsApp Marketing & AI Sales Guides | One Inbox')"
    :description="__('Practical guides on WhatsApp marketing, Instagram DM automation, AI sales bots, and social media customer service. Learn from the One Inbox team.')"
>

    {{-- Hero --}}
    <section class="border-b border-zinc-200 bg-zinc-50 py-16 dark:border-zinc-800 dark:bg-zinc-900/50">
        <div class="mx-auto max-w-6xl px-6 text-center">
            <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">{{ __('One Inbox Blog') }}</h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-zinc-600 dark:text-zinc-400">
                {{ __('Practical guides on WhatsApp marketing, Instagram DM automation, AI sales, and social media customer service.') }}
            </p>
        </div>
    </section>

    {{-- Posts Grid --}}
    <section class="py-16 lg:py-24">
        <div class="mx-auto max-w-6xl px-6">

            @if($posts->isEmpty())
                <div class="py-24 text-center">
                    <p class="text-zinc-500">{{ __('No articles yet. Check back soon.') }}</p>
                </div>
            @else
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($posts as $post)
                    <article class="group flex flex-col rounded-2xl border border-zinc-200 bg-white overflow-hidden transition-all hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        {{-- Category badge --}}
                        <div class="p-6 pb-0">
                            <span class="inline-block rounded-full bg-purple-100 px-3 py-1 text-xs font-medium text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">
                                {{ $post->category }}
                            </span>
                        </div>

                        <div class="flex flex-1 flex-col p-6">
                            <h2 class="text-lg font-semibold leading-snug group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                                <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
                            </h2>
                            <p class="mt-3 flex-1 text-sm text-zinc-600 dark:text-zinc-400 line-clamp-3">{{ $post->excerpt }}</p>

                            <div class="mt-5 flex items-center justify-between text-xs text-zinc-400">
                                <span>{{ $post->author }}</span>
                                <div class="flex items-center gap-3">
                                    <span>{{ $post->reading_time }}</span>
                                    <span>{{ $post->published_at->format('M j, Y') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-zinc-100 px-6 py-3 dark:border-zinc-800">
                            <a href="{{ route('blog.show', $post->slug) }}" class="flex items-center gap-1 text-sm font-medium text-purple-600 hover:text-purple-700 dark:text-purple-400">
                                {{ __('Read article') }}
                                <svg class="size-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                            </a>
                        </div>
                    </article>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($posts->hasPages())
                <div class="mt-12">
                    {{ $posts->links() }}
                </div>
                @endif
            @endif

        </div>
    </section>

    {{-- CTA --}}
    <section class="border-t border-zinc-200 bg-zinc-50 py-16 dark:border-zinc-800 dark:bg-zinc-900/50">
        <div class="mx-auto max-w-2xl px-6 text-center">
            <h2 class="text-2xl font-bold">{{ __('Ready to automate your social inbox?') }}</h2>
            <p class="mt-3 text-zinc-600 dark:text-zinc-400">{{ __('Connect WhatsApp, Instagram, Facebook & Telegram in one inbox with AI that sells for you.') }}</p>
            <a href="{{ route('register') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-purple-600 px-8 py-3.5 font-semibold text-white shadow-lg shadow-purple-500/25 transition-all hover:bg-purple-700">
                {{ __('Get Started Free') }}
            </a>
        </div>
    </section>

</x-layouts.marketing>
