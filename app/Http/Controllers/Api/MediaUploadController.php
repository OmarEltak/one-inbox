<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ConvertAudioToOgg;
use App\Services\Media\MediaStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaUploadController extends Controller
{
    public function __construct(
        private readonly MediaStorage $storage,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $maxImage = (int) config('services.media.max_upload_image_bytes', 5 * 1024 * 1024);
        $maxAudio = (int) config('services.media.max_upload_audio_bytes', 16 * 1024 * 1024);

        $data = $request->validate([
            'file' => ['required', 'file', 'max:'.max($maxImage, $maxAudio) / 1024],
            'kind' => ['required', 'in:image,audio,video,document'],
        ]);

        $file = $data['file'];
        $kind = $data['kind'];

        // Per-kind size checks (validation above is the outer cap).
        if ($kind === 'image' && $file->getSize() > $maxImage) {
            return response()->json(['message' => 'Image exceeds 5 MB limit'], 422);
        }
        if ($kind === 'audio' && $file->getSize() > $maxAudio) {
            return response()->json(['message' => 'Audio exceeds 16 MB limit'], 422);
        }

        $team = $request->user()->currentTeam ?? $request->user()->teams()->first();
        abort_if($team === null, 403, 'No team context.');

        $asset = $this->storage->storeBytes(
            team: $team,
            bytes: file_get_contents($file->getRealPath()),
            mimeType: $file->getMimeType() ?? 'application/octet-stream',
            kind: $kind,
            originalFilename: $file->getClientOriginalName(),
        );

        // If audio came in as webm/opus (browser MediaRecorder default), convert to
        // WhatsApp-compatible .ogg out-of-band. The DB row is updated in place.
        if ($kind === 'audio' && in_array($asset->mime_type, ['audio/webm', 'audio/mp4'], true)) {
            ConvertAudioToOgg::dispatch($asset->id);
        }

        return response()->json([
            'id'        => $asset->id,
            'mime_type' => $asset->mime_type,
            'url'       => $this->storage->streamUrl($asset),
            'kind'      => $asset->kind,
        ]);
    }
}
