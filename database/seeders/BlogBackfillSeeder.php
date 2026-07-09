<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Backfills blog posts that exist locally but are missing on prod.
 *
 * Data lives alongside this file. Idempotent: updateOrCreate(slug).
 */
class BlogBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $path = __DIR__ . '/data/blog-backfill.json';

        if (! is_file($path)) {
            throw new RuntimeException("Missing data file: {$path}");
        }

        $rows = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        foreach ($rows as $row) {
            Post::updateOrCreate(['slug' => $row['slug']], $row);
        }

        $this->command?->info(sprintf('BlogBackfillSeeder: upserted %d posts.', count($rows)));
    }
}
