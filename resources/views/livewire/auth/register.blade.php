<x-layouts::auth>
    <div class="flex flex-col gap-6">
        {{-- Header --}}
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">Create your account</h1>
            <p class="mt-1 text-sm text-zinc-500">{{ __('Start managing all your conversations in one place') }}</p>
        </div>

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-5">
            @csrf

            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Full name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Your name')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="you@company.com"
            />

            <!-- Password -->
            <div class="flex flex-col gap-1">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Min. 8 characters')"
                    viewable
                />
                <p class="text-xs text-zinc-400">
                    {{ __('Min. 8 characters with uppercase, lowercase, and a number.') }}
                </p>
            </div>

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Re-enter password')"
                viewable
            />

            <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                {{ __('Create account') }}
            </flux:button>
        </form>

        <p class="text-sm text-center text-zinc-500">
            {{ __('Already have an account?') }}
            <flux:link :href="route('login')" wire:navigate class="font-medium">{{ __('Sign in') }}</flux:link>
        </p>
    </div>
</x-layouts::auth>
