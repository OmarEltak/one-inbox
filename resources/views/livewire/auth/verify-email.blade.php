<x-layouts::auth>
    <div class="mt-4 flex flex-col gap-6">
        <p class="text-center text-sm text-ink/80 leading-relaxed">
            {{ __('Please verify your email address by clicking on the link we just emailed to you.') }}
        </p>

        @if (session('status') == 'verification-link-sent')
            <p class="text-center font-medium text-sm text-emer-700">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </p>
        @endif

        <div class="flex flex-col items-center justify-between space-y-3">
            <form method="POST" action="{{ route('verification.send') }}" class="w-full">
                @csrf
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 w-full bg-emer-600 text-white px-6 py-3 rounded-full font-semibold hover:bg-emer-700 transition cursor-pointer">
                    {{ __('Resend verification email') }}
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" data-test="logout-button"
                    class="text-sm text-ink/70 hover:text-ink underline underline-offset-2 cursor-pointer">
                    {{ __('Log out') }}
                </button>
            </form>
        </div>
    </div>
</x-layouts::auth>
