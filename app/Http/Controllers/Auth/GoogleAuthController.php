<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect()->route('login')
                ->with('error', 'Google sign-in failed. Please try again.');
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            // Link google_id if not yet linked (existing email/password user)
            if (! $user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }
        } else {
            $user = DB::transaction(function () use ($googleUser) {
                $newUser = User::create([
                    'name'              => $googleUser->getName() ?? $googleUser->getEmail(),
                    'email'             => $googleUser->getEmail(),
                    'google_id'         => $googleUser->getId(),
                    'email_verified_at' => now(),
                ]);

                $team = Team::create([
                    'name'     => ($newUser->name) . "'s Team",
                    'slug'     => Str::slug($newUser->name) . '-' . Str::random(6),
                    'owner_id' => $newUser->id,
                ]);

                $team->members()->attach($newUser->id, ['role' => 'admin']);
                $newUser->update(['current_team_id' => $team->id]);

                return $newUser;
            });
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }
}
