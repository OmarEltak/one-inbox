<x-layouts.brand-marketing
    :solidNav="true"
    :title="__('Contact OT1-Pro — Support for Your Social Media CRM')"
    :description="__('Get help with OT1-Pro — your AI-powered social media CRM. Contact our team for support, sales questions, or partnership inquiries.')">

    <section class="pt-32 pb-24 lg:pt-40 lg:pb-28">
        <div class="mx-auto max-w-4xl px-6">
            <div class="text-center">
                <h1 class="serif text-5xl leading-[1.02] text-ink lg:text-7xl">{{ __('Contact Us') }}</h1>
                <p class="mt-6 text-lg text-ink/70">
                    {{ __('Have a question or want to learn more? We\'d love to hear from you.') }}
                </p>
            </div>

            <div class="mt-16 grid gap-12 lg:grid-cols-2">
                {{-- Contact Form --}}
                <div>
                    <form action="#" method="POST" class="space-y-6">
                        @csrf
                        <div>
                            <label for="name" class="block text-sm font-medium text-ink/80">{{ __('Name') }}</label>
                            <input type="text" name="name" id="name" required
                                   class="mt-1 block w-full rounded-lg border border-line bg-cream px-4 py-2.5 text-sm shadow-sm focus:border-emer-500 focus:ring-emer-500">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-ink/80">{{ __('Email') }}</label>
                            <input type="email" name="email" id="email" required
                                   class="mt-1 block w-full rounded-lg border border-line bg-cream px-4 py-2.5 text-sm shadow-sm focus:border-emer-500 focus:ring-emer-500">
                        </div>
                        <div>
                            <label for="message" class="block text-sm font-medium text-ink/80">{{ __('Message') }}</label>
                            <textarea name="message" id="message" rows="5" required
                                      class="mt-1 block w-full rounded-lg border border-line bg-cream px-4 py-2.5 text-sm shadow-sm focus:border-emer-500 focus:ring-emer-500"></textarea>
                        </div>
                        <button type="submit" class="w-full rounded-full bg-ink px-6 py-3 text-sm font-semibold text-cream transition-colors hover:bg-ink2">
                            {{ __('Send Message') }}
                        </button>
                    </form>
                </div>

                {{-- Contact Info --}}
                <div class="space-y-8">
                    <div>
                        <h3 class="serif text-2xl leading-snug text-ink">{{ __('Email') }}</h3>
                        <p class="mt-2 text-ink/70">omareltak7@gmail.com</p>
                    </div>
                    <div>
                        <h3 class="serif text-2xl leading-snug text-ink">{{ __('Response Time') }}</h3>
                        <p class="mt-2 text-ink/70">{{ __('We typically respond within 24 hours.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════ FINAL CTA ═══════ --}}
    <section class="bg-ink text-cream py-24 grain relative overflow-hidden">
        <div class="max-w-4xl mx-auto px-6 text-center relative z-10">
            <h2 class="serif text-5xl lg:text-6xl leading-none mb-6">{{ __('Ninety seconds to') }} <span class="serif-it text-emer-400">{{ __('go live.') }}</span></h2>
            <p class="text-cream/70 text-lg mb-8 max-w-xl mx-auto">{{ __('No card. No auto-renew. Pay by transfer only when it\'s working.') }}</p>
            @if(Route::has('register'))
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-emer-500 text-ink px-7 py-4 rounded-full font-semibold text-lg hover:bg-emer-400 transition">{{ __('Start free — no card') }} <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>
            @endif
        </div>
    </section>

</x-layouts.brand-marketing>
