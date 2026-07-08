<x-layouts::auth>
    <div class="flex flex-col gap-6">
        {{-- Header --}}
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">{{ __('Welcome back') }}</h1>
            <p class="mt-1 text-sm text-zinc-500">{{ __('Sign in to your account') }}</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="you@company.com"
            />

            <!-- Password -->
            <div class="flex flex-col gap-1">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm font-medium text-zinc-700">{{ __('Password') }}</span>
                    @if (Route::has('password.request'))
                        <flux:link class="text-xs" :href="route('password.request')" wire:navigate>
                            {{ __('Forgot password?') }}
                        </flux:link>
                    @endif
                </div>
                <flux:input
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                {{ __('Sign in') }}
            </flux:button>
        </form>

        @if (Route::has('register'))
            <p class="text-sm text-center text-zinc-500">
                {{ __("Don't have an account?") }}
                <flux:link :href="route('register')" wire:navigate class="font-medium">{{ __('Sign up free') }}</flux:link>
            </p>
        @endif
    </div>
</x-layouts::auth>
