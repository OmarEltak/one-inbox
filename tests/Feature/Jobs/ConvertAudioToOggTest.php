<?php

declare(strict_types=1);

use App\Jobs\ConvertAudioToOgg;
use App\Models\MediaAsset;
use App\Models\Team;
use App\Services\Media\MediaStorage;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('media');
    $this->team = Team::factory()->create();
});

it('runs ffmpeg and updates the asset mime type + path', function () {
    Process::fake([
        '*' => Process::result(exitCode: 0),
    ]);

    // Seed a webm asset.
    $webmBytes = random_bytes(100);
    $asset = app(MediaStorage::class)->storeBytes(
        $this->team, $webmBytes, 'audio/webm', 'audio', 'voice.webm'
    );

    // Simulate ffmpeg producing an output file: write bytes at the expected output path.
    Storage::disk('media')->put(
        preg_replace('/\.webm$/', '.ogg', $asset->path),
        random_bytes(80)
    );

    (new ConvertAudioToOgg($asset->id))->handle();

    $asset->refresh();
    expect($asset->mime_type)->toBe('audio/ogg')
        ->and($asset->path)->toEndWith('.ogg');

    Process::assertRan(fn (\Illuminate\Process\PendingProcess $p) =>
        str_contains(implode(' ', (array) $p->command), 'ffmpeg')
    );
});

it('does nothing if the asset is already ogg', function () {
    Process::fake();

    $ogg = MediaAsset::factory()->for($this->team)->create(['mime_type' => 'audio/ogg', 'path' => 't/a.ogg']);

    (new ConvertAudioToOgg($ogg->id))->handle();

    Process::assertNothingRan();
});
