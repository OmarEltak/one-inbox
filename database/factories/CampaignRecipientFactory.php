<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignRecipientFactory extends Factory
{
    protected $model = CampaignRecipient::class;

    public function definition(): array
    {
        return [
            'campaign_id'  => Campaign::factory(),
            'channel'      => 'email',
            'email'        => $this->faker->unique()->safeEmail(),
            'status'       => 'pending',
            'attempts'     => 0,
            'scheduled_at' => now(),
        ];
    }
}
