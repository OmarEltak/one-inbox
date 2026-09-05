<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'team_id'            => Team::factory(),
            'created_by'         => User::factory(),
            'platform'           => 'whatsapp',
            'name'               => $this->faker->words(3, true),
            'type'               => 'broadcast',
            'status'             => 'draft',
            'jitter_min_seconds' => 30,
            'jitter_max_seconds' => 60,
            'total_contacts'     => 0,
            'sent_count'         => 0,
            'reply_count'        => 0,
            'failed_count'       => 0,
            'opened_count'       => 0,
            'unsubscribed_count' => 0,
        ];
    }
}
