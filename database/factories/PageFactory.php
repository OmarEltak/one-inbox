<?php

namespace Database\Factories;

use App\Models\ConnectedAccount;
use App\Models\Page;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $team = Team::factory()->create();

        return [
            'connected_account_id' => ConnectedAccount::factory()->for($team)->create()->id,
            'team_id'              => $team->id,
            'platform'             => 'whatsapp',
            'platform_page_id'     => 'inst-' . $this->faker->unique()->numberBetween(1, 99999),
            'name'                 => $this->faker->name(),
            'page_access_token'    => encrypt('test-token-' . $this->faker->uuid()),
            'is_active'            => true,
        ];
    }
}
