<?php

namespace Database\Factories;

use App\Models\ConnectedAccount;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConnectedAccount>
 */
class ConnectedAccountFactory extends Factory
{
    protected $model = ConnectedAccount::class;

    public function definition(): array
    {
        return [
            'team_id'         => Team::factory(),
            'platform'        => 'whatsapp',
            'platform_user_id' => 'user-' . $this->faker->unique()->numberBetween(1, 99999),
            'name'            => $this->faker->name(),
            'email'           => $this->faker->email(),
            'access_token'    => encrypt('access-token-' . $this->faker->uuid()),
            'is_active'       => true,
            'connected_at'    => now(),
        ];
    }
}
