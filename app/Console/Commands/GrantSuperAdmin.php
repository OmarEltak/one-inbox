<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GrantSuperAdmin extends Command
{
    protected $signature = 'super-admin:grant {email} {--revoke : Revoke instead of grant}';

    protected $description = 'Grant (or revoke) the platform super-admin flag on a user.';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $revoke = (bool) $this->option('revoke');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email {$email}.");

            return self::FAILURE;
        }

        $user->is_super_admin = ! $revoke;
        $user->save();

        $verb = $revoke ? 'revoked from' : 'granted to';
        $this->info("Super-admin {$verb} {$user->email} (id={$user->id}).");

        return self::SUCCESS;
    }
}
