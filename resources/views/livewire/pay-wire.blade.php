<div class="min-h-screen bg-zinc-50 py-16 px-4">
    <div class="max-w-2xl mx-auto">

        {{-- Header --}}
        <div class="text-center mb-10">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 mb-6">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl" style="background: linear-gradient(135deg, #7c3aed, #5b21b6);">
                    <x-app-logo-icon class="size-5 fill-current text-white" />
                </span>
                <span class="text-xl font-bold text-zinc-900">{{ config('app.name', 'OT1-Pro') }}</span>
            </a>
            <h1 class="text-3xl font-bold text-zinc-900">Wire Transfer Payment</h1>
            <p class="mt-2 text-zinc-500">Pay via international bank transfer and we'll activate your workspace within 12–24 hours.</p>
        </div>

        @if($submitted)
            {{-- Success state --}}
            <div class="bg-white rounded-2xl border border-zinc-200 shadow-sm p-10 text-center">
                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>
                <h2 class="text-xl font-bold text-zinc-900 mb-2">We've received your submission!</h2>
                <p class="text-zinc-500 max-w-sm mx-auto">
                    We'll review your receipt and activate your account early — usually within a few hours. Expect a confirmation email at <strong>{{ $email }}</strong>.
                </p>
                <p class="mt-6 text-sm text-zinc-400">
                    Questions? Email us at
                    <a href="mailto:it@mishkahu.com" class="text-violet-600 hover:underline">it@mishkahu.com</a>
                </p>
            </div>
        @else
            <div class="grid gap-6">

                {{-- Bank Details Card --}}
                <div class="bg-white rounded-2xl border border-zinc-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-zinc-100 flex items-center gap-2">
                        <svg class="w-5 h-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <h2 class="font-semibold text-zinc-900">Step 1 — Send your payment to this account</h2>
                    </div>
                    <div class="px-6 py-5 space-y-3">
                        @php
                            $bankDetails = [
                                'Account Holder' => env('BANK_HOLDER_NAME', 'Omar Mohamed Ali'),
                                'Bank Name'      => env('BANK_NAME', 'National Bank of Egypt (NBE)'),
                                'SWIFT / BIC'    => env('BANK_SWIFT', 'NBEGEGCX134'),
                                'IBAN'           => env('BANK_IBAN', 'EG180003013450818264140000110'),
                            ];
                        @endphp

                        @foreach($bankDetails as $label => $value)
                            <div class="flex items-center justify-between py-2 border-b border-zinc-50 last:border-0">
                                <span class="text-sm text-zinc-500 font-medium">{{ $label }}</span>
                                <span class="text-sm font-semibold text-zinc-900 font-mono select-all">{{ $value }}</span>
                            </div>
                        @endforeach

                        <div class="mt-3 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3">
                            <p class="text-xs text-amber-800">
                                <strong>Important:</strong> Include <code class="bg-amber-100 px-1 rounded">OT1 Pro SaaS Subscription</code> in your transfer notes/memo field to expedite compliance clearance.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Form Card --}}
                <div class="bg-white rounded-2xl border border-zinc-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-zinc-100 flex items-center gap-2">
                        <svg class="w-5 h-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h2 class="font-semibold text-zinc-900">Step 2 — Fill in your details &amp; upload your receipt</h2>
                    </div>

                    <form wire:submit="submit" class="px-6 py-5 space-y-5">

                        <div class="grid sm:grid-cols-2 gap-4">
                            <flux:input
                                label="Full name"
                                wire:model="full_name"
                                placeholder="Your full name"
                                required
                            />
                            <flux:input
                                label="Email address"
                                wire:model="email"
                                type="email"
                                placeholder="you@company.com"
                                required
                            />
                        </div>

                        <flux:select label="Plan you're subscribing to" wire:model="plan">
                            <option value="basic">Basic — $8/month</option>
                            <option value="starter">Starter — $29/month</option>
                            <option value="pro">Pro — $79/month</option>
                        </flux:select>

                        <div class="grid sm:grid-cols-2 gap-4">
                            <flux:input
                                label="Your bank name"
                                wire:model="bank_name"
                                placeholder="e.g. HSBC, Mashreq, Chase"
                                required
                            />
                            <flux:input
                                label="Your country"
                                wire:model="bank_country"
                                placeholder="e.g. UAE, USA, UK"
                                required
                            />
                        </div>

                        <flux:input
                            label="Transaction reference / TXID (optional)"
                            wire:model="txid"
                            placeholder="Reference number shown on your bank receipt"
                        />

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">
                                Attach receipt / transaction screenshot <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    type="file"
                                    wire:model="receipt"
                                    accept=".jpg,.jpeg,.png,.pdf,.webp"
                                    class="block w-full text-sm text-zinc-700 border border-zinc-300 rounded-lg cursor-pointer bg-zinc-50 focus:outline-none focus:border-violet-400 file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-medium file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100 py-2 px-3"
                                />
                            </div>
                            <p class="mt-1 text-xs text-zinc-400">JPG, PNG, PDF, or WebP — max 10 MB</p>
                            @error('receipt') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror

                            <div wire:loading wire:target="receipt" class="mt-2 text-xs text-violet-600">Uploading...</div>
                        </div>

                        @if($errors->any())
                            <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3">
                                <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
                            <span wire:loading.remove>Submit Payment Details</span>
                            <span wire:loading>Submitting...</span>
                        </flux:button>

                        <p class="text-xs text-center text-zinc-400">
                            SWIFT transfers take 2–5 business days to clear. We'll provision your account as soon as we see your receipt — usually within a few hours.
                        </p>
                    </form>
                </div>

            </div>
        @endif
    </div>
</div>
