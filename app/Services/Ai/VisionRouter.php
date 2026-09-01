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
