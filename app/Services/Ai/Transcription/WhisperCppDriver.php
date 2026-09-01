<?php

declare(strict_types=1);

namespace App\Services\Ai\Transcription;

use App\Models\MediaAsset;
use App\Services\Media\MediaStorage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class WhisperCppDriver implements TranscriptionDriver
{
    public function __construct(
        private readonly MediaStorage $storage,
    ) {}

    public function name(): string
    {
        return 'whisper_cpp';
    }

    public function transcribe(MediaAsset $asset): ?string
    {
        $bin     = (string) config('services.whisper_cpp.bin');
        $model   = (string) config('services.whisper_cpp.model');
        $threads = (int)    config('services.whisper_cpp.threads', 2);

        $inputPath = $this->storage->absolutePath($asset);
        $wavPath   = sys_get_temp_dir().'/whisper-'.$asset->id.'.wav';
        $outStem   = sys_get_temp_dir().'/whisper-'.$asset->id;
        $outTxt    = $outStem.'.txt';

        // whisper.cpp requires 16 kHz mono PCM WAV.
        $ffmpeg = Process::timeout(30)->run([
            'ffmpeg', '-y', '-i', $inputPath, '-ar', '16000', '-ac', '1', '-c:a', 'pcm_s16le', $wavPath,
        ]);
        if (! $ffmpeg->successful()) {
            Log::warning('ffmpeg conversion failed for whisper input', ['stderr' => $ffmpeg->errorOutput()]);
            return null;
        }

        $whisper = Process::timeout(55)->run([
            $bin, '-m', $model, '-f', $wavPath, '-t', (string) $threads, '-otxt', '-of', $outStem,
        ]);

        @unlink($wavPath);

        if (! $whisper->successful()) {
            Log::warning('whisper.cpp failed', ['stderr' => $whisper->errorOutput()]);
            @unlink($outTxt);
            return null;
        }

        if (! is_file($outTxt)) {
            return null;
        }

        $text = trim((string) file_get_contents($outTxt));
        @unlink($outTxt);

        return $text !== '' ? $text : null;
    }
}
