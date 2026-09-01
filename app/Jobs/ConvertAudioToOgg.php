<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\MediaAsset;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class ConvertAudioToOgg implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(public string $mediaAssetId)
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $asset = MediaAsset::find($this->mediaAssetId);
        if ($asset === null || $asset->mime_type === 'audio/ogg') {
            return;
        }

        $inputPath  = Storage::disk($asset->disk)->path($asset->path);
        $outputPath = preg_replace('/\.[^.]+$/', '.ogg', $inputPath);

        $result = Process::timeout(45)->run([
            'ffmpeg',
            '-y',
            '-i', $inputPath,
            '-c:a', 'libopus',
            '-b:a', '32k',
            '-vbr', 'on',
            $outputPath,
        ]);

        if (! $result->successful()) {
            Log::error('ffmpeg webm→ogg conversion failed', [
                'asset_id'   => $asset->id,
                'stderr'     => $result->errorOutput(),
                'exit_code'  => $result->exitCode(),
            ]);
            $this->fail(new \RuntimeException('ffmpeg failed'));
            return;
        }

        // Update DB row to point at the new ogg path.
        $newRelativePath = preg_replace('/\.[^.]+$/', '.ogg', $asset->path);
        $newBytes = filesize($outputPath);

        $asset->update([
            'mime_type'  => 'audio/ogg',
            'path'       => $newRelativePath,
            'size_bytes' => $newBytes,
        ]);

        // Optionally delete original webm (safe — it's been converted).
        @unlink($inputPath);
    }
}
