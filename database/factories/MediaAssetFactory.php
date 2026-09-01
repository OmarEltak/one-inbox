<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MediaAsset;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MediaAsset>
 */
class MediaAssetFactory extends Factory
{
    protected $model = MediaAsset::class;

    public function definition(): array
    {
        return [
            'team_id'           => Team::factory(),
            'disk'              => 'media',
            'path'              => 'test/'.Str::uuid()->toString().'.jpg',
            'original_filename' => 'photo.jpg',
            'mime_type'         => 'image/jpeg',
            'size_bytes'        => 12345,
            'kind'              => 'image',
            'duration_seconds'  => null,
            'checksum_sha256'   => hash('sha256', Str::uuid()->toString()),
            'metadata'          => [],
        ];
    }
}
