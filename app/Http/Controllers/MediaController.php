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

    public function stream(Request $request, string $ulid): StreamedResponse
    {
        $asset = MediaAsset::find($ulid);
        abort_if($asset === null, 404);

        $absolute = $this->storage->absolutePath($asset);
        abort_unless(is_file($absolute), 404);

        return response()->stream(
            callback: function () use ($absolute) {
                $fh = fopen($absolute, 'rb');
                while (! feof($fh)) {
                    echo fread($fh, 8192);
                }
                fclose($fh);
            },
            status: 200,
            headers: [
                'Content-Type'   => $asset->mime_type,
                'Content-Length' => (string) $asset->size_bytes,
                'Cache-Control'  => 'private, max-age=604800',
            ],
        );
    }
}
