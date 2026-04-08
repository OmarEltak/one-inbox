<x-layouts.marketing :title="__('Terms of Service') . ' — One Inbox'" :description="__('One Inbox Terms of Service — the rules and guidelines for using our platform.')">

    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-3xl px-6">
            <h1 class="text-4xl font-bold tracking-tight">{{ __('Terms of Service') }}</h1>
            <p class="mt-4 text-sm text-zinc-500">{{ __('Last updated') }}: March 22, 2025</p>
            <p class="mt-2 text-sm text-zinc-500">{{ __('Effective date') }}: March 22, 2025</p>

            <div class="mt-12 space-y-10 text-zinc-600 dark:text-zinc-400">

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('1. Acceptance of Terms') }}</h2>
                    <p class="mt-3">{{ __('By accessing or using One Inbox ("Service", "Platform"), you ("User", "Customer") agree to be bound by these Terms of Service ("Terms") and our Privacy Policy. If you are using One Inbox on behalf of a business, you represent that you have the authority to bind that business to these Terms.') }}</p>
                    <p class="mt-3">{{ __('If you do not agree to these Terms, do not access or use the Service.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('2. Description of Service') }}</h2>
                    <p class="mt-3">{{ __('One Inbox provides a unified social media inbox for businesses, enabling them to:') }}</p>
                    <ul class="mt-2 list-disc pl-5 space-y-1 text-sm">
                        <li>{{ __('Aggregate and manage messages from connected social media platforms (Facebook Messenger, Instagram Direct, WhatsApp Business, Telegram, TikTok Direct Messages, Email) in a single interface') }}</li>
                        <li>{{ __('Use AI-powered features to generate reply suggestions and auto-respond to incoming messages') }}</li>
                        <li>{{ __('View analytics and reporting on message volume and response performance') }}</li>
                        <li>{{ __('Manage contacts and conversation history') }}</li>
                        <li>{{ __('Run campaigns to send bulk messages to opted-in contacts') }}</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('3. Account Registration') }}</h2>
                    <p class="mt-3">{{ __('You must create an account to use One Inbox. You agree to:') }}</p>
                    <ul class="mt-2 list-disc pl-5 space-y-1 text-sm">
                        <li>{{ __('Provide accurate, current, and complete information during registration') }}</li>
                        <li>{{ __('Maintain the security of your password and account') }}</li>
                        <li>{{ __('Promptly notify us of any unauthorized use of your account') }}</li>
                        <li>{{ __('Be responsible for all activities that occur under your account') }}</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('4. Connected Platform Compliance') }}</h2>
                    <p class="mt-3">{{ __('When you connect social media accounts to One Inbox, you agree to comply with the terms and policies of each respective platform:') }}</p>
                    <ul class="mt-3 list-disc pl-5 space-y-2 text-sm">
                        <li><strong>Meta (Facebook, Instagram, WhatsApp):</strong> {{ __('Meta Platform Terms, WhatsApp Business Policy') }}</li>
                        <li><strong>TikTok:</strong> {{ __('TikTok Terms of Service, TikTok Developer Terms of Service') }}</li>
                        <li><strong>Telegram:</strong> {{ __('Telegram Terms of Service') }}</li>
                    </ul>
                    <p class="mt-3">{{ __('You are solely responsible for ensuring your use of connected platforms complies with their respective terms. One Inbox is not responsible for any actions taken by platforms against your accounts.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('5. Acceptable Use') }}</h2>
                    <p class="mt-3">{{ __('You agree NOT to use One Inbox to:') }}</p>
                    <ul class="mt-2 list-disc pl-5 space-y-1 text-sm">
                        <li>{{ __('Send spam, unsolicited bulk messages, or messages to contacts who have not opted in') }}</li>
                        <li>{{ __('Violate any applicable law or regulation, including anti-spam laws (CAN-SPAM, GDPR, CASL)') }}</li>
                        <li>{{ __('Impersonate any person or entity') }}</li>
                        <li>{{ __('Transmit harmful, abusive, harassing, or fraudulent content') }}</li>
                        <li>{{ __('Attempt to reverse engineer, scrape, or circumvent any platform\'s security measures') }}</li>
                        <li>{{ __('Use AI-generated messages in a deceptive manner that misleads message recipients') }}</li>
                        <li>{{ __('Exceed API rate limits or attempt to circumvent platform restrictions') }}</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('6. AI-Generated Content') }}</h2>
                    <p class="mt-3">{{ __('One Inbox provides AI-powered reply suggestions and auto-response features powered by third-party AI providers. You acknowledge that:') }}</p>
                    <ul class="mt-2 list-disc pl-5 space-y-2 text-sm">
                        <li>{{ __('AI-generated responses may not always be accurate, appropriate, or complete') }}</li>
                        <li>{{ __('You are solely responsible for all messages sent from your connected accounts, including AI-generated ones') }}</li>
                        <li>{{ __('You should review AI-generated content before sending when possible') }}</li>
                        <li>{{ __('One Inbox is not liable for any consequences resulting from AI-generated messages') }}</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('7. Subscription and Billing') }}</h2>
                    <p class="mt-3">{{ __('One Inbox offers free and paid subscription plans. By subscribing to a paid plan, you agree to:') }}</p>
                    <ul class="mt-2 list-disc pl-5 space-y-1 text-sm">
                        <li>{{ __('Pay all fees associated with your chosen plan') }}</li>
                        <li>{{ __('Subscriptions automatically renew unless cancelled before the renewal date') }}</li>
                        <li>{{ __('Fees are non-refundable except where required by law') }}</li>
                        <li>{{ __('We reserve the right to change pricing with 30 days notice') }}</li>
                    </ul>
                    <p class="mt-3">{{ __('You can cancel your subscription at any time from your account settings. Access continues until the end of the current billing period.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('8. Intellectual Property') }}</h2>
                    <p class="mt-3">{{ __('One Inbox and its original content, features, and functionality are owned by One Inbox and protected by international copyright, trademark, and other intellectual property laws.') }}</p>
                    <p class="mt-3">{{ __('You retain ownership of all content you create and messages you manage through One Inbox. By using our Service, you grant us a limited license to process and display your content solely for the purpose of providing the Service.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('9. Data & Privacy') }}</h2>
                    <p class="mt-3">{{ __('Your use of One Inbox is also governed by our Privacy Policy, which is incorporated into these Terms by reference. We process your data as described therein and in compliance with applicable data protection laws.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('10. Service Availability') }}</h2>
                    <p class="mt-3">{{ __('We strive to maintain high availability but do not guarantee uninterrupted service. We may temporarily suspend the Service for maintenance, upgrades, or due to circumstances beyond our control. We are not liable for any losses arising from service interruptions.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('11. Limitation of Liability') }}</h2>
                    <p class="mt-3">{{ __('To the maximum extent permitted by law, One Inbox and its affiliates, officers, employees, and agents shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including loss of profits, data, or goodwill, arising from your use of the Service.') }}</p>
                    <p class="mt-3">{{ __('Our total liability to you for any claim arising from these Terms or your use of the Service shall not exceed the amount you paid us in the 12 months preceding the claim.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('12. Indemnification') }}</h2>
                    <p class="mt-3">{{ __('You agree to indemnify and hold harmless One Inbox from any claims, damages, losses, and expenses (including legal fees) arising from your use of the Service, your violation of these Terms, or your violation of any third-party rights including connected platform terms.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('13. Termination') }}</h2>
                    <p class="mt-3">{{ __('We may suspend or terminate your account immediately if you violate these Terms, engage in fraudulent activity, or for any other reason at our discretion. You may cancel your account at any time through your account settings.') }}</p>
                    <p class="mt-3">{{ __('Upon termination, your right to use the Service ceases and we may delete your data in accordance with our Privacy Policy.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('14. Changes to Terms') }}</h2>
                    <p class="mt-3">{{ __('We reserve the right to modify these Terms at any time. We will provide at least 30 days notice of material changes via email or in-app notification. Continued use of One Inbox after the effective date of changes constitutes acceptance.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('15. Governing Law') }}</h2>
                    <p class="mt-3">{{ __('These Terms shall be governed by and construed in accordance with applicable law. Any disputes arising under these Terms shall be resolved through good-faith negotiation, and if unresolved, through binding arbitration.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('16. Contact Us') }}</h2>
                    <p class="mt-3">{{ __('For questions about these Terms, contact us at:') }}</p>
                    <div class="mt-3 text-sm">
                        <p><strong>{{ __('Email:') }}</strong> legal@ot1-pro.com</p>
                        <p class="mt-1"><strong>{{ __('Website:') }}</strong> https://ot1-pro.com/terms</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

</x-layouts.marketing>
