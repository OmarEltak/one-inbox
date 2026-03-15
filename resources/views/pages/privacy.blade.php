<x-layouts.marketing :title="__('Privacy Policy') . ' — One Inbox'" :description="__('One Inbox Privacy Policy — how we collect, use, and protect your data.')">

    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-3xl px-6">
            <h1 class="text-4xl font-bold tracking-tight">{{ __('Privacy Policy') }}</h1>
            <p class="mt-4 text-sm text-zinc-500">{{ __('Last updated') }}: {{ date('F j, Y') }}</p>

            <div class="mt-12 space-y-8 text-zinc-600 dark:text-zinc-400">
                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('1. Information We Collect') }}</h2>
                    <p class="mt-3">{{ __('We collect information you provide directly, such as your name, email address, and the social media accounts you connect. We also collect conversation data from your connected platforms to provide our inbox and AI services.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('2. How We Use Your Information') }}</h2>
                    <p class="mt-3">{{ __('We use your information to provide and improve our services, including managing your unified inbox, powering AI responses, scoring leads, and generating analytics. We do not sell your personal data to third parties.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('3. Data Security') }}</h2>
                    <p class="mt-3">{{ __('We implement industry-standard security measures to protect your data, including encryption in transit and at rest. Access to your data is restricted to authorized personnel only.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('4. Third-Party Services') }}</h2>
                    <p class="mt-3">{{ __('We integrate with Meta (Facebook, Instagram, WhatsApp), Telegram, and AI providers to deliver our services. Each integration is governed by the respective platform\'s terms and privacy policies.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('5. Your Rights') }}</h2>
                    <p class="mt-3">{{ __('You have the right to access, update, or delete your personal data at any time. You can also disconnect your social accounts and export your data. Contact us for any data-related requests.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('6. Cookies') }}</h2>
                    <p class="mt-3">{{ __('We use essential cookies to maintain your session and preferences. We do not use third-party tracking cookies.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('7. Contact') }}</h2>
                    <p class="mt-3">{{ __('For privacy-related inquiries, contact us at privacy@oneinbox.app.') }}</p>
                </div>
            </div>
        </div>
    </section>

</x-layouts.marketing>
