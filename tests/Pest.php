<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Create a user attached to a fresh team and set it as their current team.
 * Returns [User, Team] tuple.
 *
 * @return array{0: \App\Models\User, 1: \App\Models\Team}
 */
function makeUserWithTeam(): array
{
    $user = \App\Models\User::factory()->create();
    $team = \App\Models\Team::create([
        'name'     => 'Test Team',
        'slug'     => 'team-'.\Illuminate\Support\Str::random(8),
        'owner_id' => $user->id,
    ]);
    $user->teams()->attach($team->id, ['role' => 'admin']);
    $user->forceFill(['current_team_id' => $team->id])->save();

    return [$user->fresh(), $team];
}

/**
 * Create an active email Page (sender) for a team with fake SMTP credentials.
 */
function makeEmailPage(\App\Models\Team $team, string $email = 'sender@example.com'): \App\Models\Page
{
    $account = \App\Models\ConnectedAccount::create([
        'team_id'          => $team->id,
        'platform'         => 'email',
        'platform_user_id' => $email,
        'name'             => $email,
        'access_token'     => encrypt('secret-password'),
        'scopes'           => ['imap', 'smtp'],
        'is_active'        => true,
        'connected_at'     => now(),
    ]);

    return \App\Models\Page::create([
        'connected_account_id' => $account->id,
        'team_id'              => $team->id,
        'platform'             => 'email',
        'platform_page_id'     => $email,
        'name'                 => $email,
        'page_access_token'    => 'secret-password',
        'category'             => 'email_inbox',
        'is_active'            => true,
        'metadata'             => [
            'smtp_host'       => 'smtp.example.com',
            'smtp_port'       => 587,
            'smtp_encryption' => 'tls',
            'imap_host'       => 'imap.example.com',
            'imap_port'       => 993,
            'imap_encryption' => 'ssl',
        ],
    ]);
}
