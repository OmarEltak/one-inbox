<x-layouts.marketing :title="__('Privacy Policy') . ' — OT1-Pro'" :description="__('How OT Pro collects, uses, shares, retains, and deletes data through OT1-Pro.')">
    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-3xl px-6">
            <h1 class="text-4xl font-bold tracking-tight">{{ __('Privacy Policy') }}</h1>
            <p class="mt-4 text-sm text-zinc-500">{{ __('Last updated') }}: October 2, 2026</p>
            <p class="mt-2 text-sm text-zinc-500">{{ __('Effective date') }}: October 2, 2026</p>

            <div class="mt-12 space-y-10 text-zinc-600 dark:text-zinc-400">
                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('1. Who we are') }}</h2>
                    <p class="mt-3">{{ __('OT Pro (“OT Pro”, “we”, “us”, or “our”) operates OT1-Pro, a unified inbox and optional AI-assisted messaging service. Our legal business name is OT Pro. Our business address is Cairo, Cairo 171811, Egypt. You can reach us at support@ot1-pro.com or +20 102 636 1218.') }}</p>
                    <p class="mt-3">{{ __('This policy describes personal data processed when you use ot1-pro.com and OT1-Pro, including data received from Meta products. It does not replace the privacy policy of a business that uses OT1-Pro to communicate with you.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('2. The people this policy covers and our role') }}</h2>
                    <p class="mt-3">{{ __('There are two distinct groups of people in OT1-Pro: (1) our business customers and their authorized users, and (2) the customers, prospects, and other people who message those businesses through a connected channel.') }}</p>
                    <p class="mt-3">{{ __('For our business customers’ account, billing, security, and service-administration data, OT Pro generally decides why and how the data is processed. For message and contact data processed on behalf of a business customer, that customer generally decides the purpose of processing and is responsible for its notices and lawful basis; OT Pro processes that data to provide the service. If you are a sender who contacted an OT1-Pro customer, direct your access, correction, or deletion request to that business first. We will assist the business where required.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('3. Data we collect') }}</h2>
                    <h3 class="mt-5 text-base font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Account and service data') }}</h3>
                    <p class="mt-2">{{ __('We collect names, email addresses, password hashes, authentication details, team and subscription information, support requests, browser and device information, IP address, and security and usage logs.') }}</p>
                    <h3 class="mt-5 text-base font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Meta Platform Data') }}</h3>
                    <p class="mt-2">{{ __('When a customer connects Facebook Pages, Instagram professional accounts, or WhatsApp Business, we may receive the data authorized through the relevant Meta permissions and APIs: account or Page identifiers and names, access tokens, connected assets, message and conversation content, sender identifiers and profile data made available by Meta, timestamps, delivery or read status, and interaction data needed to display, send, organize, and secure conversations. We use this data only to provide the connected inbox features, optional AI features, analytics, support, and security; we do not sell Meta Platform Data.') }}</p>
                    <h3 class="mt-5 text-base font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Other connected-channel and AI data') }}</h3>
                    <p class="mt-2">{{ __('We process equivalent account, message, and contact data for other channels a customer chooses to connect, such as Telegram, TikTok, or email. If a customer enables AI-assisted replies, lead scoring, image understanding, or voice-note transcription, the relevant message content, image, or audio is processed for that feature. TikTok direct-message data may be included in AI processing only when the customer enables the applicable AI feature; it is not sold or used for advertising.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('4. How we use data') }}</h2>
                    <ul class="mt-3 list-disc space-y-2 pl-5 text-sm">
                        <li>{{ __('Provide, maintain, and secure OT1-Pro and connected messaging features.') }}</li>
                        <li>{{ __('Display, organize, and send messages at the direction of our business customer.') }}</li>
                        <li>{{ __('Generate optional AI replies, transcripts, summaries, and lead scores when enabled.') }}</li>
                        <li>{{ __('Provide service analytics, billing, support, fraud prevention, and legal compliance.') }}</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('5. Service providers and disclosures') }}</h2>
                    <p class="mt-3">{{ __('We do not sell or rent personal data. We disclose data only to provide the service, comply with law, protect rights and safety, or complete a business transaction permitted by law. Our service providers may include: Meta for connected Facebook, Instagram, and WhatsApp services; NaraRouter and the model provider selected through it for enabled AI replies and analysis; Google Gemini if configured for an enabled AI feature; Groq for enabled voice-note transcription; payment providers for billing; and hosting, database, email, and security providers that operate our service. Providers process data under their applicable terms and instructions. Processing may occur in Egypt and in other countries where these providers operate.') }}</p>
                    <p class="mt-3">{{ __('We do not state that every provider retains no data or never uses data for model training, because those terms can differ by provider, plan, and configuration. We review providers and limit the data sent to what is needed for the enabled feature.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('6. Retention and deletion') }}</h2>
                    <p class="mt-3">{{ __('We retain account and platform data only for as long as needed to provide the service, meet the purposes in this policy, resolve disputes, and meet legal obligations. Access tokens are removed when a customer disconnects the relevant platform. A customer can request deletion of its OT1-Pro account or relevant platform data; verified requests are completed within 30 days. A business customer may also ask us to delete data for a sender it serves.') }}</p>
                    <p class="mt-3">{{ __('For Meta Platform Data, we delete data when Meta sends a valid data-deletion request, when the relevant user or customer requests deletion through the appropriate channel, or when the data is no longer necessary for the service. We may keep a minimal record of the request and limited data where necessary for security, fraud prevention, legal obligations, or to prove that the deletion was completed. Backup copies are removed on their normal replacement cycle.') }}</p>
                    <p class="mt-3"><a href="{{ route('data-deletion') }}" class="text-emerald-600 hover:underline">{{ __('Read our Data Deletion Instructions') }}</a></p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('7. Meta data-deletion callback') }}</h2>
                    <p class="mt-3">{{ __('OT Pro provides a Meta Data Deletion Callback for valid Meta requests. When Meta sends a signed deletion request, our system validates it, creates a deletion request, and returns a confirmation code and a unique status URL. The request is processed through our deletion workflow. The status URL lets the requester check whether the request is pending, completed, or needs support.') }}</p>
                    <p class="mt-3">{{ __('Facebook and Instagram users can make a request through the platform’s connected-app settings by removing OT Pro or OT1-Pro and selecting any offered data-deletion option. WhatsApp senders should first contact the business they messaged; OT1-Pro customers can request deletion for their WhatsApp Business data. Full instructions are available on our Data Deletion Instructions page.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('8. Security') }}</h2>
                    <p class="mt-3">{{ __('We use measures intended to protect data, including encryption in transit, encrypted storage for credentials where supported, access controls, and security monitoring. No internet service can guarantee absolute security.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('9. Your rights and international transfers') }}</h2>
                    <p class="mt-3">{{ __('Depending on applicable law, you may have rights to access, correct, delete, restrict, object to processing of, or receive a portable copy of your personal data, and to complain to your local data-protection authority. You may withdraw consent for optional features by disabling them or disconnecting the relevant platform, where consent is the basis for processing. We use appropriate safeguards for international transfers as required by applicable law.') }}</p>
                    <p class="mt-3">{{ __('To exercise a right, contact support@ot1-pro.com. We may request information necessary to verify identity and will respond within the time required by applicable law. If you are communicating with one of our business customers, contact that business first for message and contact data it controls.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('10. Children and policy changes') }}</h2>
                    <p class="mt-3">{{ __('OT1-Pro is a business service and is not directed to children. We may update this policy when our service or legal requirements change. We will post the new effective date and, where appropriate, provide additional notice.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('11. Contact us') }}</h2>
                    <p class="mt-3">OT Pro<br>Cairo, Cairo 171811, Egypt<br><a class="text-emerald-600 hover:underline" href="tel:+201026361218">+20 102 636 1218</a><br><a class="text-emerald-600 hover:underline" href="mailto:support@ot1-pro.com">support@ot1-pro.com</a></p>
                </div>
            </div>
        </div>
    </section>
</x-layouts.marketing>
