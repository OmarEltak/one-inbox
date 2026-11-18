<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\AiProviderInterface;
use App\Models\AiConfig;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

/**
 * ══ ARCHITECTURE REFERENCE §10 ══
 * READ docs/ARCHITECTURE.md §10 (Captured-Data Extraction) before changing
 * the hybrid regex+LLM strategy. Load-bearing: extraction MUST run BEFORE
 * the reply generation call so the reply prompt can push for what's still
 * missing. Extracting after reply generation makes the AI repeatedly ask
 * for fields the customer already provided in this turn.
 *
 * Extracts captured-data fields from customer messages based on the AI Config's
 * required_capture_fields list. Hybrid strategy:
 *
 *   - Structured/validatable fields (email, phone) → regex-first, free, ~1ms.
 *   - Freeform fields (address, name, custom text)  → LLM extraction pass.
 *
 * Merges into Conversation.captured_data. Reports back whether the full
 * required set is now captured so the caller can complete the conversation.
 */
class CaptureExtractor
{
    public function __construct(private readonly AiProviderInterface $ai)
    {
    }

    /**
     * Run extraction against the latest inbound message. Updates the
     * conversation's captured_data JSON in-place.
     *
     * @return array{captured: array<string, mixed>, complete: bool}
     */
    public function extract(Conversation $conversation, Message $inbound, AiConfig $config): array
    {
        $required = $config->required_capture_fields ?? [];
        $existing = $conversation->captured_data ?? [];

        // Skip if nothing to capture, or if we've already captured everything.
        if (empty($required) || $this->isComplete($required, $existing)) {
            return [
                'captured' => $existing,
                'complete' => $this->isComplete($required, $existing),
            ];
        }

        $content = trim((string) $inbound->content);
        if ($content === '') {
            return ['captured' => $existing, 'complete' => false];
        }

        $captured = $existing;

        foreach ($required as $field) {
            $key = $field['key'] ?? null;
            if (! $key || isset($captured[$key])) {
                continue;
            }

            $value = $this->extractField($field, $content, $conversation);
            if ($value !== null && $value !== '') {
                $captured[$key] = $value;
            }
        }

        if ($captured !== $existing) {
            $conversation->update(['captured_data' => $captured]);
        }

        return [
            'captured' => $captured,
            'complete' => $this->isComplete($required, $captured),
        ];
    }

    /**
     * Are all required fields now captured?
     *
     * @param  array<int, array<string, string>> $required
     * @param  array<string, mixed> $captured
     */
    public function isComplete(array $required, array $captured): bool
    {
        foreach ($required as $field) {
            $key = $field['key'] ?? null;
            if (! $key) {
                continue;
            }
            if (! isset($captured[$key]) || $captured[$key] === '') {
                return false;
            }
        }

        return ! empty($required);
    }

    /**
     * Route a single field to the right extractor based on its declared type.
     */
    private function extractField(array $field, string $content, Conversation $conversation): ?string
    {
        $type = $field['type'] ?? 'text';

        return match ($type) {
            'email' => $this->extractEmail($content),
            'phone' => $this->extractPhone($content),
            default => $this->extractFreeform($field, $content, $conversation),
        };
    }

    private function extractEmail(string $content): ?string
    {
        if (preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $content, $m)) {
            return $m[0];
        }
        return null;
    }

    private function extractPhone(string $content): ?string
    {
        // Tokenise on whitespace/commas/newlines first so "01000386318 01064244810"
        // yields TWO candidates (not one 20-digit monster). Without this the old
        // greedy regex `[0-9\s\-().]{6,20}` happily spanned whitespace between
        // two separate numbers and returned the concatenated garbage —
        // observed on نورهان مجدي's conv ("01000386318010642448" as a single
        // "phone" in the sheet).
        //
        // Returns the FIRST valid phone (7-15 digits after stripping punct).
        // If customer sends two, we honour the first and let the operator sort
        // out which is active during delivery.
        $tokens = preg_split('/[\s,،;\|\/]+/u', $content) ?: [];
        foreach ($tokens as $tok) {
            if (preg_match('/^\+?[0-9][0-9\-().]*[0-9]$/', $tok)) {
                $digits = preg_replace('/[^0-9+]/', '', $tok);
                if ($digits !== null && strlen($digits) >= 7 && strlen($digits) <= 15) {
                    return $digits;
                }
            }
        }
        // Fallback: run the historic greedy regex ONCE, but strip whitespace from
        // the match so even a loose `0100 0386 318` collapses correctly.
        if (preg_match('/\+?[0-9][0-9\s\-().]{6,20}[0-9]/', $content, $m)) {
            $digits = preg_replace('/[^0-9+]/', '', $m[0]);
            // Reject if the result is unreasonably long (two numbers smushed).
            if (strlen($digits) >= 7 && strlen($digits) <= 15) {
                return $digits;
            }
        }
        return null;
    }

    /**
     * LLM-backed extraction for freeform fields (address, name, custom text).
     * Returns null if the model couldn't confidently extract, so we don't
     * pollute captured_data with garbage.
     */
    private function extractFreeform(array $field, string $content, Conversation $conversation): ?string
    {
        $label = \App\Models\AiConfig::captureFieldLabel($field);

        $systemPrompt = "You extract structured data from customer messages. "
            . "Given a single field description and a raw message, respond with ONLY the extracted value — no quotes, no commentary. "
            . "If the message does not contain the field, respond with the single word: NONE";

        $userPrompt = "Field to extract: {$label}\n\nCustomer message:\n{$content}";

        try {
            $reply = trim($this->ai->generateText($systemPrompt, $userPrompt));
        } catch (\Throwable $e) {
            Log::warning('Capture extraction failed', ['error' => $e->getMessage(), 'field' => $label]);
            return null;
        }

        if ($reply === '' || strcasecmp($reply, 'NONE') === 0) {
            return null;
        }

        // Very short freeform values are usually noise (e.g. model returning "OK").
        if (mb_strlen($reply) < 2) {
            return null;
        }

        return $reply;
    }
}
