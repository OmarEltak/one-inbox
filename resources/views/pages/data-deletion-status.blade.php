<x-layouts.marketing
    :title="__('Data Deletion Status — One Inbox')"
    :description="__('Status of your One Inbox data deletion request.')">

    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-2xl px-6">
            <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Data Deletion Request') }}</h1>
            <p class="mt-3 text-zinc-600 dark:text-zinc-400">{{ __('This page shows the current status of a data deletion request submitted on your behalf by Meta.') }}</p>

            <dl class="mt-10 divide-y divide-zinc-200 rounded-2xl border border-zinc-200 bg-zinc-50 dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-900/50">
                <div class="grid grid-cols-3 gap-4 px-6 py-4">
                    <dt class="text-sm font-medium text-zinc-500">{{ __('Confirmation code') }}</dt>
                    <dd class="col-span-2 font-mono text-sm">{{ $deletion->confirmation_code }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-4">
                    <dt class="text-sm font-medium text-zinc-500">{{ __('Status') }}</dt>
                    <dd class="col-span-2 text-sm">
                        @if($deletion->status === 'completed')
                            <span class="inline-flex items-center gap-2 rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700 dark:bg-green-900/40 dark:text-green-300">
                                {{ __('Completed') }}
                            </span>
                        @elseif($deletion->status === 'failed')
                            <span class="inline-flex items-center gap-2 rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                {{ __('Failed — please contact support') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                {{ __('Pending') }}
                            </span>
                        @endif
                    </dd>
                </div>
                <div class="grid grid-cols-3 gap-4 px-6 py-4">
                    <dt class="text-sm font-medium text-zinc-500">{{ __('Requested') }}</dt>
                    <dd class="col-span-2 text-sm">{{ $deletion->requested_at?->format('F j, Y H:i \U\T\C') }}</dd>
                </div>
                @if($deletion->completed_at)
                <div class="grid grid-cols-3 gap-4 px-6 py-4">
                    <dt class="text-sm font-medium text-zinc-500">{{ __('Completed') }}</dt>
                    <dd class="col-span-2 text-sm">{{ $deletion->completed_at?->format('F j, Y H:i \U\T\C') }}</dd>
                </div>
                @endif
            </dl>

            <div class="mt-10 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="font-semibold">{{ __('What was deleted') }}</h2>
                <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('When status reads "Completed", every record associated with the requested user ID has been removed: connected account, contact profile, conversations, messages, and lead score events. This action is permanent and cannot be reversed.') }}
                </p>
            </div>

            <div class="mt-6 text-sm text-zinc-500">
                <p>
                    {{ __('Need help? Contact us on') }}
                    <a href="https://wa.me/201026361218" class="text-purple-600 hover:underline">WhatsApp</a>
                    {{ __('or via the') }}
                    <a href="{{ route('contact') }}" class="text-purple-600 hover:underline">{{ __('contact page') }}</a>.
                </p>
            </div>
        </div>
    </section>

</x-layouts.marketing>
