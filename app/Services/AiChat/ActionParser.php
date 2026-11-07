<?php

namespace App\Services\AiChat;

/**
 * Pure parser for AI-chat action blocks.
 *
 * The AI chat UI expects the model to wrap write-actions in a fenced
 *   ```pending_action { ... }```
 * block and auto-exec actions in a fenced
 *   ```action { ... }```
 * block. Reality is messier — models sometimes emit bare JSON or wrap it in a
 * plain ``` fence or a ```json fence instead. When that happens the chat UI
 * silently drops the action and the operator sees the model say "sent" while
 * nothing actually went out. This parser accepts the primary fenced forms AND
 * a strict bare-JSON fallback so we don't lose actions to formatting drift.
 *
 * The parser is deliberately defensive about the fallback:
 *   - A pending-action candidate must be a valid JSON object with a top-level
 *     `action` key whose value is in PENDING_ACTIONS.
 *   - A save-memory candidate must be a valid JSON object with a top-level
 *     `action` of `save_memory` AND (fenced OR clearly isolated — on its own
 *     line/paragraph, not inline inside prose).
 *   - Random prose that mentions the word "action" or JSON-looking fragments
 *     with unrecognized actions are ignored.
 */
class ActionParser
{
    /** Write-actions that require user confirmation before dispatching. */
    public const PENDING_ACTIONS = [
        'send_message',
        'send_bulk_message',
        'pause_ai',
        'resume_ai',
        'pause_campaign',
        'resume_campaign',
    ];

    /** Actions the UI auto-executes without confirmation. */
    public const AUTO_ACTIONS = [
        'save_memory',
    ];

    /**
     * Parse an AI response into (pending action, auto-exec actions, cleaned text).
     *
     * @return array{pending: ?array<string,mixed>, auto: array<int,array<string,mixed>>, cleaned_response: string}
     */
    public function parse(string $response): array
    {
        $cleaned = $response;
        $pending = null;
        $auto = [];

        // 1. PRIMARY: ```pending_action { ... } ``` — the format the prompt asks for.
        if (preg_match('/```pending_action\s*(\{.+?\})\s*```/s', $cleaned, $m)) {
            $decoded = $this->tryDecode($m[1]);
            if ($decoded !== null && $this->isPending($decoded)) {
                $pending = $decoded;
                $cleaned = (string) preg_replace('/```pending_action\s*\{.+?\}\s*```/s', '', $cleaned);
            }
        }

        // 2. PRIMARY: ```action { ... } ``` — auto-exec (save_memory).
        if (preg_match_all('/```action\s*(\{.+?\})\s*```/s', $cleaned, $matches)) {
            foreach ($matches[1] as $jsonStr) {
                $decoded = $this->tryDecode($jsonStr);
                if ($decoded !== null && $this->isAuto($decoded)) {
                    $auto[] = $decoded;
                }
            }
            $cleaned = (string) preg_replace('/```action\s*\{.+?\}\s*```/s', '', $cleaned);
        }

        // 3. FALLBACK: scan for any JSON object that looks like one of our
        //    actions but was emitted bare or inside a plain ``` / ```json
        //    fence. We must iterate candidates and only strip the ones we
        //    accept, so prose containing unrelated braces survives intact.
        foreach ($this->jsonCandidates($cleaned) as $cand) {
            [$jsonStr, $context] = [$cand['json'], $cand['context']];

            $decoded = $this->tryDecode($jsonStr);
            if ($decoded === null || ! is_array($decoded)) {
                continue;
            }
            $action = $decoded['action'] ?? null;
            if (! is_string($action) || $action === '') {
                continue;
            }

            if ($pending === null && in_array($action, self::PENDING_ACTIONS, true)) {
                $pending = $decoded;
                $cleaned = $this->stripOnce($cleaned, $cand['full']);
                continue;
            }

            if (in_array($action, self::AUTO_ACTIONS, true) && $context !== 'inline') {
                $auto[] = $decoded;
                $cleaned = $this->stripOnce($cleaned, $cand['full']);
            }
        }

        return [
            'pending' => $pending,
            'auto' => $auto,
            'cleaned_response' => trim($cleaned),
        ];
    }

