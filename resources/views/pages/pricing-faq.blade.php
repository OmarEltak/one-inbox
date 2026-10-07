@php
    /**
     * Phase G — public /pricing-faq page.
     *
     * All numbers are read from config('ai_costs') so the page never goes
     * stale when the cost table is tuned. See docs/superpowers/specs/
     * 2026-10-04-ai-credit-economy-design.md §7.3.
     */
    $costs = config('ai_costs', []);
    $deepPer100 = (int) ($costs['deep_analysis_per_100_contacts'] ?? 5);
    $deepMinimum = (int) ($costs['deep_analysis_minimum'] ?? 10);
    $deepFor1000 = max($deepMinimum, 10 * $deepPer100);

    // Build the cost rows dynamically. The deep_analysis_* keys are scaling
    // knobs — they are rendered separately in the Deep Analysis section, so
    // we skip them here to avoid confusion.
    $costRows = [
        'ai_chat_turn'               => __('AI chat message (operator asks a question)'),
        'ai_reply_outbound'          => __('AI reply to a customer message'),
        'ai_comment_reply'           => __('AI comment reply (Facebook / Instagram post)'),
        'ai_lead_score'              => __('Lead scoring (free — we eat this cost)'),
        'agent_audit'                => __('Agent audit (summary of your team\'s handling of a cohort)'),
        'bulk_campaign_recipient'    => __('Bulk campaign recipient'),
        'whatsapp_meta_conversation' => __('WhatsApp Meta API conversation (24h window)'),
    ];

    // Phase RP (2026-10-06) — plan ladder table at the top. Numbers read live
    // from config('plans.plans') so this page can never go stale.
    $planLadder = (array) config('plans.plans', []);
@endphp

