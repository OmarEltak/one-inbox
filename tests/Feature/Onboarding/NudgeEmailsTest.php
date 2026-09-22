<?php

declare(strict_types=1);

use App\Jobs\SendOnboardingNudge;
use App\Mail\OnboardingNudge1MeetAi;
use App\Mail\OnboardingNudge2ConnectPage;
use App\Mail\OnboardingNudge3PersonalFromOmar;
use App\Mail\OnboardingNudge4EmailFounder;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

// Phase C — onboarding nudge sweep. Covers scheduling gates, per-team +
// global kill switches, idempotency ledger, and the unsubscribe route.

function makeNudgeTeam(int $ageHours = 2, ?string $email = null): array
{
    $user = User::factory()->create($email ? ['email' => $email] : []);
    $team = Team::create([
        'name'     => 'Nudge Test Co',
        'slug'     => 'team-'.Str::random(8),
        'owner_id' => $user->id,
    ]);
    $user->teams()->attach($team->id, ['role' => 'admin']);
    $user->forceFill(['current_team_id' => $team->id])->save();

    // Backdate the team's created_at so the age gate is satisfied.
    Team::query()->where('id', $team->id)
        ->update(['created_at' => now()->subHours($ageHours)]);

    return [$user->fresh(), $team->fresh()];
}

function attachActivePage(Team $team): Page
{
    $account = ConnectedAccount::create([
        'team_id'          => $team->id,
        'platform'         => 'facebook',
        'platform_user_id' => 'fb-'.Str::random(8),
        'name'             => 'Test',
        'access_token'     => encrypt('t'),
        'scopes'           => ['pages_messaging'],
        'is_active'        => true,
        'connected_at'     => now(),
    ]);

    return Page::create([
        'connected_account_id' => $account->id,
        'team_id'              => $team->id,
        'platform'             => 'facebook',
        'platform_page_id'     => 'page-'.Str::random(8),
        'name'                 => 'Test Page',
        'page_access_token'    => encrypt('t'),
        'is_active'            => true,
    ]);
}

function attachMessage(Team $team): Message
{
    $page = attachActivePage($team);
    $conv = Conversation::create([
        'team_id'                  => $team->id,
        'page_id'                  => $page->id,
        'platform'                 => 'facebook',
        'platform_conversation_id' => 'conv-'.Str::random(8),
        'status'                   => 'open',
    ]);

    return Message::create([
        'conversation_id' => $conv->id,
        'direction'       => 'outbound',
        'sender_type'     => 'user',
        'content_type'    => 'text',
        'content'         => 'hi',
    ]);
}

beforeEach(function () {
    Mail::fake();
    // Baseline kill-switch state: enabled globally.
    config()->set('services.onboarding_nudges.enabled', true);
});

it('queues Nudge1 at +1h if onboarding not completed', function () {
    [$user, $team] = makeNudgeTeam(ageHours: 2);

    (new SendOnboardingNudge())->handle();

    Mail::assertQueued(OnboardingNudge1MeetAi::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });

    expect($team->fresh()->nudge_1_sent_at)->not->toBeNull();
});

it('does not queue Nudge1 if onboarding is already completed', function () {
    [, $team] = makeNudgeTeam(ageHours: 2);
    $team->forceFill(['onboarding_completed_at' => now()])->save();

    (new SendOnboardingNudge())->handle();

    Mail::assertNotQueued(OnboardingNudge1MeetAi::class);
});

