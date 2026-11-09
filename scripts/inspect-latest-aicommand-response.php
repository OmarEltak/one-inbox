<?php

declare(strict_types=1);

/**
 * Dump the latest few AiCommand rows for user 3 (Omar) to see the raw AI
 * response that's leaking JSON into the UI bubble. Then run ActionParser
 * against it to see what parse() returns.
 */

use App\Models\AiCommand;
use App\Services\AiChat\ActionParser;

$parser = new ActionParser();

$cmds = AiCommand::where('user_id', 3)
    ->orderByDesc('id')
    ->take(5)
    ->get();

echo "Latest 5 AiCommand rows for user 3:\n\n";
foreach ($cmds as $cmd) {
    echo "══════════════════════════════════════════════\n";
    echo "id={$cmd->id}  status={$cmd->status}  created_at={$cmd->created_at?->toIso8601String()}\n";
    echo "command: " . mb_substr((string) $cmd->command, 0, 120) . "\n";
    echo "--- RAW response (first 1200 chars) ---\n";
    echo mb_substr((string) $cmd->response, 0, 1200) . "\n";
    echo "--- parse() OUTPUT ---\n";
    $parsed = $parser->parse((string) $cmd->response);
    echo "  pending: " . ($parsed['pending'] ? json_encode($parsed['pending'], JSON_UNESCAPED_UNICODE) : 'null') . "\n";
    echo "  auto: " . json_encode($parsed['auto'], JSON_UNESCAPED_UNICODE) . "\n";
    echo "  cleaned_response (first 800 chars):\n" . mb_substr($parsed['cleaned_response'], 0, 800) . "\n";
    echo "--- clean() OUTPUT (first 800 chars) ---\n";
    echo mb_substr($parser->clean((string) $cmd->response), 0, 800) . "\n";
    echo "--- diff? ---\n";
    echo (trim($parsed['cleaned_response']) === trim($parser->clean((string) $cmd->response))) ? "parse() and clean() produce SAME cleaned output" : "parse() and clean() DIFFER";
    echo "\n\n";
}
