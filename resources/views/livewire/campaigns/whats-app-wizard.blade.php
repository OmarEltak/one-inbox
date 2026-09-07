<div class="max-w-3xl mx-auto py-8 px-4">
    {{-- Page heading — dark, bold, with WhatsApp-green accent on the platform word. --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">
            New <span class="text-[#25D366]">WhatsApp</span> Campaign
        </h1>
        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
            Upload a contact list, compose your message, test one send, then launch.
        </p>
    </div>

    {{-- Step 1 — Upload --}}
    @if ($step === 'upload')
        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 shadow-sm space-y-6">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">
                        Upload contact list
                    </h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        CSV or Excel (.xlsx), up to 10 MB and 50,000 rows.
                    </p>
                </div>
                <a href="/samples/whatsapp-campaign-contacts.csv"
                   download
                   class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 dark:border-zinc-700 px-3 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16" />
                    </svg>
                    Download sample CSV
                </a>
            </div>

            {{-- Custom dropzone. Native input is visually hidden; label triggers it.
                 Three visual states via wire:loading + $file presence:
                   idle       → dashed border, upload icon, "Choose file"
                   uploading  → subtle pulse + spinner (wire:loading targeted on file)
                   selected   → green border, filename, Replace + Remove buttons --}}
            <div>
                <input type="file"
                       id="wa-wizard-file"
                       wire:model="file"
                       accept=".csv,.xlsx"
                       class="sr-only" />

                @if (! $file && ! $storedPath)
                    {{-- Idle state --}}
                    <label for="wa-wizard-file"
                           wire:loading.class="border-blue-400 bg-blue-50 dark:bg-blue-900/20 animate-pulse"
                           wire:target="file"
                           class="flex flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed border-zinc-300 dark:border-zinc-600 bg-zinc-50/50 dark:bg-zinc-900/30 px-6 py-10 cursor-pointer hover:border-emerald-400 hover:bg-emerald-50/40 dark:hover:bg-emerald-900/10 transition">
                        <div wire:loading.remove wire:target="file" class="flex flex-col items-center gap-3">
                            <div class="rounded-full bg-emerald-100 dark:bg-emerald-900/40 p-3">
                                <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.9 5 5 0 019.9-1.09A3.5 3.5 0 0117 15.5H7z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 12v6m0-6l-2 2m2-2l2 2" />
                                </svg>
                            </div>
                            <div class="text-center">
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    Click to choose a file
                                </p>
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    .csv or .xlsx up to 10 MB
                                </p>
                            </div>
                        </div>
                        <div wire:loading wire:target="file" class="flex items-center gap-2 text-sm font-medium text-blue-600 dark:text-blue-400">
                            <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            Uploading…
                        </div>
                    </label>
                @else
                    {{-- Selected state --}}
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-900/20 px-4 py-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-500 text-white">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $file?->getClientOriginalName() ?? $originalName ?? 'file selected' }}
                                </p>
                                <p class="text-xs text-emerald-700 dark:text-emerald-400">
                                    Ready to import
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <label for="wa-wizard-file"
                                   class="inline-flex items-center gap-1.5 rounded-md border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-xs font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-700 cursor-pointer transition">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v6h6M20 20v-6h-6M4 4l7 7M20 20l-7-7" />
                                </svg>
                                Replace
                            </label>
                            <button type="button"
                                    wire:click="removeFile"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-red-200 dark:border-red-800 bg-white dark:bg-zinc-800 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Remove
                            </button>
                        </div>
                    </div>
                @endif

                @error('file')
                    <p class="mt-2 flex items-center gap-1.5 text-sm text-red-600 dark:text-red-400">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M4.93 19h14.14a2 2 0 001.75-3l-7.07-12a2 2 0 00-3.5 0l-7.07 12a2 2 0 001.75 3z" />
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Phone-format guide — non-negotiable knowledge before uploading. --}}
            <div class="rounded-xl bg-zinc-50 dark:bg-zinc-900/40 border border-zinc-200 dark:border-zinc-700 p-4">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-2">
                    Phone number format
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                    <div>
                        <p class="font-medium text-emerald-700 dark:text-emerald-400 mb-1">✓ Accepted shapes</p>
                        <ul class="space-y-0.5 text-zinc-600 dark:text-zinc-300 font-mono">
                            <li><code>+201099887766</code> (E.164, always safe)</li>
                            <li><code>+20 10 9988 7766</code> (spaces ok)</li>
                            <li><code>+20-10-9988-7766</code> (dashes ok)</li>
                            <li><code>01099887766</code> (local, needs default country)</li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-medium text-red-600 dark:text-red-400 mb-1">✗ Rejected</p>
                        <ul class="space-y-0.5 text-zinc-600 dark:text-zinc-300 font-mono">
                            <li>Empty rows</li>
                            <li>Numbers under 7 digits</li>
                            <li>Text that isn't a phone (<code>not-a-number</code>)</li>
                            <li>Local format without a default country</li>
                        </ul>
                    </div>
                </div>
                <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                    <strong>Country code:</strong> not required in the file if you set a default country in the next step. E.164 numbers (starting with <code>+</code>) always win over the default.
                </p>
            </div>

            <div class="flex justify-end">
                <flux:button wire:click="advanceToMap" variant="primary" :disabled="! $file && ! $storedPath">
                    Next →
                </flux:button>
            </div>
        </div>

    {{-- Step 2 — Map columns --}}
    @elseif ($step === 'map')
        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 shadow-sm space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">Map columns</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Tell us which column has the phone number and which has the name.
            </p>

            <flux:select wire:model="phoneColumn" label="Phone column">
                @foreach ($detectedHeaders as $h)
                    <flux:select.option value="{{ $h }}">{{ $h }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="nameColumn" label="Name column (optional)">
                <flux:select.option value="">— none —</flux:select.option>
                @foreach ($detectedHeaders as $h)
                    <flux:select.option value="{{ $h }}">{{ $h }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="defaultCountry" label="Default country (ISO2)" placeholder="EG" />

            <div class="flex justify-end pt-2">
                <flux:button wire:click="advanceToCompose" variant="primary">
                    Import &amp; Continue
                </flux:button>
            </div>
        </div>

    {{-- Step 3 — Compose --}}
    @elseif ($step === 'compose')
        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 shadow-sm space-y-4">
            <div class="flex items-center gap-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-3 py-2">
                <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-sm text-emerald-800 dark:text-emerald-300">
                    Imported <strong>{{ $importedCount }}</strong> contact{{ $importedCount === 1 ? '' : 's' }}.
                </span>
            </div>

            <flux:input wire:model="campaignName" label="Campaign name" placeholder="August promo" />

            <flux:select wire:model="senderPageId" label="Send from">
                @foreach ($this->whatsappSenders as $p)
                    <flux:select.option value="{{ $p->id }}">{{ $p->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:textarea wire:model="body" label="Message" rows="6" placeholder="Hi {{ '{{name}}' }}, we have something for you…" />

            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="jitterMin" type="number" label="Jitter min (sec)" />
                <flux:input wire:model="jitterMax" type="number" label="Jitter max (sec)" />
            </div>

            <div class="flex justify-end pt-2">
                <flux:button wire:click="advanceToTest" variant="primary">
                    Test send →
                </flux:button>
            </div>
        </div>

    {{-- Step 4 — Test send --}}
    @elseif ($step === 'test')
        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 shadow-sm space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">Test send</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Send one message to verify the format and connection before launching.
            </p>

            <flux:input wire:model="testPhone" label="Test phone (E.164)" placeholder="+201099887766" />
            <flux:input wire:model="testName" label="Test name" />

            <flux:button wire:click="sendTest">Send test</flux:button>

            @if ($testResult === true)
                <div class="flex items-center gap-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-3 py-2">
                    <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="text-sm text-emerald-800 dark:text-emerald-300">
                        Test message sent. Check WhatsApp.
                    </span>
                </div>
                <flux:button wire:click="advanceToReview" variant="primary">
                    Looks good — review →
                </flux:button>
            @elseif ($testResult === false)
                <div class="flex items-start gap-2 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-3 py-2">
                    <svg class="h-4 w-4 mt-0.5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M4.93 19h14.14a2 2 0 001.75-3l-7.07-12a2 2 0 00-3.5 0l-7.07 12a2 2 0 001.75 3z" />
                    </svg>
                    <span class="text-sm text-red-800 dark:text-red-300">
                        Test failed: {{ $testError }}
                    </span>
                </div>
            @endif
        </div>

    {{-- Step 5 — Review --}}
    @elseif ($step === 'review')
        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 shadow-sm space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">Ready to launch</h2>
            <p class="text-zinc-700 dark:text-zinc-300">
                Sending to <strong>{{ $importedCount }}</strong> recipient{{ $importedCount === 1 ? '' : 's' }} with
                <strong>{{ $jitterMin }}–{{ $jitterMax }}s</strong> jitter between messages.
            </p>
            <flux:button wire:click="launch" variant="primary">
                Launch campaign
            </flux:button>
        </div>

    {{-- Step 6 — Launched --}}
    @elseif ($step === 'launched')
        <div class="rounded-2xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 p-6 shadow-sm text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500 text-white mb-3">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-emerald-900 dark:text-emerald-100">
                Campaign launched
            </h2>
            <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-300">
                Sending is in progress. You can close this page.
            </p>
        </div>
    @endif
</div>
