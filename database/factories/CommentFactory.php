<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Page;
use App\Models\PagesPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        $page = Page::factory()->create();
        $post = PagesPost::factory()->create(['page_id' => $page->id]);

        return [
            'page_id'               => $page->id,
            'pages_post_id'         => $post->id,
            'platform_comment_id'   => (string) $this->faker->unique()->numberBetween(1000000, 9999999),
            'parent_comment_id'     => null,
            'commenter_platform_id' => (string) $this->faker->unique()->numberBetween(100, 999999),
            'commenter_name'        => $this->faker->name(),
            'text'                  => $this->faker->sentence(),
            'received_at'           => now(),
        ];
    }
}
