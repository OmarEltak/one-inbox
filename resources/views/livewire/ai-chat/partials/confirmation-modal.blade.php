{{--
    Phase D — Expensive-action confirmation modal (spec §3.6).

    Opened by AiChat Livewire via Flux::modal('deep-analysis-confirm')->show()
    whenever AiCredits::charge() throws ExpensiveActionRequiresConfirmationException.
    Reads $pendingExpensiveAction (cost, balance_after, description) + the
    bound $autoDeductOptIn checkbox.

    CLAUDE.md pin #6: use <flux:modal name="..."> + Flux::modal()->show()/close()
    only — $this->dispatch('open-modal', ...) silently no-ops in Flux 2.x.
    CLAUDE.md pin #12: every string here MUST have an ar.json entry.
--}}
<flux:modal name="deep-analysis-confirm" class="w-full max-w-md !bg-white dark:!bg-white">
    @if($pendingExpensiveAction)
        <div class="space-y-5">
            <div>
                <h3 class="text-lg font-semibold text-zinc-900">
                    {{ __('This will use :count AI credits', ['count' => $pendingExpensiveAction['cost']]) }}
                </h3>
                <p class="text-sm text-zinc-700 mt-1" dir="auto">
                    {{ __('You\'ll have :count left after.', ['count' => $pendingExpensiveAction['balance_after']]) }}
                </p>
                @if(! empty($pendingExpensiveAction['description']))
                    <p class="text-xs text-zinc-500 mt-2" dir="auto">
                        {{ $pendingExpensiveAction['description'] }}
                    </p>
                @endif
            </div>

            <label class="flex items-start gap-2 text-xs text-zinc-700 cursor-pointer select-none">
                <input
                    type="checkbox"
                    wire:model.live="autoDeductOptIn"
                    class="mt-0.5 size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500"
                />
                <span>
                    {{ __('Always auto-deduct for actions ≥ 5 credits (don\'t ask me again)') }}
                </span>
            </label>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-200">
                <flux:button
                    variant="ghost"
                    wire:click="cancelExpensiveAction"
                    wire:loading.attr="disabled"
                >
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button
                    variant="primary"
                    wire:click="confirmExpensiveAction"
                    wire:loading.attr="disabled"
                    wire:target="confirmExpensiveAction"
                >
                    <span wire:loading.remove wire:target="confirmExpensiveAction">{{ __('Confirm') }}</span>
                    <span wire:loading wire:target="confirmExpensiveAction">{{ __('Running...') }}</span>
                </flux:button>
            </div>
        </div>
    @endif
</flux:modal>
