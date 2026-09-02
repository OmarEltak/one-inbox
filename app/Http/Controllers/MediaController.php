<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MediaAsset;
use App\Services\Media\MediaStorage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function __construct(
        private readonly MediaStorage $storage,
    ) {}

    /**
     * Stream a MediaAsset over HTTP with Range-request support.
     *
     * WHY RANGES MATTER: <audio> and <video> in Chromium/WebKit probe
     * playability by sending `Range: bytes=0-1` first. If the server
     * returns the full file with status 200 (no `Accept-Ranges: bytes`,
     * no 206 Partial Content), the browser assumes the resource isn't
     * seekable and silently refuses to play. Symptom: play button
     * flashes, no sound. That's the "won't play" bug we hit.
     */
    public function stream(Request $request, string $ulid): StreamedResponse
    {
        $asset = MediaAsset::find($ulid);
        abort_if($asset === null, 404);

        $absolute = $this->storage->absolutePath($asset);
        abort_unless(is_file($absolute), 404);

        $size  = filesize($absolute) ?: (int) $asset->size_bytes;
        $range = $request->header('Range');

        $start  = 0;
        $end    = $size - 1;
        $status = 200;

        if ($range && preg_match('/bytes=(\d*)-(\d*)/', $range, $m)) {
            if ($m[1] !== '') {
                $start = (int) $m[1];
            }
            if ($m[2] !== '') {
                $end = min((int) $m[2], $size - 1);
            }
            if ($start > $end || $start >= $size) {
                return response()->stream(fn () => null, 416, [
                    'Content-Range' => "bytes */{$size}",
                ]);
            }
            $status = 206;
        }

        $length = $end - $start + 1;

        $headers = [
            'Content-Type'   => $asset->mime_type ?: 'application/octet-stream',
            'Content-Length' => (string) $length,
            'Accept-Ranges'  => 'bytes',
            'Cache-Control'  => 'private, max-age=604800',
        ];
        if ($status === 206) {
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
        }

        return response()->stream(function () use ($absolute, $start, $length) {
            $fh = fopen($absolute, 'rb');
            fseek($fh, $start);
            $remaining = $length;
            while ($remaining > 0 && ! feof($fh)) {
                $chunk = fread($fh, min(8192, $remaining));
                echo $chunk;
                $remaining -= strlen($chunk);
                if (connection_status() !== CONNECTION_NORMAL) {
                    break;
                }
                @ob_flush();
                @flush();
            }
            fclose($fh);
        }, $status, $headers);
    }
}
