<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Page;
use App\Models\PagesPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PagesPost>
 */
class PagesPostFactory extends Factory
{
    protected $model = PagesPost::class;

    public function definition(): array
    {
        return [
            'page_id'             => Page::factory(),
            'platform_post_id'    => (string) $this->faker->unique()->numberBetween(100000, 999999),
            'created_at_platform' => now()->subDays(7),
            'first_seen_at'       => now(),
        ];
    }
}
