<x-layouts.marketing
    :title="__('Contact One Inbox — Support for Your Social Media CRM')"
    :description="__('Get help with One Inbox — your AI-powered social media CRM. Contact our team for support, sales questions, or partnership inquiries.')">

    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-4xl px-6">
            <div class="text-center">
                <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">{{ __('Contact Us') }}</h1>
                <p class="mt-6 text-lg text-zinc-600 dark:text-zinc-400">
                    {{ __('Have a question or want to learn more? We\'d love to hear from you.') }}
                </p>
            </div>

            <div class="mt-16 grid gap-12 lg:grid-cols-2">
                {{-- Contact Form --}}
                <div>
                    <form action="#" method="POST" class="space-y-6">
                        @csrf
                        <div>
                            <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Name') }}</label>
                            <input type="text" name="name" id="name" required
                                   class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-4 py-2.5 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Email') }}</label>
                            <input type="email" name="email" id="email" required
                                   class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-4 py-2.5 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                        </div>
                        <div>
                            <label for="message" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Message') }}</label>
                            <textarea name="message" id="message" rows="5" required
                                      class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-4 py-2.5 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
                        </div>
                        <button type="submit" class="w-full rounded-lg bg-purple-600 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-purple-700">
                            {{ __('Send Message') }}
                        </button>
                    </form>
                </div>

                {{-- Contact Info --}}
                <div class="space-y-8">
                    <div>
                        <h3 class="text-lg font-semibold">{{ __('Email') }}</h3>
                        <p class="mt-2 text-zinc-600 dark:text-zinc-400">support@oneinbox.app</p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">{{ __('Response Time') }}</h3>
                        <p class="mt-2 text-zinc-600 dark:text-zinc-400">{{ __('We typically respond within 24 hours.') }}</p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">{{ __('Social') }}</h3>
                        <div class="mt-2 flex gap-4 text-zinc-500">
                            <span>Twitter</span>
                            <span>LinkedIn</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</x-layouts.marketing>
