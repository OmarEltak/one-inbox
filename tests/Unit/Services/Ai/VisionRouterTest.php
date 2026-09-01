<?php

declare(strict_types=1);

use App\Services\Ai\VisionRouter;

uses(Tests\TestCase::class);

it('iterates the chain and returns the first vision-capable model', function () {
    config(['services.nararouter.fallback_models' => 'mistral-large,agnes-2.5-flash,agnes-2.0-flash']);

    $router = new VisionRouter();
    expect($router->firstVisionCapableModel())->toBe('agnes-2.5-flash');
});

it('returns null when no vision-capable model exists in the chain', function () {
    config(['services.nararouter.fallback_models' => 'mistral-large,deepseek-v4-flash']);

    $router = new VisionRouter();
    expect($router->firstVisionCapableModel())->toBeNull();
});

it('produces the multipart payload for vision calls', function () {
    $router = new VisionRouter();
    $payload = $router->buildPayload(
        model: 'agnes-2.5-flash',
        imageUrl: 'https://example/img.jpg',
        prompt: 'Describe',
    );

    expect($payload['model'])->toBe('agnes-2.5-flash')
        ->and($payload['messages'][0]['content'][0]['type'])->toBe('text')
        ->and($payload['messages'][0]['content'][1]['type'])->toBe('image_url')
        ->and($payload['messages'][0]['content'][1]['image_url']['url'])->toBe('https://example/img.jpg');
});
