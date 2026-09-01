<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\MediaAsset;
use App\Models\Team;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class MediaStorage
{
    private const DEFAULT_DISK = 'media';

    public function storeBytes(
        Team $team,
        string $bytes,
        string $mimeType,
        string $kind,
        ?string $originalFilename = null,
        ?int $durationSeconds = null,
        array $metadata = [],
    ): MediaAsset {
        $checksum = hash('sha256', $bytes);

        // Dedup within team.
        $existing = MediaAsset::where('team_id', $team->id)
            ->where('checksum_sha256', $checksum)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $extension = $this->extensionFor($mimeType, $originalFilename);
        $ulid = (string) Str::ulid();
        $path = sprintf(
            '%d/%s/%s.%s',
            $team->id,
            now()->format('Y/m'),
            $ulid,
            $extension,
        );

        Storage::disk(self::DEFAULT_DISK)->put($path, $bytes);

        return MediaAsset::create([
            'id'                => $ulid,
            'team_id'           => $team->id,
            'disk'              => self::DEFAULT_DISK,
            'path'              => $path,
            'original_filename' => $originalFilename,
            'mime_type'         => $mimeType,
            'size_bytes'        => strlen($bytes),
            'kind'              => $kind,
            'duration_seconds'  => $durationSeconds,
            'checksum_sha256'   => $checksum,
            'metadata'          => $metadata,
        ]);
    }

    public function streamUrl(MediaAsset $asset): string
    {
        $ttl = (int) config('services.media.signed_url_ttl_days', 7);

        return URL::temporarySignedRoute(
            'media.stream',
            now()->addDays($ttl),
            ['ulid' => $asset->id],
        );
    }

    public function readBytes(MediaAsset $asset): string
    {
        return Storage::disk($asset->disk)->get($asset->path)
            ?? throw new \RuntimeException("Media asset file missing on disk: {$asset->id}");
    }

    public function absolutePath(MediaAsset $asset): string
    {
        return Storage::disk($asset->disk)->path($asset->path);
    }

    private function extensionFor(string $mimeType, ?string $originalFilename): string
    {
        if ($originalFilename !== null) {
            $ext = pathinfo($originalFilename, PATHINFO_EXTENSION);
            if ($ext !== '') {
                return strtolower($ext);
            }
        }

        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png'               => 'png',
            'image/gif'               => 'gif',
            'image/webp'              => 'webp',
            'audio/ogg'               => 'ogg',
            'audio/mpeg', 'audio/mp3' => 'mp3',
            'audio/wav', 'audio/wave' => 'wav',
            'audio/webm'              => 'webm',
            'audio/mp4', 'audio/m4a'  => 'm4a',
            'video/mp4'               => 'mp4',
            'application/pdf'         => 'pdf',
            default                   => 'bin',
        };
    }
}