<x-layouts.marketing :title="__('Pricing FAQ') . ' — OT1-Pro'" :description="__('How OT1-Pro AI credits work: cost per action, Deep Analysis pricing, top-up flow, and auto-deduct.')">
    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-3xl px-6">
            <h1 class="text-4xl font-bold tracking-tight">{{ __('Pricing FAQ') }}</h1>
            <p class="mt-4 text-sm text-zinc-500">{{ __('Last updated') }}: October 5, 2026</p>
            <p class="mt-2 text-base text-zinc-600 dark:text-zinc-400">{{ __('Everything you need to know about AI credits — how we count them, how to top up, and what happens when things go wrong.') }}</p>

            <div class="mt-12 space-y-10 text-zinc-600 dark:text-zinc-400">

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Plans at a glance') }}</h2>
                    <p class="mt-3">{{ __('Four tiers. Pay only for what you use beyond the included monthly credits.') }}</p>
                    <div class="mt-5 overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-sm" data-test="plan-ladder-table">
                            <thead class="bg-zinc-50 dark:bg-zinc-900/40">
                                <tr class="border-b border-zinc-100 dark:border-zinc-700 text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    <th class="px-4 py-2.5 text-start font-semibold">{{ __('Plan') }}</th>
                                    <th class="px-4 py-2.5 text-end font-semibold">{{ __('Price') }}</th>
                                    <th class="px-4 py-2.5 text-end font-semibold">{{ __('AI credits / month') }}</th>
                                    <th class="px-4 py-2.5 text-end font-semibold hidden md:table-cell">{{ __('Facebook / Instagram pages') }}</th>
                                    <th class="px-4 py-2.5 text-end font-semibold hidden md:table-cell">{{ __('WhatsApp Business API numbers') }}</th>
                                    <th class="px-4 py-2.5 text-end font-semibold hidden md:table-cell">{{ __('Bulk campaigns / month') }}</th>
                                    <th class="px-4 py-2.5 text-end font-semibold hidden lg:table-cell">{{ __('Contacts stored') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($planLadder as $slug => $plan)
                                    @php
                                        $limits = (array) ($plan['limits'] ?? []);
                                    @endphp
                                    <tr class="border-b border-zinc-50 dark:border-zinc-700/40 last:border-0" data-test="plan-ladder-row-{{ $slug }}">
                                        <td class="px-4 py-2.5 text-zinc-800 dark:text-zinc-100 font-semibold">
                                            {{ __($plan['name'] ?? ucfirst($slug)) }}
                                        </td>
                                        <td class="px-4 py-2.5 text-end font-mono tabular-nums text-zinc-900 dark:text-zinc-100 font-semibold">
                                            ${{ number_format((int) ($plan['price'] ?? 0)) }}
                                        </td>
                                        <td class="px-4 py-2.5 text-end font-mono tabular-nums">
                                            {{ number_format((int) ($plan['ai_credits'] ?? 0)) }}
                                        </td>
                                        <td class="px-4 py-2.5 text-end font-mono tabular-nums hidden md:table-cell">
                                            {{ number_format((int) ($limits['pages'] ?? 0)) }}
                                        </td>
                                        <td class="px-4 py-2.5 text-end font-mono tabular-nums hidden md:table-cell">
                                            {{ number_format((int) ($limits['whatsapp_numbers'] ?? 0)) }}
                                        </td>
                                        <td class="px-4 py-2.5 text-end font-mono tabular-nums hidden md:table-cell">
                                            {{ number_format((int) ($limits['bulk_campaigns_monthly'] ?? 0)) }}
                                        </td>
                                        <td class="px-4 py-2.5 text-end font-mono tabular-nums hidden lg:table-cell">
                                            {{ number_format((int) ($limits['contacts_stored'] ?? 0)) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('What is 1 AI credit?') }}</h2>
                    <p class="mt-3">{{ __('1 credit = 1 AI reply to a customer, or 1 message in the AI chat. Some actions (like Deep Analysis of a large cohort) cost more, because they do more work. See the full cost table below.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Cost table') }}</h2>
                    <p class="mt-3">{{ __('Here is exactly what every AI action costs today. We tune this table from time to time — the numbers below are read live from our server, so this page is always current.') }}</p>
                    <div class="mt-5 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-sm" data-test="cost-table">
                            <thead class="bg-zinc-50 dark:bg-zinc-900/40">
                                <tr class="border-b border-zinc-100 dark:border-zinc-700 text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    <th class="px-4 py-2.5 text-start font-semibold">{{ __('Action') }}</th>
                                    <th class="px-4 py-2.5 text-end font-semibold">{{ __('Cost (credits)') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($costRows as $key => $label)
                                    <tr class="border-b border-zinc-50 dark:border-zinc-700/40 last:border-0">
                                        <td class="px-4 py-2.5 text-zinc-800 dark:text-zinc-100" data-test="cost-row-{{ $key }}">
                                            {{ $label }}
                                        </td>
                                        <td class="px-4 py-2.5 text-end font-mono tabular-nums font-semibold text-zinc-900 dark:text-zinc-100">
                                            {{ (int) ($costs[$key] ?? 1) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('How is Deep Analysis priced?') }}</h2>
                    <p class="mt-3">{{ __('Deep Analysis scales with the cohort size: :per credits per 100 contacts analysed, with a minimum of :min credits per run. So analysing 1,000 contacts costs :forThousand credits. Any action over 5 credits asks for your confirmation first.', ['per' => $deepPer100, 'min' => $deepMinimum, 'forThousand' => $deepFor1000]) }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('What is "auto-deduct"?') }}</h2>
                    <p class="mt-3">{{ __('If you turn on "always auto-deduct for actions ≥ 5 credits", you won\'t see a confirmation modal next time. You can turn it back off in Settings → Billing.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('How do I top up?') }}</h2>
                    <p class="mt-3">{{ __('Go to Settings → Billing → Top up. Send payment via PayPal, bank transfer, or WhatsApp. Credits land in your account within an hour of our confirming the payment.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Do my credits expire?') }}</h2>
                    <p class="mt-3">{{ __('Monthly plan credits reset on your billing-cycle anniversary (use them or lose them). Credit packs you buy separately never expire.') }}</p>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('What happens if the AI service fails?') }}</h2>
                    <p class="mt-3">{{ __('If a credit charge was made but the AI call failed due to a system outage, we automatically refund the credits back to your balance.') }}</p>
                </div>

                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-sm dark:border-emerald-900/60 dark:bg-emerald-950/40">
                    <p class="text-zinc-700 dark:text-zinc-200">{{ __('Still have questions? Read the') }} <a href="{{ route('privacy') }}" class="text-emerald-700 hover:underline dark:text-emerald-300">{{ __('Privacy Policy') }}</a> {{ __('and') }} <a href="{{ route('terms') }}" class="text-emerald-700 hover:underline dark:text-emerald-300">{{ __('Terms of Service') }}</a>{{ __(', or email omareltak7@gmail.com.') }}</p>
                </div>

            </div>
        </div>
    </section>
</x-layouts.marketing>
