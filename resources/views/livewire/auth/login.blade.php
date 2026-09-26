<x-layouts::auth>
    <div class="flex flex-col gap-6">
        {{-- Header --}}
        <div>
            <h1 class="serif text-3xl leading-tight text-ink">{{ __('Welcome back') }}</h1>
            <p class="mt-2 text-sm text-ink/70">{{ __('Sign in to your account') }}</p>
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
                    <span class="text-sm font-medium text-ink">{{ __('Password') }}</span>
                    @if (Route::has('password.request'))
                        <a class="text-xs font-medium text-emer-700 hover:text-emer-600 underline underline-offset-2" href="{{ route('password.request') }}" wire:navigate>
                            {{ __('Forgot password?') }}
                        </a>
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

            <button type="submit" data-test="login-button"
                class="inline-flex items-center justify-center gap-2 w-full bg-emer-600 text-white px-6 py-3 rounded-full font-semibold hover:bg-emer-700 transition cursor-pointer">
                {{ __('Sign in') }}
            </button>
        </form>

        @if (Route::has('register'))
            <p class="text-sm text-center text-ink/70">
                {{ __("Don't have an account?") }}
                <a href="{{ route('register') }}" wire:navigate class="font-semibold text-emer-700 hover:text-emer-600">{{ __('Sign up free') }}</a>
            </p>
        @endif
    </div>
</x-layouts::auth>
