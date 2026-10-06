<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Billing;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TopUp;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', Profile::class)->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/password', Password::class)->name('user-password.edit');
    Route::livewire('settings/appearance', Appearance::class)->name('appearance.edit');
    Route::livewire('settings/billing', Billing::class)->name('settings.billing');

    // Phase F — user-facing manual-payment top-up page. Shows pricing, PayPal
    // + bank + WhatsApp instructions, and a rate-limited "notify Omar" trigger
    // that emails him so he can confirm the payment and grant credits via
    // /super-admin/billing (Phase E). No card capture lives here.
    Route::get('settings/billing/top-up', TopUp::class)->name('settings.billing.top-up');

    Route::livewire('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
