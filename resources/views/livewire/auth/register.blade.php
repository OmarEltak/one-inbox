<x-layouts::auth>
    <div class="flex flex-col gap-6">
        {{-- Header --}}
        <div>
            <h1 class="serif text-3xl leading-tight text-ink">{{ __('Create your account') }}</h1>
            <p class="mt-2 text-sm text-ink/70">{{ __('Start managing all your conversations in one place') }}</p>
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
                <p class="text-xs text-ink/60">
                    {{ __('Min. 8 characters.') }}
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

            <button type="submit" data-test="register-user-button"
                class="inline-flex items-center justify-center gap-2 w-full bg-emer-600 text-white px-6 py-3 rounded-full font-semibold hover:bg-emer-700 transition cursor-pointer">
                {{ __('Create account') }}
            </button>
        </form>

        <p class="text-sm text-center text-ink/70">
            {{ __('Already have an account?') }}
            <a href="{{ route('login') }}" wire:navigate class="font-semibold text-emer-700 hover:text-emer-600">{{ __('Sign in') }}</a>
        </p>
    </div>
</x-layouts::auth>
