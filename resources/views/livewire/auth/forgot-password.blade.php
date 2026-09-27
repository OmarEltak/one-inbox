<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email Address')"
                type="email"
                required
                autofocus
                placeholder="email@example.com"
            />

            <button type="submit" data-test="email-password-reset-link-button"
                class="inline-flex items-center justify-center gap-2 w-full bg-emer-600 text-white px-6 py-3 rounded-full font-semibold hover:bg-emer-700 transition cursor-pointer">
                {{ __('Email password reset link') }}
            </button>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-ink/70">
            <span>{{ __('Or, return to') }}</span>
            <a href="{{ route('login') }}" wire:navigate class="font-semibold text-emer-700 hover:text-emer-600">{{ __('log in') }}</a>
        </div>
    </div>
</x-layouts::auth>
