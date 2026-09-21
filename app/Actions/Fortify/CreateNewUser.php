<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            $team = Team::create([
                'name' => $user->name . "'s Team",
                'slug' => Str::slug($user->name) . '-' . Str::random(6),
                'owner_id' => $user->id,
            ]);

            $team->members()->attach($user->id, ['role' => 'admin']);
            $user->update(['current_team_id' => $team->id]);

            Session::flash('heron_event', [
                'name' => 'signup_completed',
                'payload' => ['method' => 'email'],
            ]);

            // Phase A onboarding: send new signups to the "Meet Your AI" playground
            // instead of straight to /dashboard. Fortify honors the `url.intended`
            // session key when its default RegisterResponse builds the redirect.
            // See tasks/onboarding-activation-plan.md → Phase A → Route flow.
            Session::put('url.intended', route('onboarding.meet-your-ai'));

            return $user;
        });
    }
}