it('queues Nudge2 at +1d if no Page connected and Nudge1 already sent', function () {
    [$user, $team] = makeNudgeTeam(ageHours: 26);
    $team->forceFill([
        'onboarding_completed_at' => now(),   // skip Nudge1 gate
        'nudge_1_sent_at'         => now()->subDay(),
    ])->save();

    (new SendOnboardingNudge())->handle();

    Mail::assertQueued(OnboardingNudge2ConnectPage::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});

it('queues Nudge3 as plain text at +3d if still no messages', function () {
    [$user, $team] = makeNudgeTeam(ageHours: 24 * 3 + 2);
    $team->forceFill([
        'onboarding_completed_at' => now(),
        'nudge_1_sent_at'         => now()->subDays(3),
        'nudge_2_sent_at'         => now()->subDays(2),
    ])->save();
    attachActivePage($team); // clear the Nudge2 gate

    (new SendOnboardingNudge())->handle();

    Mail::assertQueued(OnboardingNudge3PersonalFromOmar::class, function ($mail) use ($user) {
        $rendered = $mail->render();

        // Content is plain-text — does NOT include HTML doctype/chrome.
        expect(stripos($rendered, '<!DOCTYPE'))->toBeFalse();
        expect(stripos($rendered, '<html'))->toBeFalse();

        return $mail->hasTo($user->email);
    });
});

it('queues Nudge4 at +7d and body contains NO calendar/book/schedule CTA', function () {
    [$user, $team] = makeNudgeTeam(ageHours: 24 * 7 + 2);
    $team->forceFill([
        'onboarding_completed_at' => now(),
        'nudge_1_sent_at'         => now()->subDays(7),
        'nudge_2_sent_at'         => now()->subDays(6),
        'nudge_3_sent_at'         => now()->subDays(4),
    ])->save();
    attachActivePage($team);

    (new SendOnboardingNudge())->handle();

    Mail::assertQueued(OnboardingNudge4EmailFounder::class, function ($mail) use ($user) {
        $rendered = $mail->render();

        // Anti-regression: this nudge must NOT include a call/calendar CTA.
        expect(stripos($rendered, 'book a call'))->toBeFalse();
        expect(stripos($rendered, 'book a meeting'))->toBeFalse();
        expect(stripos($rendered, 'calendly'))->toBeFalse();
        expect(stripos($rendered, 'schedule a call'))->toBeFalse();

        return $mail->hasTo($user->email);
    });
});

it('respects per-team disable_onboarding_nudges kill switch at ANY schedule', function () {
    // Try each stage — none should send.
    foreach ([2, 26, 24 * 3 + 2, 24 * 7 + 2] as $ageHours) {
        Mail::fake();

        [, $team] = makeNudgeTeam(ageHours: $ageHours);
        $team->forceFill(['disable_onboarding_nudges' => true])->save();

        (new SendOnboardingNudge())->handle();

        Mail::assertNothingQueued();
    }
});

it('respects the global services.onboarding_nudges.enabled kill switch', function () {
    config()->set('services.onboarding_nudges.enabled', false);
    makeNudgeTeam(ageHours: 2);

    (new SendOnboardingNudge())->handle();

    Mail::assertNothingQueued();
});

it('is idempotent — dispatching twice in a day sends only ONE email per stage', function () {
    [$user, $team] = makeNudgeTeam(ageHours: 2);

    (new SendOnboardingNudge())->handle();
    (new SendOnboardingNudge())->handle();

    Mail::assertQueuedCount(1);
});

it('sends nothing when the team owner has no email', function () {
    [$user, $team] = makeNudgeTeam(ageHours: 2);
    // Blank the owner's email via raw DB update (validation-safe).
    \Illuminate\Support\Facades\DB::table('users')
        ->where('id', $user->id)
        ->update(['email' => '']);

    (new SendOnboardingNudge())->handle();

    Mail::assertNothingQueued();
});

it('unsubscribe route flips disable_onboarding_nudges and blocks future sends', function () {
    [, $team] = makeNudgeTeam(ageHours: 2);

    $url = URL::signedRoute(
        'onboarding.nudges.unsubscribe',
        ['team' => $team->id],
        now()->addDays(30),
    );

    $this->get($url)->assertOk();

    expect($team->fresh()->disable_onboarding_nudges)->toBeTrue();

    Mail::fake();
    (new SendOnboardingNudge())->handle();
    Mail::assertNothingQueued();
});

it('rejects the unsubscribe route without a valid signature', function () {
    [, $team] = makeNudgeTeam();

    $this->get('/onboarding/nudges/unsubscribe/'.$team->id)
        ->assertStatus(403);
});

it('Nudge1 envelope from is the founder personal email (omareltak7@gmail.com)', function () {
    [$user, $team] = makeNudgeTeam();
    $mail = new OnboardingNudge1MeetAi($user, $team, 'https://example.com/u');

    $envelope = $mail->envelope();

    expect($envelope->from->address)->toBe('omareltak7@gmail.com');
});

it('Nudge3 uses text-only content (no HTML view)', function () {
    [$user, $team] = makeNudgeTeam();
    $mail = new OnboardingNudge3PersonalFromOmar($user, $team, 'https://example.com/u');
    $mail->unsubscribeUrl = 'https://example.com/u'; // ensure property set for view

    $rendered = $mail->render();
    // Plain-text mail rendered as plain text — no <html>, no <!DOCTYPE>.
    expect(stripos($rendered, '<!DOCTYPE'))->toBeFalse();
    expect(stripos($rendered, '<html'))->toBeFalse();
    // Should look Gmail-typed — starts with lowercase greeting per template.
    expect($rendered)->toContain("Omar");
});