    /**
     * Enumerate JSON-object candidates with enough context to decide if they
     * were bare / fenced / isolated or embedded inline inside prose.
     *
     * Returns an array of ['json' => string, 'full' => string, 'context' => 'fenced'|'isolated'|'inline'].
     *
     * @return array<int, array{json: string, full: string, context: string}>
     */
    private function jsonCandidates(string $text): array
    {
        $out = [];

        // Fenced blocks: ```json { ... } ```  OR  ``` { ... } ```
        if (preg_match_all('/```(?:json)?\s*(\{.+?\})\s*```/s', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $out[] = ['json' => $m[1], 'full' => $m[0], 'context' => 'fenced'];
            }
        }

        // Bare / isolated JSON objects. We look for an object on its own line
        // (possibly multi-line, balanced) — i.e. preceded by a line break or
        // start-of-string and followed by end-of-line or end-of-string after
        // whitespace. Anything else is treated as inline.
        $len = strlen($text);
        $i = 0;
        while ($i < $len) {
            $open = strpos($text, '{', $i);
            if ($open === false) {
                break;
            }
            $close = $this->matchBrace($text, $open);
            if ($close === false) {
                break;
            }

            $json = substr($text, $open, $close - $open + 1);

            // Skip ones we already captured in a fence.
            $alreadyFenced = false;
            foreach ($out as $existing) {
                if ($existing['context'] === 'fenced' && $existing['json'] === $json) {
                    $alreadyFenced = true;
                    break;
                }
            }
            if ($alreadyFenced) {
                $i = $close + 1;
                continue;
            }

            // Context: is this the entire content of its paragraph?
            $before = substr($text, 0, $open);
            $after = substr($text, $close + 1);
            $leftIsolated = ($before === '') || preg_match('/(^|\n)\s*$/', $before) === 1;
            $rightIsolated = ($after === '') || preg_match('/^\s*($|\n)/', $after) === 1;
            $context = ($leftIsolated && $rightIsolated) ? 'isolated' : 'inline';

            $out[] = ['json' => $json, 'full' => $json, 'context' => $context];
            $i = $close + 1;
        }

        return $out;
    }

    /**
     * Return the position of the matching `}` for the `{` at $open, respecting
     * quoted strings and escape sequences. False if no balanced match exists
     * within $text.
     */
    private function matchBrace(string $text, int $open): int|false
    {
        $depth = 0;
        $inString = false;
        $escape = false;
        $len = strlen($text);

        for ($i = $open; $i < $len; $i++) {
            $ch = $text[$i];

            if ($escape) {
                $escape = false;
                continue;
            }
            if ($ch === '\\' && $inString) {
                $escape = true;
                continue;
            }
            if ($ch === '"') {
                $inString = ! $inString;
                continue;
            }
            if ($inString) {
                continue;
            }
            if ($ch === '{') {
                $depth++;
            } elseif ($ch === '}') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return false;
    }

    /**
     * Strip the first occurrence of $needle from $haystack, treating $needle
     * as a literal string (no regex). Returns the original string if $needle
     * is empty or not found.
     */
    private function stripOnce(string $haystack, string $needle): string
    {
        if ($needle === '') {
            return $haystack;
        }
        $pos = strpos($haystack, $needle);
        if ($pos === false) {
            return $haystack;
        }

        return substr($haystack, 0, $pos) . substr($haystack, $pos + strlen($needle));
    }

    /** @return array<string,mixed>|null */
    private function tryDecode(string $json): ?array
    {
        try {
            $decoded = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /** @param array<string,mixed> $decoded */
    private function isPending(array $decoded): bool
    {
        $action = $decoded['action'] ?? null;

        return is_string($action) && in_array($action, self::PENDING_ACTIONS, true);
    }

    /** @param array<string,mixed> $decoded */
    private function isAuto(array $decoded): bool
    {
        $action = $decoded['action'] ?? null;

        return is_string($action) && in_array($action, self::AUTO_ACTIONS, true);
    }
}
