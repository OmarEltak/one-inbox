<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Seeds 10 Egyptian Arabic blog posts from a JSON payload.
 *
 * The JSON lives alongside this file so the same content deploys to every
 * environment. Uses updateOrCreate so re-running the seeder is idempotent.
 *
 * Run once per environment:
 *   php artisan db:seed --class=EgyptianArabicPostSeeder --force
 */
class EgyptianArabicPostSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/egyptian-arabic-posts.json');

        if (! File::exists($path)) {
            $this->command?->error("Missing data file: {$path}");
            return;
        }

        $posts = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        foreach ($posts as $data) {
            $data['published_at'] = Carbon::parse($data['published_at']);
            Post::updateOrCreate(['slug' => $data['slug']], $data);
        }

        $this->command?->info('Seeded ' . count($posts) . ' Egyptian Arabic blog posts.');
    }
}
