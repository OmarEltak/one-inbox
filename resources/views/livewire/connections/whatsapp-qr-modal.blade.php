<div x-on:open-whatsapp-qr.window="$wire.openModal()">
    <flux:modal wire:model="show" class="md:w-[26rem]" @close="$wire.closeModal()">
        <div class="space-y-5 text-center">

            {{-- Header --}}
            <div class="text-left">
                <flux:heading size="lg">Connect WhatsApp via QR</flux:heading>
                <flux:text class="mt-1">Scan with any WhatsApp Business number — no Meta setup required.</flux:text>
            </div>

            {{-- IDLE: show start button --}}
            @if($status === 'idle')
                <div class="py-4">
                    <flux:icon.device-phone-mobile class="mx-auto size-16 text-green-500 mb-4" />
                    <flux:text class="text-zinc-500 dark:text-zinc-400 mb-4">
                        Open <strong>WhatsApp Business</strong> on your phone, go to
                        <strong>Linked Devices</strong>, and scan the QR code that will appear.
                    </flux:text>
                    <flux:button variant="primary" wire:click="startConnection" class="w-full">
                        Generate QR Code
                    </flux:button>
                </div>

            {{-- CREATING: spinner while Evolution API sets up instance --}}
            @elseif($status === 'creating')
                <div class="py-10">
                    <flux:icon.arrow-path class="mx-auto size-10 text-green-500 animate-spin mb-3" />
                    <flux:text class="text-zinc-500">Setting up your connection…</flux:text>
                </div>

            {{-- QR PENDING: show QR image, poll every 2s --}}
            @elseif($status === 'qr_pending')
                <div wire:poll.2000ms="poll">
                    @if($qrDataUrl)
                        <div class="flex justify-center">
                            <img
                                src="{{ $qrDataUrl }}"
                                alt="WhatsApp QR Code"
                                class="w-56 h-56 rounded-xl border border-zinc-200 dark:border-zinc-700"
                            />
                        </div>
                        <flux:text class="text-xs text-zinc-400 mt-2">QR code refreshes every 20 seconds</flux:text>
                        <div class="mt-3 rounded-lg bg-green-50 dark:bg-green-900/20 p-3 text-sm text-green-700 dark:text-green-400">
                            Open WhatsApp → <strong>Linked Devices</strong> → <strong>Link a Device</strong> → Scan
                        </div>
                    @else
                        <div class="py-6">
                            <flux:icon.arrow-path class="mx-auto size-10 text-green-500 animate-spin mb-3" />
                            <flux:text class="text-zinc-500">Waiting for QR code…</flux:text>
                        </div>
                    @endif
                </div>

            {{-- CONNECTED: success state --}}
            @elseif($status === 'connected')
                <div class="py-4">
                    <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                        <flux:icon.check class="size-8 text-green-600 dark:text-green-400" />
                    </div>
                    <flux:heading size="lg" class="text-green-600 dark:text-green-400">Connected!</flux:heading>
                    <flux:text class="mt-1 text-zinc-600 dark:text-zinc-300">
                        <strong>{{ $connectedName }}</strong>
                        @if($connectedPhone && $connectedPhone !== $connectedName)
                            <span class="text-zinc-400"> ({{ $connectedPhone }})</span>
                        @endif
                    </flux:text>
                    <flux:text class="mt-3 text-sm text-zinc-500">Messages from this number will now appear in your inbox.</flux:text>
                    <flux:button variant="primary" wire:click="closeModal" class="w-full mt-4">Done</flux:button>
                </div>

            {{-- ERROR --}}
            @elseif($status === 'error')
                <div class="py-4">
                    <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                        <flux:icon.exclamation-triangle class="size-8 text-red-600 dark:text-red-400" />
                    </div>
                    <flux:text class="text-red-600 dark:text-red-400">{{ $errorMessage }}</flux:text>
                    <div class="mt-4 flex gap-2">
                        <flux:button wire:click="startConnection" variant="primary" class="flex-1">Try Again</flux:button>
                        <flux:button wire:click="closeModal" variant="ghost">Cancel</flux:button>
                    </div>
                </div>
            @endif

            {{-- Cancel link (shown during QR scan) --}}
            @if($status === 'qr_pending')
                <div class="pt-1">
                    <button wire:click="closeModal" class="text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                        Cancel
                    </button>
                </div>
            @endif
        </div>
    </flux:modal>
</div>
