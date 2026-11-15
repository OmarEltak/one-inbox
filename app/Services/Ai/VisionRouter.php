<?php

declare(strict_types=1);

namespace App\Services\Ai;

class VisionRouter
{
    /**
     * NaraRouter models that accept image_url input. Update when the chain
     * changes — verified against NaraRouter dashboard 2026-09-01.
     * See docs/VPS.md.
     */
    private const VISION_CAPABLE = [
        'agnes-2.5-flash',
        'agnes-2.0-flash',
        'glm-5.3-flash-free',
        'minimax-m3-free',
        'mistral-medium-3-5',
        'stepfun-3.7-flash',
    ];

    public function firstVisionCapableModel(): ?string
    {
        // Prefer the explicit NARAROUTER_VISION_MODELS chain. If the operator
        // configured a vision chain, trust that intent — don't filter against
        // the hardcoded allowlist below (which goes stale every quarter as
        // NaraRouter adds new vision models). This was the Nov 2026 bug: the
        // text chain contained gpt-6.1-sol (vision-capable) but we were
        // looking at fallback_models (text chain) AND filtering through the
        // stale allowlist that didn't include gpt-6.1-sol.
        $visionRaw = (string) config('services.nararouter.vision_models', '');
        $visionChain = array_filter(array_map('trim', explode(',', $visionRaw)));
        if (! empty($visionChain)) {
            return (string) $visionChain[array_key_first($visionChain)];
        }

        // Fallback: scan the text chain for any model we know is vision-capable.
        // Preserves the historical behaviour for deployments that only set
        // NARAROUTER_TEXT_MODELS (no explicit vision chain).
        $chainRaw = (string) config('services.nararouter.fallback_models', '');
        $chain    = array_filter(array_map('trim', explode(',', $chainRaw)));

        foreach ($chain as $model) {
            if (in_array($model, self::VISION_CAPABLE, true)) {
                return $model;
            }
        }

        return null;
    }

    public function buildPayload(string $model, string $imageUrl, string $prompt): array
    {
        return [
            'model'    => $model,
            'messages' => [
                [
                    'role'    => 'user',
                    'content' => [
                        ['type' => 'text',      'text' => $prompt],
                        ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]],
                    ],
                ],
            ],
            'max_tokens' => 400,
        ];
    }
}
