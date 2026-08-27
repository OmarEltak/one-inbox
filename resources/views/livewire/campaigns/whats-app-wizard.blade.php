<div class="max-w-3xl mx-auto py-8">
    <h1 class="text-2xl font-semibold mb-6">New WhatsApp Campaign</h1>

    @if ($step === 'upload')
        <div class="space-y-4">
            <flux:input type="file" wire:model="file" accept=".csv,.xlsx" />
            <flux:button wire:click="advanceToMap" variant="primary">Next</flux:button>
        </div>

    @elseif ($step === 'map')
        <div class="space-y-4">
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
            <flux:input wire:model="defaultCountry" label="Default country (ISO2)" />
            <flux:button wire:click="advanceToCompose" variant="primary">Import &amp; Continue</flux:button>
        </div>

    @elseif ($step === 'compose')
        <div class="space-y-4">
            <p class="text-sm text-zinc-500">Imported {{ $importedCount }} contacts.</p>
            <flux:input wire:model="campaignName" label="Campaign name" />
            <flux:select wire:model="senderPageId" label="Send from">
                @foreach ($this->whatsappSenders as $p)
                    <flux:select.option value="{{ $p->id }}">{{ $p->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:textarea wire:model="body" label="Message" rows="6" />
            <div class="flex gap-3">
                <flux:input wire:model="jitterMin" type="number" label="Jitter min (sec)" />
                <flux:input wire:model="jitterMax" type="number" label="Jitter max (sec)" />
            </div>
            <flux:button wire:click="advanceToTest" variant="primary">Test send</flux:button>
        </div>

    @elseif ($step === 'test')
        <div class="space-y-4">
            <p class="text-sm text-zinc-500">Send one test message to verify the format and connection.</p>
            <flux:input wire:model="testPhone" label="Test phone (E.164)" placeholder="+201026361218" />
            <flux:input wire:model="testName"  label="Test name" />
            <flux:button wire:click="sendTest">Send test</flux:button>

            @if ($testResult === true)
                <p class="text-green-600">✅ Test message sent. Check WhatsApp.</p>
                <flux:button wire:click="advanceToReview" variant="primary">Looks good — review</flux:button>
            @elseif ($testResult === false)
                <p class="text-red-600">❌ Test failed: {{ $testError }}</p>
            @endif
        </div>

    @elseif ($step === 'review')
        <div class="space-y-4">
            <p>Ready to send to <strong>{{ $importedCount }}</strong> recipients with {{ $jitterMin }}–{{ $jitterMax }}s jitter.</p>
            <flux:button wire:click="launch" variant="primary">Launch campaign</flux:button>
        </div>

    @elseif ($step === 'launched')
        <div class="space-y-4">
            <p class="text-green-600">Campaign launched. Sending in progress.</p>
        </div>
    @endif
</div>
