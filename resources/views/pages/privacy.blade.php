<x-layouts.marketing :title="__('Privacy Policy') . ' — One Inbox'" :description="__('One Inbox Privacy Policy — how we collect, use, and protect your data.')">

    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-3xl px-6">
            <h1 class="text-4xl font-bold tracking-tight">{{ __('Privacy Policy') }}</h1>
            <p class="mt-4 text-sm text-zinc-500">{{ __('Last updated') }}: March 22, 2025</p>
            <p class="mt-2 text-sm text-zinc-500">{{ __('Effective date') }}: March 22, 2025</p>

            <div class="mt-12 space-y-10 text-zinc-600 dark:text-zinc-400">

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('1. Introduction') }}</h2>
                    <p class="mt-3">{{ __('One Inbox ("we", "us", or "our") operates a unified social media inbox platform that allows businesses to manage messages from multiple social platforms in one place. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our service at oneinbox.app.') }}</p>
                    <p class="mt-3">{{ __('By using One Inbox, you agree to the collection and use of information in accordance with this policy. If you disagree with any part of this policy, please do not use our service.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('2. Information We Collect') }}</h2>

                    <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-200 mt-5">{{ __('2.1 Account Information') }}</h3>
                    <p class="mt-2">{{ __('When you create an account, we collect your name, email address, and password (hashed). If you register via Google OAuth, we receive your Google profile name and email.') }}</p>

                    <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-200 mt-5">{{ __('2.2 Connected Platform Data') }}</h3>
                    <p class="mt-2">{{ __('When you connect a social media account, we collect and store:') }}</p>
                    <ul class="mt-2 list-disc pl-5 space-y-1 text-sm">
                        <li>{{ __('OAuth access tokens and refresh tokens required to communicate with the platform API') }}</li>
                        <li>{{ __('Your page/account name, ID, and profile information as returned by the platform') }}</li>
                        <li>{{ __('Incoming messages, conversation threads, and sender metadata (name, ID, avatar) from your connected accounts') }}</li>
                        <li>{{ __('Message timestamps, read status, and delivery receipts') }}</li>
                    </ul>
                    <p class="mt-3">{{ __('Platforms currently supported: Facebook Messenger, Instagram Direct, WhatsApp Business, Telegram, TikTok Direct Messages, and Email (IMAP/SMTP).') }}</p>

                    <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-200 mt-5">{{ __('2.3 TikTok-Specific Data') }}</h3>
                    <p class="mt-2">{{ __('When you connect your TikTok account via TikTok Login, we access the following permissions with your explicit consent: user.info.basic (display name and avatar), direct_message.read (to display incoming DMs in your inbox), and direct_message.write (to send replies on your behalf). We do not access your TikTok content, followers, analytics, or any data beyond what is required for the inbox functionality. TikTok data is used solely to display and manage your direct messages within One Inbox and is never shared with or sold to third parties.') }}</p>

                    <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-200 mt-5">{{ __('2.4 Usage Data') }}</h3>
                    <p class="mt-2">{{ __('We automatically collect certain information about how you interact with our service, including browser type, IP address, pages visited, and actions performed within the app. This data is used solely to improve service performance and reliability.') }}</p>

                    <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-200 mt-5">{{ __('2.5 AI Interaction Data') }}</h3>
                    <p class="mt-2">{{ __('When you use our AI-powered auto-reply feature, message content is sent to our AI provider (Google Gemini) to generate responses. No message content is stored by the AI provider beyond the immediate API call.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('3. How We Use Your Information') }}</h2>
                    <ul class="mt-3 list-disc pl-5 space-y-2 text-sm">
                        <li>{{ __('To provide and operate the One Inbox service, including fetching, displaying, and sending messages on your behalf') }}</li>
                        <li>{{ __('To generate AI-powered reply suggestions and auto-responses when you enable that feature') }}</li>
                        <li>{{ __('To display analytics and conversation statistics in your dashboard') }}</li>
                        <li>{{ __('To send you transactional emails (e.g., password resets, billing receipts)') }}</li>
                        <li>{{ __('To detect and prevent fraud, abuse, or violations of our Terms of Service') }}</li>
                        <li>{{ __('To improve and develop new features based on usage patterns') }}</li>
                        <li>{{ __('To comply with legal obligations') }}</li>
                    </ul>
                    <p class="mt-4 font-medium text-zinc-700 dark:text-zinc-300">{{ __('We do not sell, rent, or trade your personal data or your customers\' data to any third party for marketing purposes.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('4. Data Sharing & Third Parties') }}</h2>
                    <p class="mt-3">{{ __('We share data only in the following circumstances:') }}</p>
                    <ul class="mt-3 list-disc pl-5 space-y-2 text-sm">
                        <li><strong>{{ __('Platform APIs:') }}</strong> {{ __('Data is exchanged with Meta (Facebook, Instagram, WhatsApp), Telegram, TikTok, and email servers solely to provide the inbox functionality.') }}</li>
                        <li><strong>{{ __('AI Providers:') }}</strong> {{ __('Message content may be sent to Google Gemini for AI reply generation. This is opt-in and can be disabled.') }}</li>
                        <li><strong>{{ __('Infrastructure Providers:') }}</strong> {{ __('We use cloud infrastructure providers (servers, databases) that process data on our behalf under data processing agreements.') }}</li>
                        <li><strong>{{ __('Legal Requirements:') }}</strong> {{ __('We may disclose information if required by law, court order, or to protect the rights and safety of our users.') }}</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('5. Data Retention') }}</h2>
                    <p class="mt-3">{{ __('We retain your account data for as long as your account is active. Conversation data (messages, contacts) is retained indefinitely to provide your inbox history, but you may delete individual conversations or your entire account at any time.') }}</p>
                    <p class="mt-3">{{ __('When you disconnect a social platform or delete your account, associated access tokens are deleted immediately. Message history may be retained for up to 30 days before permanent deletion.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('6. Data Security') }}</h2>
                    <p class="mt-3">{{ __('We implement industry-standard security measures including:') }}</p>
                    <ul class="mt-2 list-disc pl-5 space-y-1 text-sm">
                        <li>{{ __('TLS/SSL encryption for all data in transit') }}</li>
                        <li>{{ __('Encrypted storage for OAuth tokens and credentials') }}</li>
                        <li>{{ __('Access controls limiting data access to authorized personnel') }}</li>
                        <li>{{ __('Regular security reviews and dependency updates') }}</li>
                    </ul>
                    <p class="mt-3">{{ __('No method of transmission over the internet is 100% secure. We strive to protect your data but cannot guarantee absolute security.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('7. Your Rights') }}</h2>
                    <p class="mt-3">{{ __('Depending on your location, you may have the following rights regarding your personal data:') }}</p>
                    <ul class="mt-3 list-disc pl-5 space-y-2 text-sm">
                        <li><strong>{{ __('Access:') }}</strong> {{ __('Request a copy of the personal data we hold about you.') }}</li>
                        <li><strong>{{ __('Rectification:') }}</strong> {{ __('Request correction of inaccurate data.') }}</li>
                        <li><strong>{{ __('Erasure:') }}</strong> {{ __('Request deletion of your personal data ("right to be forgotten").') }}</li>
                        <li><strong>{{ __('Portability:') }}</strong> {{ __('Request an export of your data in a machine-readable format.') }}</li>
                        <li><strong>{{ __('Objection:') }}</strong> {{ __('Object to processing of your data for certain purposes.') }}</li>
                        <li><strong>{{ __('Withdrawal of Consent:') }}</strong> {{ __('Disconnect any social platform at any time via your Connections settings.') }}</li>
                    </ul>
                    <p class="mt-3">{{ __('To exercise any of these rights, email us at privacy@oneinbox.app. We will respond within 30 days.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('8. Cookies') }}</h2>
                    <p class="mt-3">{{ __('We use only essential cookies required to maintain your authenticated session. We do not use advertising cookies, cross-site tracking cookies, or analytics cookies from third-party services.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __("9. Children's Privacy") }}</h2>
                    <p class="mt-3">{{ __('One Inbox is a business tool intended for users aged 18 and older. We do not knowingly collect personal information from individuals under 18. If we become aware that a minor has provided us data, we will delete it promptly.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('10. International Data Transfers') }}</h2>
                    <p class="mt-3">{{ __('Your data may be processed in countries outside your own. By using One Inbox, you consent to your data being transferred to and processed in these countries. We ensure appropriate safeguards are in place for such transfers.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('11. Changes to This Policy') }}</h2>
                    <p class="mt-3">{{ __('We may update this Privacy Policy from time to time. We will notify you of significant changes by email or by displaying a prominent notice in the app. Continued use of One Inbox after changes constitutes acceptance of the updated policy.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('12. Contact Us') }}</h2>
                    <p class="mt-3">{{ __('For privacy-related questions, data requests, or concerns, contact us at:') }}</p>
                    <div class="mt-3 text-sm">
                        <p><strong>{{ __('Email:') }}</strong> privacy@oneinbox.app</p>
                        <p class="mt-1"><strong>{{ __('Website:') }}</strong> https://oneinbox.app/privacy</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

</x-layouts.marketing>
