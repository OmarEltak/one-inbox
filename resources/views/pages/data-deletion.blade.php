<x-layouts.marketing
    :title="__('Data Deletion Instructions') . ' — OT1-Pro'"
    :description="__('How to request deletion of data processed by OT Pro and OT1-Pro, including Meta Platform Data.')">

    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-3xl px-6">
            <h1 class="text-4xl font-bold tracking-tight">{{ __('Data Deletion Instructions') }}</h1>
            <p class="mt-4 text-lg text-zinc-600 dark:text-zinc-400">
                {{ __('This page explains how to request deletion of personal data processed by OT Pro through OT1-Pro.') }}
            </p>

            <div class="mt-12 space-y-10 text-zinc-600 dark:text-zinc-400">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-sm dark:border-emerald-900/60 dark:bg-emerald-950/30">
                    <p><strong class="text-zinc-900 dark:text-zinc-100">{{ __('Need to remove Meta Platform Data?') }}</strong> {{ __('If you make the request through Facebook or Instagram, Meta sends our verified Data Deletion Callback a signed request. We return a confirmation code and a private status link so you can follow the request.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('1. Facebook and Instagram') }}</h2>
                    <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm">
                        <li>{{ __('Sign in to the Facebook or Instagram account used with OT1-Pro.') }}</li>
                        <li>{{ __('Open the platform’s Settings, then its Apps and Websites or connected-apps area.') }}</li>
                        <li>{{ __('Find “OT Pro” or “OT1-Pro”, choose Remove, and follow any on-screen data-deletion option.') }}</li>
                        <li>{{ __('Meta will submit the request to OT Pro. Save the confirmation code and status-link information supplied by Meta.') }}</li>
                    </ol>
                    <p class="mt-3 text-sm">{{ __('You can also request deletion by email. Include the Facebook or Instagram account identifier, if available, so that we can locate the correct data.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('2. WhatsApp Business') }}</h2>
                    <p class="mt-3 text-sm">{{ __('If you are a customer messaging a business that uses OT1-Pro, please contact that business first. The business controls its customer relationship and can request deletion through its OT1-Pro workspace or by contacting us. If you are the OT1-Pro customer, contact us from your registered business email and identify the connected WhatsApp Business number.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('3. OT1-Pro account or other platform data') }}</h2>
                    <p class="mt-3 text-sm">{{ __('Email us with the subject “Data Deletion Request”, your registered email address, the relevant connected platform, and enough information to verify your request. Do not send passwords or access tokens.') }}</p>
                    <a href="mailto:omareltak7@gmail.com?subject=Data%20Deletion%20Request" class="mt-4 inline-flex rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500">omareltak7@gmail.com</a>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('What we delete and when') }}</h2>
                    <p class="mt-3 text-sm">{{ __('After verifying a request, we delete or de-identify the relevant account, access credentials, contact data, conversation data, messages, and AI-derived data that we hold for the request. Meta callback requests are queued immediately and have a confirmation-status page. Other verified requests are completed within 30 days. Limited records may be retained only where necessary for security, fraud prevention, legal obligations, or to document the deletion request; backup copies are removed on their normal backup-replacement cycle.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Contact') }}</h2>
                    <p class="mt-3 text-sm">OT Pro, Cairo, Cairo 171811, Egypt<br><a class="text-emerald-600 hover:underline" href="tel:+201026361218">+20 102 636 1218</a><br><a class="text-emerald-600 hover:underline" href="mailto:omareltak7@gmail.com">omareltak7@gmail.com</a></p>
                </div>
            </div>
        </div>
    </section>
</x-layouts.marketing>
