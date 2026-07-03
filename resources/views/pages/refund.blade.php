<x-layouts.marketing :title="__('Refund Policy') . ' — OT1-Pro'" :description="__('OT1-Pro Refund Policy — how we handle refund requests for paid subscriptions.')">

    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-3xl px-6">
            <h1 class="text-4xl font-bold tracking-tight">{{ __('Refund Policy') }}</h1>
            <p class="mt-4 text-sm text-zinc-500">{{ __('Last updated') }}: July 4, 2026</p>
            <p class="mt-2 text-sm text-zinc-500">{{ __('Effective date') }}: July 4, 2026</p>

            <div class="mt-12 space-y-10 text-zinc-600 dark:text-zinc-600">

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('1. Overview') }}</h2>
                    <p class="mt-3">{{ __('OT1-Pro is committed to providing a high-quality service. If you are not satisfied with your subscription, we offer a 14-day money-back guarantee on all new paid plan subscriptions.') }}</p>
                    <p class="mt-3">{{ __('All payments are processed by Paddle (paddle.com), our authorised reseller and Merchant of Record. Paddle handles all payment processing and refund transactions on our behalf.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('2. 14-Day Money-Back Guarantee') }}</h2>
                    <p class="mt-3">{{ __('New subscribers on any paid plan (Starter or Pro) may request a full refund within 14 days of their initial purchase. To qualify:') }}</p>
                    <ul class="mt-2 list-disc pl-5 space-y-1 text-sm">
                        <li>{{ __('The request must be submitted within 14 calendar days of the original purchase date') }}</li>
                        <li>{{ __('The guarantee applies to the first billing period only — subsequent renewal charges are not eligible') }}</li>
                        <li>{{ __('The guarantee applies once per customer, per plan') }}</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('3. Renewals and Subsequent Charges') }}</h2>
                    <p class="mt-3">{{ __('Subscription renewals (monthly or annual) are non-refundable unless required by applicable law. We recommend cancelling your subscription before the renewal date if you do not wish to continue. You can cancel at any time from your account settings — access continues until the end of the current billing period.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('4. Plan Upgrades and Downgrades') }}</h2>
                    <p class="mt-3">{{ __('When you upgrade to a higher plan mid-cycle, you will be charged the prorated difference immediately. When you downgrade, the change takes effect at the next renewal date — no partial refund is issued for the remainder of the current period.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('5. Exceptional Circumstances') }}</h2>
                    <p class="mt-3">{{ __('Outside of the 14-day guarantee window, we may consider refund requests in exceptional circumstances, including:') }}</p>
                    <ul class="mt-2 list-disc pl-5 space-y-1 text-sm">
                        <li>{{ __('Prolonged service outages caused solely by OT1-Pro (exceeding 72 consecutive hours)') }}</li>
                        <li>{{ __('Duplicate charges caused by a billing error') }}</li>
                        <li>{{ __('Charges made after a documented cancellation request was submitted') }}</li>
                    </ul>
                    <p class="mt-3">{{ __('Each case is reviewed individually. We reserve the right to decline requests that do not meet these criteria.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('6. How to Request a Refund') }}</h2>
                    <p class="mt-3">{{ __('To request a refund, contact us within the eligible window:') }}</p>
                    <ul class="mt-3 list-disc pl-5 space-y-2 text-sm">
                        <li><strong>{{ __('Email:') }}</strong> {{ __('Send your request to') }} <a href="mailto:support@ot1-pro.com" class="text-indigo-600 hover:underline">support@ot1-pro.com</a> {{ __('with the subject line "Refund Request" and include your registered email address and the reason for your request.') }}</li>
                        <li><strong>{{ __('WhatsApp:') }}</strong> {{ __('Message us directly at') }} <a href="https://wa.me/201026361218" class="text-indigo-600 hover:underline" target="_blank" rel="noopener">+20 102 636 1218</a>.</li>
                    </ul>
                    <p class="mt-3">{{ __('We will respond within 2 business days. Once approved, Paddle will process the refund to your original payment method within 5–10 business days, depending on your bank.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('7. Chargebacks') }}</h2>
                    <p class="mt-3">{{ __('We encourage you to contact us before initiating a chargeback with your bank. Chargebacks initiated without contacting us first may result in the immediate suspension of your account. If you have a legitimate concern, we are committed to resolving it directly and promptly.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('8. Contact Us') }}</h2>
                    <p class="mt-3">{{ __('For questions about this policy, contact us at:') }}</p>
                    <div class="mt-3 text-sm">
                        <p><strong>{{ __('Email:') }}</strong> support@ot1-pro.com</p>
                        <p class="mt-1"><strong>{{ __('Website:') }}</strong> https://ot1-pro.com/refund</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

</x-layouts.marketing>
