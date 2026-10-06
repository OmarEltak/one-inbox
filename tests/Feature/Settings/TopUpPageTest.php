<?php

declare(strict_types=1);

use App\Livewire\Settings\TopUp;
use App\Mail\TopUpRequestedMail;
use App\Models\AiCreditLedgerEntry;
use App\Models\Team;
use App\Models\User;
use App\Services\Billing\AiCredits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Mirrors makeBillingTeam() in BillingPageTest — same shape so both suites
 * stay in sync. Owner + attached team + verified email + current_team set.
 */
function makeTopUpTeam(string $plan = 'starter', int $monthly = 500, int $wallet = 0): array
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $team = Team::create([
        'name'                     => 'Top Up Test Co',
        'slug'                     => 'team-' . Str::random(8),
        'owner_id'                 => $user->id,
        'subscription_plan'        => $plan,
        'onboarding_completed_at'  => now(),
    ]);
    $user->teams()->attach($team->id, ['role' => 'admin']);
    $user->forceFill(['current_team_id' => $team->id])->save();

    // Reset the auto-grant from Team::booted() so the test controls the balance.
    AiCreditLedgerEntry::query()->where('team_id', $team->id)->delete();
    Cache::flush();
    RateLimiter::clear("topup-notify:{$team->id}");

    $credits = app(AiCredits::class);
    if ($monthly > 0) {
        $credits->grant($team, $monthly, AiCreditLedgerEntry::BALANCE_MONTHLY, 'test_seed');
    }
    if ($wallet > 0) {
        $credits->grant($team, $wallet, AiCreditLedgerEntry::BALANCE_WALLET, 'test_seed');
    }

    return [$user->fresh(), $team->fresh()];
}

it('renders the top-up page for an authenticated team owner', function () {
    [$user] = makeTopUpTeam();

    $this->actingAs($user);

    $response = $this->get(route('settings.billing.top-up'));

    $response->assertOk()
        ->assertSeeLivewire(TopUp::class)
        ->assertSeeText(__('Credit packs'))
        ->assertSee((string) config('plans.manual_payment.whatsapp'));
});

it('renders the 4-tier ladder (Starter / Pro / Business + current-plan indicator)', function () {
    [$user] = makeTopUpTeam(plan: 'starter');

    $this->actingAs($user);

    $response = $this->get(route('settings.billing.top-up'));

    $response->assertOk()
        ->assertSee('data-test="plan-row-starter"', false)
        ->assertSee('data-test="plan-row-pro"', false)
        ->assertSee('data-test="plan-row-business"', false)
        // Current-plan badge visible for the starter team.
        ->assertSeeText(__('Current plan'));
});

it('redirects unauthenticated users away from the top-up page', function () {
    $this->get(route('settings.billing.top-up'))->assertRedirect(route('login'));
});

it('sends a TopUpRequestedMail to the configured owner email when notified with a valid product', function () {
    Mail::fake();

    [$user, $team] = makeTopUpTeam();

    Livewire::actingAs($user)
        ->test(TopUp::class)
        ->set('selectedProduct', 'pack:medium')
        ->call('notify')
        ->assertHasNoErrors();

    $expectedAddress = config('mail.admin_address', 'it@mishkahu.com');

    Mail::assertSent(TopUpRequestedMail::class, function (TopUpRequestedMail $mail) use ($team, $user, $expectedAddress): bool {
        return $mail->team->is($team)
            && $mail->requester->is($user)
            && $mail->product === 'pack:medium'
            && $mail->hasTo($expectedAddress);
    });
});

it('rate-limits a second click within 10 minutes and shows an amber warning', function () {
    Mail::fake();

    [$user, $team] = makeTopUpTeam();

    // First call — allowed, mail sent.
    Livewire::actingAs($user)
        ->test(TopUp::class)
        ->set('selectedProduct', 'pack:small')
        ->call('notify')
        ->assertHasNoErrors();

    Mail::assertSent(TopUpRequestedMail::class, 1);

    // Second call within the same decay window — must not send a second mail
    // and must surface a flash message naming the 10-minute window.
    $component = Livewire::actingAs($user)
        ->test(TopUp::class)
        ->set('selectedProduct', 'pack:small')
        ->call('notify')
        ->assertHasNoErrors();

    Mail::assertSent(TopUpRequestedMail::class, 1); // still 1 — the second was swallowed

    // The flash message mentions the 10-minute window and renders inside the
    // amber warning block (data-test="topup-warning-flash").
    $component
        ->assertSee('10 minutes')
        ->assertSeeHtml('data-test="topup-warning-flash"');
});

it('refuses to send mail when no product is selected and surfaces a validation error', function () {
    Mail::fake();

    [$user] = makeTopUpTeam();

    Livewire::actingAs($user)
        ->test(TopUp::class)
        ->set('selectedProduct', '')
        ->call('notify')
        ->assertHasErrors(['selectedProduct']);

    Mail::assertNothingSent();
});
