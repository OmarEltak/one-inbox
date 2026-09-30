<div class="p-4 sm:p-6 max-w-4xl mx-auto space-y-6">

    {{-- Header. Was designed for a dark app shell but the actual shell is
         light — all text-white/* was invisible. Full sweep to zinc palette
         per contrast-guardrails skill. --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">{{ __('New Email Campaign') }}</h1>
            <p class="mt-1 text-sm text-zinc-700">{{ __('Upload a CSV/Excel sheet of contacts and send a bulk email blast.') }}</p>
        </div>
        <a href="{{ route('campaigns.index') }}" wire:navigate
           class="text-sm font-medium text-zinc-700 hover:text-zinc-900">← {{ __('Back to campaigns') }}</a>
    </div>

    {{-- Step indicator. Contrast-safe pairs: emerald-600/white for current,
         emerald-100/emerald-900 for done, zinc-100/zinc-700 for future. --}}
    @php
        $steps = [
            'upload'   => __('1. Upload'),
            'map'      => __('2. Map columns'),
            'compose'  => __('3. Compose'),
            'review'   => __('4. Review'),
            'launched' => __('5. Launched'),
        ];
        $stepKeys = array_keys($steps);
        $current  = array_search($step, $stepKeys, true);
    @endphp
    <div class="flex items-center gap-2 text-xs flex-wrap">
        @foreach($steps as $key => $label)
            @php $i = array_search($key, $stepKeys, true); @endphp
            <div class="flex items-center gap-2">
                <span class="px-3 py-1.5 rounded-lg font-medium
                    {{ $i < $current ? 'bg-emerald-100 text-emerald-900' : '' }}
                    {{ $i === $current ? 'bg-emerald-600 text-white font-semibold shadow-sm' : '' }}
                    {{ $i > $current ? 'bg-zinc-100 text-zinc-700' : '' }}">
                    {{ $label }}
                </span>
                @if(!$loop->last)
                    <span class="text-zinc-400">→</span>
                @endif
            </div>
        @endforeach
    </div>

    {{-- STEP 1: UPLOAD --}}
    @if($step === 'upload')
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 sm:p-8 shadow-sm space-y-4">
            {{-- Import-size notice. Now on a LIGHT surface — use amber-50/amber-900
                 per contrast-guardrails skill (was amber-900/40 + amber-100 for dark). --}}
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 flex items-start gap-3">
                <svg class="h-5 w-5 shrink-0 text-amber-900 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.5m0 3v.01M4.93 19h14.14a2 2 0 001.75-2.98l-7.07-12a2 2 0 00-3.5 0l-7.07 12A2 2 0 004.93 19z"/>
                </svg>
                <p class="text-sm text-amber-900 leading-relaxed">
                    <strong class="font-semibold">{{ __('Current upload limit: 2 MB (~20,000 contacts)') }}</strong>
                    {{ __('while we ship a background import for larger lists. Need more?') }}
                    <a href="https://wa.me/201026361218?text=Hi%20Omar%2C%20I%20need%20to%20import%20more%20than%2020%2C000%20contacts%20for%20a%20campaign." target="_blank" rel="noopener" class="underline font-medium">{{ __('Message me on WhatsApp') }}</a>
                    {{ __('or email') }} <a href="mailto:support@ot1-pro.com" class="underline font-medium">support@ot1-pro.com</a>.
                </p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-zinc-900 mb-2">{{ __('CSV or Excel file') }}</label>
                <input type="file" wire:model="file"
                       accept=".csv,.txt,.xlsx"
                       class="block w-full text-sm text-zinc-900
                              file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                              file:text-sm file:font-semibold
                              file:bg-emerald-100 file:text-emerald-900
                              hover:file:bg-emerald-200 cursor-pointer" />
                <p class="text-xs text-zinc-700 mt-2">{{ __('.csv or .xlsx, up to 2 MB (~20,000 contacts).') }}</p>
                @error('file') <p class="text-xs text-red-700 mt-1">{{ $message }}</p> @enderror
            </div>

            <div wire:loading wire:target="file" class="text-sm text-zinc-700">{{ __('Uploading…') }}</div>

            @if($file)
                <button wire:click="uploadAndPreview"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-500 transition">
                    {{ __('Preview & map columns') }} →
                </button>
            @endif
        </div>
    @endif

    {{-- STEP 2: MAP --}}
    @if($step === 'map')
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900">{{ __('Map columns') }}</h2>
            <p class="text-sm text-zinc-700">{{ __('We detected :count columns. Tell us which one holds the email address.', ['count' => count($detectedHeaders)]) }}</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-zinc-900 mb-1">{{ __('Email column (required)') }}</label>
                    <select wire:model="emailColumn"
                            class="w-full bg-white border border-zinc-300 rounded-lg px-3 py-2 text-sm text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none">
                        <option value="">{{ __('— pick a column —') }}</option>
                        @foreach($detectedHeaders as $h)
                            <option value="{{ $h }}">{{ $h }}</option>
                        @endforeach
                    </select>
                    @error('emailColumn') <p class="text-xs text-red-700 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-900 mb-1">{{ __('Name column (optional)') }}</label>
                    <select wire:model="nameColumn"
                            class="w-full bg-white border border-zinc-300 rounded-lg px-3 py-2 text-sm text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none">
                        <option value="">{{ __('— none —') }}</option>
                        @foreach($detectedHeaders as $h)
                            <option value="{{ $h }}">{{ $h }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-900 mb-1">{{ __('Custom fields to keep (for @{{column_name}} variables)') }}</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($detectedHeaders as $h)
                        <label class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-zinc-50 border border-zinc-300 hover:bg-zinc-100 cursor-pointer text-xs text-zinc-900">
                            <input type="checkbox" wire:model="customColumns" value="{{ $h }}" class="rounded border-zinc-400 text-emerald-600 focus:ring-emerald-500">
                            {{ $h }}
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Preview --}}
            <div>
                <p class="text-xs font-semibold text-zinc-900 mb-2">{{ __('Preview (first :n rows)', ['n' => count($previewRows)]) }}</p>
                <div class="overflow-x-auto rounded-lg border border-zinc-200">
                    <table class="min-w-full text-xs">
                        <thead class="bg-zinc-50">
                            <tr>
                                @foreach($detectedHeaders as $h)
                                    <th class="px-3 py-2 text-left text-zinc-900 font-semibold">{{ $h }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($previewRows as $row)
                                <tr class="border-t border-zinc-100">
                                    @foreach($detectedHeaders as $h)
                                        <td class="px-3 py-1.5 text-zinc-700">{{ \Illuminate\Support\Str::limit($row[$h] ?? '', 40) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button wire:click="$set('step', 'upload')" class="px-4 py-2 rounded-xl text-sm font-medium text-zinc-800 bg-zinc-100 hover:bg-zinc-200 border border-zinc-200">← {{ __('Back') }}</button>
                <button wire:click="confirmMapAndImport"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-500 transition disabled:opacity-60">
                    <span wire:loading.remove wire:target="confirmMapAndImport">{{ __('Import contacts') }} →</span>
                    <span wire:loading wire:target="confirmMapAndImport">{{ __('Importing…') }}</span>
                </button>
            </div>
        </div>
    @endif

    {{-- STEP 3: COMPOSE --}}
    @if($step === 'compose')
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm space-y-4">
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 flex items-start gap-3">
                <svg class="h-5 w-5 shrink-0 text-emerald-900 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <p class="text-sm text-emerald-900 leading-relaxed">
                    {{ __('Imported') }} <strong>{{ $importedCount }}</strong> {{ __('contact(s), tagged') }} <code class="text-emerald-900 bg-emerald-100 px-1 py-0.5 rounded">{{ $importTag }}</code>.
                </p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-900 mb-1">{{ __('Campaign name') }}</label>
                <input type="text" wire:model="campaignName"
                       class="w-full bg-white border border-zinc-300 rounded-lg px-3 py-2 text-sm text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none">
                @error('campaignName') <p class="text-xs text-red-700 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-900 mb-1">{{ __('Sender (your connected email account)') }}</label>
                @if($this->emailSenders->isEmpty())
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                        {{ __('No connected email accounts.') }}
                        <a href="{{ route('connections.index') }}" wire:navigate class="underline font-medium">{{ __('Connect one') }} →</a>
                    </div>
                @else
                    <select wire:model="senderPageId"
                            class="w-full bg-white border border-zinc-300 rounded-lg px-3 py-2 text-sm text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none">
                        @foreach($this->emailSenders as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-900 mb-1">{{ __('Subject') }}</label>
                <input type="text" wire:model="subject"
                       placeholder="Quick question about @{{name}}'s setup"
                       class="w-full bg-white border border-zinc-300 rounded-lg px-3 py-2 text-sm text-zinc-900 placeholder:text-zinc-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none">
                @error('subject') <p class="text-xs text-red-700 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-900 mb-1">{{ __('Body') }}</label>
                <textarea wire:model="body" rows="8"
                          class="w-full bg-white border border-zinc-300 rounded-lg px-3 py-2 text-sm text-zinc-900 font-mono focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none"></textarea>
                <p class="text-xs text-zinc-700 mt-1">
                    {{ __('Variables:') }}
                    <code class="bg-zinc-100 text-zinc-900 px-1 py-0.5 rounded">@{{name}}</code>,
                    <code class="bg-zinc-100 text-zinc-900 px-1 py-0.5 rounded">@{{email}}</code>
                    @foreach($customColumns as $c)
                        @if($c)
                            @php $varToken = '{{' . $c . '}}'; @endphp
                            , <code class="bg-zinc-100 text-zinc-900 px-1 py-0.5 rounded">{{ $varToken }}</code>
                        @endif
                    @endforeach
                </p>
                @error('body') <p class="text-xs text-red-700 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-zinc-900 mb-1">{{ __('Daily cap') }}</label>
                    <input type="number" wire:model="dailyCap" min="1" max="10000"
                           class="w-full bg-white border border-zinc-300 rounded-lg px-3 py-2 text-sm text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-900 mb-1">{{ __('Jitter min (s)') }}</label>
                    <input type="number" wire:model="jitterMin" min="0" max="3600"
                           class="w-full bg-white border border-zinc-300 rounded-lg px-3 py-2 text-sm text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-900 mb-1">{{ __('Jitter max (s)') }}</label>
                    <input type="number" wire:model="jitterMax" min="0" max="3600"
                           class="w-full bg-white border border-zinc-300 rounded-lg px-3 py-2 text-sm text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none">
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-zinc-900">
                <input type="checkbox" wire:model="aiPersonalize" class="rounded border-zinc-400 text-emerald-600 focus:ring-emerald-500">
                {{ __('AI-personalize each email (uses Gemini; costs more)') }}
            </label>

            <div class="flex flex-wrap gap-2">
                <button wire:click="$set('step', 'map')" class="px-4 py-2 rounded-xl text-sm font-medium text-zinc-800 bg-zinc-100 hover:bg-zinc-200 border border-zinc-200">← {{ __('Back') }}</button>
                <button wire:click="gotoReview"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-500 transition">
                    {{ __('Review') }} →
                </button>
            </div>
        </div>
    @endif

    {{-- STEP 4: REVIEW --}}
    @if($step === 'review')
        @php $stats = $this->reviewStats; @endphp
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900">{{ __('Review & launch') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="rounded-xl bg-zinc-50 border border-zinc-200 p-4 text-center">
                    <p class="text-2xl font-bold text-zinc-900">{{ number_format($stats['total']) }}</p>
                    <p class="text-xs text-zinc-700 mt-1">{{ __('Recipients') }}</p>
                </div>
                <div class="rounded-xl bg-zinc-50 border border-zinc-200 p-4 text-center">
                    <p class="text-2xl font-bold text-zinc-900">{{ number_format($dailyCap) }}</p>
                    <p class="text-xs text-zinc-700 mt-1">{{ __('Per day cap') }}</p>
                </div>
                <div class="rounded-xl bg-zinc-50 border border-zinc-200 p-4 text-center">
                    <p class="text-2xl font-bold text-zinc-900">~{{ $stats['days'] }}</p>
                    <p class="text-xs text-zinc-700 mt-1">{{ $stats['days'] === 1 ? __('Day to send all') : __('Days to send all') }}</p>
                </div>
            </div>

            <div class="space-y-1 text-sm text-zinc-900">
                <div><span class="text-zinc-700">{{ __('Name') }}:</span> <strong>{{ $campaignName }}</strong></div>
                <div><span class="text-zinc-700">{{ __('Subject') }}:</span> <strong>{{ $subject }}</strong></div>
                <div><span class="text-zinc-700">{{ __('Sender') }}:</span> <strong>{{ optional($this->emailSenders->firstWhere('id', $senderPageId))->name ?? '—' }}</strong></div>
                <div><span class="text-zinc-700">{{ __('AI personalize') }}:</span> <strong>{{ $aiPersonalize ? __('yes') : __('no') }}</strong></div>
            </div>

            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 flex items-start gap-3">
                <svg class="h-5 w-5 shrink-0 text-amber-900 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-amber-900 leading-relaxed">
                    {{ __('Each email includes a mandatory unsubscribe link and an open-tracking pixel. Recipients who unsubscribe are saved to your team suppression list and skipped in all future campaigns.') }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button wire:click="$set('step', 'compose')" class="px-4 py-2 rounded-xl text-sm font-medium text-zinc-800 bg-zinc-100 hover:bg-zinc-200 border border-zinc-200">← {{ __('Back') }}</button>
                <button wire:click="launch"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-500 transition disabled:opacity-60">
                    <span wire:loading.remove wire:target="launch">{{ __('Launch campaign') }} 🚀</span>
                    <span wire:loading wire:target="launch">{{ __('Scheduling…') }}</span>
                </button>
            </div>
        </div>
    @endif

    {{-- STEP 5: LAUNCHED --}}
    @if($step === 'launched')
        <div class="rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm text-center space-y-4">
            <div class="size-16 mx-auto rounded-full bg-emerald-100 flex items-center justify-center">
                <svg class="size-8 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h2 class="text-xl font-bold text-zinc-900">{{ __('Campaign launched') }}</h2>
            <p class="text-sm text-zinc-700">{{ __('Your email blast is scheduled. The dispatcher runs every minute and will respect your daily cap and jitter settings.') }}</p>
            <div class="flex flex-wrap gap-2 justify-center">
                <a href="{{ route('campaigns.show', $createdCampaignId) }}" wire:navigate
                   class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-500 transition">{{ __('View progress') }}</a>
                <a href="{{ route('campaigns.index') }}" wire:navigate
                   class="px-5 py-2.5 rounded-xl text-sm font-medium text-zinc-800 bg-zinc-100 hover:bg-zinc-200 border border-zinc-200">{{ __('All campaigns') }}</a>
            </div>
        </div>
    @endif
</div>
