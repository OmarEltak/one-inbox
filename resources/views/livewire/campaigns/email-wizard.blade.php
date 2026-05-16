<div class="p-6 max-w-4xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ __('New Email Campaign') }}</h1>
            <p class="mt-1 text-sm text-white/40">{{ __('Upload a CSV/Excel sheet of contacts and send a bulk email blast.') }}</p>
        </div>
        <a href="{{ route('campaigns.index') }}" wire:navigate
           class="text-sm text-white/60 hover:text-white">← Back to campaigns</a>
    </div>

    {{-- Step indicator --}}
    @php
        $steps = [
            'upload'   => '1. Upload',
            'map'      => '2. Map columns',
            'compose'  => '3. Compose',
            'review'   => '4. Review',
            'launched' => '5. Launched',
        ];
        $stepKeys = array_keys($steps);
        $current  = array_search($step, $stepKeys, true);
    @endphp
    <div class="flex items-center gap-2 text-xs">
        @foreach($steps as $key => $label)
            @php $i = array_search($key, $stepKeys, true); @endphp
            <div class="flex items-center gap-2">
                <span class="px-3 py-1.5 rounded-lg
                    {{ $i < $current ? 'bg-green-500/20 text-green-300' : '' }}
                    {{ $i === $current ? 'bg-[#7C3AED]/30 text-[#C27AFF] font-semibold' : '' }}
                    {{ $i > $current ? 'bg-white/[0.04] text-white/40' : '' }}">
                    {{ $label }}
                </span>
                @if(!$loop->last)
                    <span class="text-white/20">→</span>
                @endif
            </div>
        @endforeach
    </div>

    {{-- STEP 1: UPLOAD --}}
    @if($step === 'upload')
        <div class="aio-card rounded-2xl p-8 space-y-4">
            <div>
                <label class="block text-sm font-semibold text-white mb-2">CSV or Excel file</label>
                <input type="file" wire:model="file"
                       accept=".csv,.txt,.xlsx"
                       class="block w-full text-sm text-white/80
                              file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                              file:text-sm file:font-semibold
                              file:bg-[#7C3AED]/20 file:text-[#C27AFF]
                              hover:file:bg-[#7C3AED]/30 cursor-pointer" />
                <p class="text-xs text-white/40 mt-2">.csv or .xlsx, up to 10 MB, max 50,000 rows.</p>
                @error('file') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div wire:loading wire:target="file" class="text-sm text-white/60">Uploading…</div>

            @if($file)
                <button wire:click="uploadAndPreview"
                        wire:loading.attr="disabled"
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white aio-btn-primary">
                    Preview &amp; map columns →
                </button>
            @endif
        </div>
    @endif

    {{-- STEP 2: MAP --}}
    @if($step === 'map')
        <div class="aio-card rounded-2xl p-6 space-y-4">
            <h2 class="text-lg font-semibold text-white">Map columns</h2>
            <p class="text-sm text-white/50">We detected {{ count($detectedHeaders) }} columns. Tell us which one holds the email address.</p>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-white/70 mb-1">Email column (required)</label>
                    <select wire:model="emailColumn"
                            class="w-full bg-white/[0.04] border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
                        <option value="">— pick a column —</option>
                        @foreach($detectedHeaders as $h)
                            <option value="{{ $h }}">{{ $h }}</option>
                        @endforeach
                    </select>
                    @error('emailColumn') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-white/70 mb-1">Name column (optional)</label>
                    <select wire:model="nameColumn"
                            class="w-full bg-white/[0.04] border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
                        <option value="">— none —</option>
                        @foreach($detectedHeaders as $h)
                            <option value="{{ $h }}">{{ $h }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-white/70 mb-1">Custom fields to keep (for {{ '{{column_name}}' }} variables)</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($detectedHeaders as $h)
                        <label class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/[0.04] border border-white/10 cursor-pointer text-xs text-white/80">
                            <input type="checkbox" wire:model="customColumns" value="{{ $h }}" class="rounded">
                            {{ $h }}
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Preview --}}
            <div>
                <p class="text-xs font-semibold text-white/70 mb-2">Preview (first {{ count($previewRows) }} rows)</p>
                <div class="overflow-x-auto rounded-lg border border-white/10">
                    <table class="min-w-full text-xs">
                        <thead class="bg-white/[0.04]">
                            <tr>
                                @foreach($detectedHeaders as $h)
                                    <th class="px-3 py-2 text-left text-white/70 font-semibold">{{ $h }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($previewRows as $row)
                                <tr class="border-t border-white/5">
                                    @foreach($detectedHeaders as $h)
                                        <td class="px-3 py-1.5 text-white/70">{{ \Illuminate\Support\Str::limit($row[$h] ?? '', 40) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex gap-2">
                <button wire:click="$set('step', 'upload')" class="px-4 py-2 rounded-xl text-sm text-white/70 bg-white/[0.04]">← Back</button>
                <button wire:click="confirmMapAndImport"
                        wire:loading.attr="disabled"
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white aio-btn-primary">
                    <span wire:loading.remove wire:target="confirmMapAndImport">Import contacts →</span>
                    <span wire:loading wire:target="confirmMapAndImport">Importing…</span>
                </button>
            </div>
        </div>
    @endif

    {{-- STEP 3: COMPOSE --}}
    @if($step === 'compose')
        <div class="aio-card rounded-2xl p-6 space-y-4">
            <flux:callout variant="success" icon="check-circle">
                Imported <strong>{{ $importedCount }}</strong> contact{{ $importedCount === 1 ? '' : 's' }} (tagged <code class="text-[#C27AFF]">{{ $importTag }}</code>).
            </flux:callout>

            <div>
                <label class="block text-xs font-semibold text-white/70 mb-1">Campaign name</label>
                <input type="text" wire:model="campaignName"
                       class="w-full bg-white/[0.04] border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
                @error('campaignName') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-white/70 mb-1">Sender (your connected email account)</label>
                @if($this->emailSenders->isEmpty())
                    <flux:callout variant="warning" icon="exclamation-triangle">
                        No connected email accounts. <a href="{{ route('connections.index') }}" wire:navigate class="underline text-[#C27AFF]">Connect one →</a>
                    </flux:callout>
                @else
                    <select wire:model="senderPageId"
                            class="w-full bg-white/[0.04] border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
                        @foreach($this->emailSenders as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div>
                <label class="block text-xs font-semibold text-white/70 mb-1">Subject</label>
                <input type="text" wire:model="subject"
                       placeholder="Quick question about {{ '{{name}}' }}'s setup"
                       class="w-full bg-white/[0.04] border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
                @error('subject') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-white/70 mb-1">Body</label>
                <textarea wire:model="body" rows="8"
                          class="w-full bg-white/[0.04] border border-white/10 rounded-lg px-3 py-2 text-sm text-white font-mono"></textarea>
                <p class="text-xs text-white/40 mt-1">
                    Variables: <code>{{ '{{name}}' }}</code>, <code>{{ '{{email}}' }}</code>
                    @foreach($customColumns as $c)
                        @if($c), <code>{{ '{{'.$c.'}}' }}</code>@endif
                    @endforeach
                </p>
                @error('body') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-white/70 mb-1">Daily cap</label>
                    <input type="number" wire:model="dailyCap" min="1" max="10000"
                           class="w-full bg-white/[0.04] border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-white/70 mb-1">Jitter min (s)</label>
                    <input type="number" wire:model="jitterMin" min="0" max="3600"
                           class="w-full bg-white/[0.04] border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-white/70 mb-1">Jitter max (s)</label>
                    <input type="number" wire:model="jitterMax" min="0" max="3600"
                           class="w-full bg-white/[0.04] border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-white/80">
                <input type="checkbox" wire:model="aiPersonalize" class="rounded">
                AI-personalize each email (uses Gemini; costs more)
            </label>

            <div class="flex gap-2">
                <button wire:click="$set('step', 'map')" class="px-4 py-2 rounded-xl text-sm text-white/70 bg-white/[0.04]">← Back</button>
                <button wire:click="gotoReview"
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white aio-btn-primary">
                    Review →
                </button>
            </div>
        </div>
    @endif

    {{-- STEP 4: REVIEW --}}
    @if($step === 'review')
        @php $stats = $this->reviewStats; @endphp
        <div class="aio-card rounded-2xl p-6 space-y-4">
            <h2 class="text-lg font-semibold text-white">Review &amp; launch</h2>
            <div class="grid grid-cols-3 gap-3">
                <div class="aio-card rounded-xl p-4 text-center">
                    <p class="text-2xl font-bold text-white">{{ number_format($stats['total']) }}</p>
                    <p class="text-xs text-white/40 mt-1">Recipients</p>
                </div>
                <div class="aio-card rounded-xl p-4 text-center">
                    <p class="text-2xl font-bold text-white">{{ number_format($dailyCap) }}</p>
                    <p class="text-xs text-white/40 mt-1">Per day cap</p>
                </div>
                <div class="aio-card rounded-xl p-4 text-center">
                    <p class="text-2xl font-bold text-white">~{{ $stats['days'] }}</p>
                    <p class="text-xs text-white/40 mt-1">Day{{ $stats['days'] === 1 ? '' : 's' }} to send all</p>
                </div>
            </div>

            <div class="space-y-1 text-sm text-white/70">
                <div><span class="text-white/40">Name:</span> {{ $campaignName }}</div>
                <div><span class="text-white/40">Subject:</span> {{ $subject }}</div>
                <div><span class="text-white/40">Sender:</span> {{ optional($this->emailSenders->firstWhere('id', $senderPageId))->name ?? '—' }}</div>
                <div><span class="text-white/40">AI personalize:</span> {{ $aiPersonalize ? 'yes' : 'no' }}</div>
            </div>

            <flux:callout variant="warning" icon="information-circle">
                Each email includes a mandatory unsubscribe link and an open-tracking pixel.
                Recipients who unsubscribe are saved to your team suppression list and skipped in all future campaigns.
            </flux:callout>

            <div class="flex gap-2">
                <button wire:click="$set('step', 'compose')" class="px-4 py-2 rounded-xl text-sm text-white/70 bg-white/[0.04]">← Back</button>
                <button wire:click="launch"
                        wire:loading.attr="disabled"
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white aio-btn-primary">
                    <span wire:loading.remove wire:target="launch">Launch campaign 🚀</span>
                    <span wire:loading wire:target="launch">Scheduling…</span>
                </button>
            </div>
        </div>
    @endif

    {{-- STEP 5: LAUNCHED --}}
    @if($step === 'launched')
        <div class="aio-card rounded-2xl p-8 text-center space-y-4">
            <div class="size-16 mx-auto rounded-full bg-green-500/20 flex items-center justify-center">
                <svg class="size-8 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h2 class="text-xl font-bold text-white">Campaign launched</h2>
            <p class="text-sm text-white/60">Your email blast is scheduled. The dispatcher runs every minute and will respect your daily cap and jitter settings.</p>
            <div class="flex gap-2 justify-center">
                <a href="{{ route('campaigns.show', $createdCampaignId) }}" wire:navigate
                   class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white aio-btn-primary">View progress</a>
                <a href="{{ route('campaigns.index') }}" wire:navigate
                   class="px-5 py-2.5 rounded-xl text-sm text-white/70 bg-white/[0.04]">All campaigns</a>
            </div>
        </div>
    @endif
</div>
