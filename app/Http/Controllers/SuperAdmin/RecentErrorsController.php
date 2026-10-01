<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * Read-only: the newest ERROR/CRITICAL entries from laravel.log, for super-admins.
 *
 * 500s on prod kept stalling on "run this SSH grep". This returns, per error, the
 * timestamp, the exception message (truncated) and the first stack frame inside our
 * own code (app/, resources/, compiled views) — enough to locate the bug — without
 * dumping full traces.
 */
class RecentErrorsController extends Controller
{
    private const TAIL_BYTES = 3_000_000;
    private const MAX_ERRORS = 15;

    public function __invoke(): JsonResponse
    {
        $path = config('logging.channels.single.path', storage_path('logs/laravel.log'));

        if (! is_readable($path)) {
            return response()->json(['file' => $path, 'error' => 'log file not readable'], 200);
        }

        $size = filesize($path);
        $fh = fopen($path, 'r');
        fseek($fh, max(0, $size - self::TAIL_BYTES));
        $tail = stream_get_contents($fh);
        fclose($fh);

        // Split into entries at each "[YYYY-MM-DD HH:MM:SS] env.LEVEL:" header.
        $parts = preg_split('/^(?=\[\d{4}-\d{2}-\d{2}[ T][\d:.+\-]+\] \w+\.[A-Z]+:)/m', $tail) ?: [];

        $errors = [];
        foreach (array_reverse($parts) as $entry) {
            if (! preg_match('/^\[([^\]]+)\] (\w+)\.(ERROR|CRITICAL|ALERT|EMERGENCY): (.*)$/m', $entry, $m)) {
                continue;
            }

            preg_match('/^#\d+ (\S*(?:\/app\/|\/resources\/|\/storage\/framework\/views\/)\S*)/m', $entry, $frame);
            preg_match('/\(\w*code: \d+\): .*? at (\S+:\d+)\)/', $entry, $at);

            $errors[] = [
                'time'      => $m[1],
                'level'     => $m[3],
                'message'   => Str::limit(trim($m[4]), 700),
                'thrown_at' => $at[1] ?? null,
                'first_app_frame' => $frame[1] ?? null,
            ];

            if (count($errors) >= self::MAX_ERRORS) {
                break;
            }
        }

        return response()->json([
            'file'          => $path,
            'size_bytes'    => $size,
            'scanned_bytes' => min($size, self::TAIL_BYTES),
            'errors'        => $errors,
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
