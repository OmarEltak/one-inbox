<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name'                    => fake()->company(),
            'slug'                    => fake()->unique()->slug(),
            'owner_id'                => User::factory(),
            'subscription_plan'       => 'free',
            'subscription_status'     => 'active',
            'ai_enabled'              => true,
            'ai_credits_used'         => 0,
            'ai_credits_limit'        => 1000,
        ];
    }
}
