<x-layouts.marketing :title="__('Cookie Notice') . ' — OT1-Pro'" :description="__('OT1-Pro Cookie Notice — how we use cookies to provide a better experience.')">

    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-3xl px-6">
            <h1 class="text-4xl font-bold tracking-tight">{{ __('Cookie Notice') }}</h1>
            <p class="mt-4 text-sm text-zinc-500">{{ __('Last updated') }}: October 2, 2026</p>
            <p class="mt-2 text-sm text-zinc-500">{{ __('Effective date') }}: October 2, 2026</p>

            <div class="mt-12 space-y-10 text-zinc-600 dark:text-zinc-600">

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('1. What are Cookies?') }}</h2>
                    <p class="mt-3">{{ __('Cookies are small text files placed on your device by websites you visit. They are widely used to make websites work, or work more efficiently, as well as to provide information to the owners of the site.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('2. How OT1-Pro Uses Cookies') }}</h2>
                    <p class="mt-3">{{ __('We use only essential cookies required to maintain your authenticated session and core platform functionality. This includes:') }}</p>
                    <ul class="mt-3 list-disc pl-5 space-y-2 text-sm">
                        <li><strong>{{ __('Session Cookies:') }}</strong> {{ __('To keep you logged in as you navigate between different pages of the app.') }}</li>
                        <li><strong>{{ __('Security Cookies:') }}</strong> {{ __('To protect your account and our platform from fraudulent activity and unauthorized access.') }}</li>
                        <li><strong>{{ __('Preference Cookies:') }}</strong> {{ __('To remember your basic UI settings (e.g., language preference).') }}</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('3. Third-Party Cookies') }}</h2>
                    <p class="mt-3">{{ __('OT1-Pro does not use third-party tracking cookies, advertising cookies, or cross-site tracking pixels. We do not sell your browsing data to advertisers.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('4. Managing Your Cookies') }}</h2>
                    <p class="mt-3">{{ __('You can control and manage cookies through your browser settings. Most browsers allow you to block cookies or delete them. However, blocking essential session cookies will prevent you from using the OT1-Pro platform.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('5. Contact Us') }}</h2>
                    <p class="mt-3">{{ __('For more information about our data practices, please refer to our') }} <a href="{{ route('privacy') }}" class="text-indigo-600 hover:underline">{{ __('Privacy Policy') }}</a>{{ __('.') }}</p>
                </div>

            </div>
        </div>
    </section>

</x-layouts.marketing>
