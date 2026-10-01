<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;

function errorsUser(bool $superAdmin): User
{
    $user = User::factory()->create(['is_super_admin' => $superAdmin]);
    $team = Team::factory()->create(['owner_id' => $user->id]);
    $user->teams()->attach($team->id, ['role' => 'admin']);
    $user->forceFill(['current_team_id' => $team->id])->save();

    return $user->fresh();
}

it('is forbidden for non-super-admins', function () {
    $this->actingAs(errorsUser(false))->get(route('super-admin.errors'))->assertForbidden();
});

it('returns the newest errors first with message and first app frame', function () {
    $log = tempnam(sys_get_temp_dir(), 'log');
    file_put_contents($log, <<<'LOG'
[2026-10-01 17:00:00] production.INFO: all good
[2026-10-01 17:01:00] production.ERROR: Undefined array key "x" {"userId":3,"exception":"[object] (ErrorException(code: 0): Undefined array key \"x\" at /var/www/ot1-pro.com/app/Livewire/Inbox/Index.php:120)
[stacktrace]
#0 /var/www/ot1-pro.com/vendor/laravel/framework/src/Foo.php(10): bar()
#1 /var/www/ot1-pro.com/app/Livewire/Inbox/Index.php(120): baz()
"}
[2026-10-01 17:02:00] production.ERROR: Second failure {"exception":"[object] (RuntimeException(code: 0): Second failure at /var/www/ot1-pro.com/resources/views/livewire/inbox/index.blade.php:9)"}
LOG);
    config(['logging.channels.single.path' => $log]);

    $this->actingAs(errorsUser(true))
        ->get(route('super-admin.errors'))
        ->assertOk()
        ->assertJsonCount(2, 'errors')
        ->assertJsonPath('errors.0.time', '2026-10-01 17:02:00')
        ->assertJsonPath('errors.1.thrown_at', '/var/www/ot1-pro.com/app/Livewire/Inbox/Index.php:120')
        ->assertJsonPath('errors.1.first_app_frame', '/var/www/ot1-pro.com/app/Livewire/Inbox/Index.php(120):');

    unlink($log);
});
