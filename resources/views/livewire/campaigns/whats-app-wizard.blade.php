<div class="max-w-3xl mx-auto py-8 px-4">
    {{-- Heading --}}
    <div class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900">
            New <span class="text-[#25D366]">WhatsApp</span> Campaign
        </h1>
        <p class="mt-2 text-sm text-zinc-700">
            Upload a contact list, compose your message, test one send, then launch.
        </p>
    </div>

    {{-- Step indicator — pure CSS, no JS. 5 real steps; 'launched' is a terminal success screen. --}}
    @php
        $labels = ['Upload', 'Map', 'Compose', 'Test', 'Launch'];
        $current = $this->stepIndex; // 0..5 (5 = launched)
        $currentForUi = min($current, 4);
    @endphp
    <ol class="mb-8 flex items-center gap-2 text-xs font-medium select-none">
        @foreach ($labels as $i => $label)
            @php
                $isDone    = $i < $currentForUi || $current === 5;
                $isCurrent = $i === $currentForUi && $current !== 5;
            @endphp
            <li class="flex items-center gap-2 min-w-0">
                <span @class([
                    'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold',
                    'bg-emerald-600 text-white'                => $isDone,
                    'bg-emerald-600 text-white ring-4 ring-emerald-100' => $isCurrent,
                    'bg-zinc-200 text-zinc-600'                => ! $isDone && ! $isCurrent,
                ])>
                    @if ($isDone)
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    @else
                        {{ $i + 1 }}
                    @endif
                </span>
                <span @class([
                    'truncate',
                    'text-zinc-900'  => $isCurrent,
                    'text-zinc-700'  => $isDone,
                    'text-zinc-500'  => ! $isDone && ! $isCurrent,
                ])>{{ $label }}</span>
            </li>
            @if ($i < count($labels) - 1)
                <li class="flex-1 h-px bg-zinc-200"></li>
            @endif
        @endforeach
    </ol>

    {{-- Step 1 — Upload --}}
    @if ($step === 'upload')
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm space-y-6">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900">Upload contact list</h2>
                    <p class="mt-1 text-sm text-zinc-700">
                        CSV or Excel (.xlsx), up to 10 MB and 50,000 rows.
                    </p>
                </div>
                <a href="/samples/whatsapp-campaign-contacts.csv"
                   download
                   class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-800 hover:bg-zinc-50 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16" />
                    </svg>
                    Download sample CSV
                </a>
            </div>

            <div>
                <input type="file"
                       id="wa-wizard-file"
                       wire:model="file"
                       accept=".csv,.xlsx"
                       class="sr-only" />

                @if (! $file && ! $storedPath)
                    <label for="wa-wizard-file"
                           wire:loading.class="border-blue-400 bg-blue-50 animate-pulse"
                           wire:target="file"
                           class="flex flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed border-zinc-300 bg-zinc-50 px-6 py-10 cursor-pointer hover:border-emerald-500 hover:bg-emerald-50 transition">
                        <div wire:loading.remove wire:target="file" class="flex flex-col items-center gap-3">
                            <div class="rounded-full bg-emerald-600 p-3 text-white shadow-sm">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.9 5 5 0 019.9-1.09A3.5 3.5 0 0117 15.5H7z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 12v6m0-6l-2 2m2-2l2 2" />
                                </svg>
                            </div>
                            <div class="text-center">
                                <p class="text-sm font-semibold text-zinc-900">Click to choose a file</p>
                                <p class="mt-0.5 text-xs text-zinc-600">.csv or .xlsx up to 10 MB</p>
                            </div>
                        </div>
                        <div wire:loading wire:target="file" class="flex items-center gap-2 text-sm font-medium text-blue-700">
                            <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            Uploading…
                        </div>
                    </label>
                @else
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-zinc-900">
                                    {{ $file?->getClientOriginalName() ?? $originalName ?? 'file selected' }}
                                </p>
                                <p class="text-xs text-emerald-800">Ready to import</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <label for="wa-wizard-file"
                                   class="inline-flex items-center gap-1.5 rounded-md border border-zinc-300 bg-white px-3 py-1.5 text-xs font-medium text-zinc-800 hover:bg-zinc-50 cursor-pointer transition">
                                Replace
                            </label>
                            <button type="button"
                                    wire:click="removeFile"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 transition">
                                Remove
                            </button>
                        </div>
                    </div>
                @endif

                @error('file')
                    <p class="mt-2 flex items-center gap-1.5 text-sm text-red-700">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M4.93 19h14.14a2 2 0 001.75-3l-7.07-12a2 2 0 00-3.5 0l-7.07 12a2 2 0 001.75 3z" />
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Phone format guide — the format info persists into step 2 as a compact tip. --}}
            <div class="rounded-xl bg-zinc-50 border border-zinc-200 p-4">
                <h3 class="text-sm font-semibold text-zinc-900 mb-2">Phone number format</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                    <div>
                        <p class="font-semibold text-emerald-800 mb-1">✓ Accepted</p>
                        <ul class="space-y-0.5 text-zinc-800 font-mono">
                            <li><code>+201099887766</code> (E.164, always safe)</li>
                            <li><code>+20 10 9988 7766</code> (spaces ok)</li>
                            <li><code>+20-10-9988-7766</code> (dashes ok)</li>
                            <li><code>01099887766</code> (local — needs default country)</li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-semibold text-red-700 mb-1">✗ Rejected</p>
                        <ul class="space-y-0.5 text-zinc-800 font-mono">
                            <li>Empty rows</li>
                            <li>Numbers under 7 digits</li>
                            <li>Text like <code>not-a-number</code></li>
                            <li><code>2.011E+11</code> (Excel corrupted the column)</li>
                            <li>Local numbers with no default country set</li>
                        </ul>
                    </div>
                </div>
                <p class="mt-3 text-xs text-zinc-700">
                    <strong class="text-zinc-900">Excel tip:</strong>
                    format the phone column as <em>Text</em> before saving, or Excel will silently mangle long numbers into scientific notation and we'll reject the row.
                </p>
            </div>

            <div class="flex justify-end">
                <flux:button wire:click="advanceToMap" variant="primary" :disabled="! $file && ! $storedPath">
                    Next →
                </flux:button>
            </div>
        </div>

    {{-- Step 2 — Map --}}
    @elseif ($step === 'map')
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm space-y-4">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900">Map your columns</h2>
                <p class="mt-1 text-sm text-zinc-700">
                    Point us to the phone column and (optionally) the name column. Pick the default country for any local-format numbers.
                </p>
            </div>

            <flux:select wire:model="phoneColumn" label="Phone column" placeholder="— choose column —">
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

            <flux:select
                wire:model="defaultCountry"
                label="Default country"
                description="Used only for local-format numbers (like 01099887766). Numbers already in +E.164 always win.">
                @foreach ($this->countries as $c)
                    <flux:select.option value="{{ $c['iso2'] }}">{{ $c['name'] }} ({{ $c['dial'] }})</flux:select.option>
                @endforeach
            </flux:select>

            {{-- Compact reminder so users don't have to backtrack to step 1. --}}
            <div class="rounded-lg bg-zinc-50 border border-zinc-200 px-3 py-2 text-xs text-zinc-700">
                <strong class="text-zinc-900">Reminder:</strong>
                E.164 numbers (starting with <code class="font-mono">+</code>) are always safe. Rows we can't parse (empty, too short, or Excel scientific notation like <code class="font-mono">2.011E+11</code>) are counted separately and shown next.
            </div>

            <div class="flex justify-between pt-2">
                <flux:button wire:click="back" variant="ghost">← Back</flux:button>
                <flux:button wire:click="advanceToCompose" variant="primary">Import &amp; continue</flux:button>
            </div>
        </div>

    {{-- Step 3 — Compose --}}
    @elseif ($step === 'compose')
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm space-y-5">
            {{-- Import summary — surfaces skipped/invalid so users aren't surprised. --}}
            @php $totalTouched = $importedCount + $skippedCount + $invalidCount; @endphp
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2">
                    <p class="text-xs font-medium text-emerald-800">Imported</p>
                    <p class="text-xl font-bold text-emerald-900">{{ $importedCount }}</p>
                </div>
                <div @class([
                    'rounded-lg px-3 py-2 border',
                    'border-amber-200 bg-amber-50' => $skippedCount > 0,
                    'border-zinc-200 bg-zinc-50'   => $skippedCount === 0,
                ])>
                    <p @class(['text-xs font-medium', 'text-amber-800' => $skippedCount > 0, 'text-zinc-700' => $skippedCount === 0])>Duplicates skipped</p>
                    <p @class(['text-xl font-bold', 'text-amber-900' => $skippedCount > 0, 'text-zinc-900' => $skippedCount === 0])>{{ $skippedCount }}</p>
                </div>
                <div @class([
                    'rounded-lg px-3 py-2 border',
                    'border-red-200 bg-red-50'   => $invalidCount > 0,
                    'border-zinc-200 bg-zinc-50' => $invalidCount === 0,
                ])>
                    <p @class(['text-xs font-medium', 'text-red-800' => $invalidCount > 0, 'text-zinc-700' => $invalidCount === 0])>Invalid rows</p>
                    <p @class(['text-xl font-bold', 'text-red-900' => $invalidCount > 0, 'text-zinc-900' => $invalidCount === 0])>{{ $invalidCount }}</p>
                </div>
            </div>
            @if ($invalidCount > 0)
                <p class="text-xs text-red-800">
                    {{ $invalidCount }} row{{ $invalidCount === 1 ? '' : 's' }} couldn't be parsed. Most common causes: empty phone, Excel scientific notation (<code class="font-mono">2.011E+11</code>), or a local number without a matching default country. Go back to fix the file, or continue with the {{ $importedCount }} valid contact{{ $importedCount === 1 ? '' : 's' }}.
                </p>
            @endif

            <flux:input wire:model="campaignName" label="Campaign name" placeholder="August promo" />

            <flux:select wire:model="senderPageId" label="Send from">
                @foreach ($this->whatsappSenders as $p)
                    <flux:select.option value="{{ $p->id }}">{{ $p->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:textarea
                wire:model="body"
                label="Message"
                rows="6"
                description="Use {{ '{{name}}' }} to insert the contact's name and {{ '{{phone}}' }} for their phone. Max 2,000 characters."
                placeholder="Hi {{ '{{name}}' }}, we have something for you…" />

            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="jitterMin" type="number" label="Wait between messages — min (sec)" min="15" max="600" />
                <flux:input wire:model="jitterMax" type="number" label="Wait between messages — max (sec)" min="15" max="600" />
            </div>
            <p class="text-xs text-zinc-700 -mt-2">
                A random pause between {{ $jitterMin }}–{{ $jitterMax }} seconds is added between each message. Higher values are safer against WhatsApp rate-limiting.
            </p>

            <div class="flex justify-between pt-2">
                <flux:button wire:click="back" variant="ghost">← Back</flux:button>
                <flux:button wire:click="advanceToTest" variant="primary" :disabled="$importedCount === 0">
                    Test send →
                </flux:button>
            </div>
        </div>

    {{-- Step 4 — Test --}}
    @elseif ($step === 'test')
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm space-y-4">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900">Send one test message</h2>
                <p class="mt-1 text-sm text-zinc-700">
                    Verify the format and your WhatsApp connection before launching to {{ $importedCount }} contact{{ $importedCount === 1 ? '' : 's' }}. Use a real WhatsApp number in <strong>+E.164 format</strong> (e.g. <code class="font-mono text-zinc-800">+201099887766</code>).
                </p>
            </div>

            <flux:input wire:model="testPhone" label="Test phone (E.164)" placeholder="+201099887766" />
            <flux:input wire:model="testName" label="Test name" />

            <div class="flex items-center gap-3">
                <flux:button wire:click="sendTest" wire:loading.attr="disabled" wire:target="sendTest">
                    <span wire:loading.remove wire:target="sendTest">Send test</span>
                    <span wire:loading wire:target="sendTest">Sending…</span>
                </flux:button>
            </div>

            @if ($testResult === true)
                <div class="flex items-center gap-2 rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-2">
                    <svg class="h-4 w-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    <span class="text-sm text-emerald-900">Test message sent. Check WhatsApp on that number.</span>
                </div>
                <div class="flex justify-between pt-2">
                    <flux:button wire:click="back" variant="ghost">← Back</flux:button>
                    <flux:button wire:click="advanceToReview" variant="primary">Looks good — review →</flux:button>
                </div>
            @elseif ($testResult === false)
                <div class="flex items-start gap-2 rounded-lg bg-red-50 border border-red-200 px-3 py-2">
                    <svg class="h-4 w-4 mt-0.5 text-red-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M4.93 19h14.14a2 2 0 001.75-3l-7.07-12a2 2 0 00-3.5 0l-7.07 12a2 2 0 001.75 3z" /></svg>
                    <span class="text-sm text-red-900">Test failed: {{ $testError }}</span>
                </div>
                <div class="flex justify-between pt-2">
                    <flux:button wire:click="back" variant="ghost">← Back</flux:button>
                </div>
            @else
                <div class="flex justify-between pt-2">
                    <flux:button wire:click="back" variant="ghost">← Back</flux:button>
                </div>
            @endif
        </div>

    {{-- Step 5 — Review --}}
    @elseif ($step === 'review')
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900">Ready to launch</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-y-2 gap-x-6 text-sm">
                <div><dt class="text-zinc-600">Recipients</dt><dd class="font-semibold text-zinc-900">{{ $importedCount }}</dd></div>
                <div><dt class="text-zinc-600">Wait between messages</dt><dd class="font-semibold text-zinc-900">{{ $jitterMin }}–{{ $jitterMax }} sec</dd></div>
                <div class="sm:col-span-2"><dt class="text-zinc-600">Campaign name</dt><dd class="font-semibold text-zinc-900">{{ $campaignName }}</dd></div>
            </dl>
            <div class="flex justify-between pt-2">
                <flux:button wire:click="back" variant="ghost">← Back</flux:button>
                <flux:button wire:click="launch" variant="primary">Launch campaign</flux:button>
            </div>
        </div>

    {{-- Terminal — Launched --}}
    @elseif ($step === 'launched')
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-600 text-white mb-3">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
            </div>
            <h2 class="text-lg font-semibold text-emerald-900">Campaign launched</h2>
            <p class="mt-1 text-sm text-emerald-900">
                Sending is in progress with a {{ $jitterMin }}–{{ $jitterMax }} second wait between messages. You can close this page.
            </p>
        </div>
    @endif
</div>
