<?php

use App\Services\Ai\MediaPlaceholders;

// Pins the "[image]\n\n[voice note]" customer-facing reply bug: placeholder
// tokens must never survive into a sent AI reply, and the history must
// narrate media instead of feeding the model a token it can parrot.

test('a reply made only of media placeholders strips to empty', function () {
    expect(MediaPlaceholders::strip("[image]\n\n[voice note]"))->toBe('')
        ->and(MediaPlaceholders::strip('[Image] [Audio/Voice message]'))->toBe('')
        ->and(MediaPlaceholders::strip('[media: you sent the customer a photo]'))->toBe('');
});

test('real text survives, placeholder tokens inside it are removed', function () {
    expect(MediaPlaceholders::strip("أهلاً بيك! [image]\nالسعر 200 جنيه"))->toBe("أهلاً بيك! \nالسعر 200 جنيه")
        ->and(MediaPlaceholders::strip('Price is [10% off] today'))->toBe('Price is [10% off] today');
});

test('placeholders are detected and narrated by direction', function () {
    expect(MediaPlaceholders::isPlaceholder('[voice note]'))->toBeTrue()
        ->and(MediaPlaceholders::isPlaceholder(null))->toBeTrue()
        ->and(MediaPlaceholders::isPlaceholder('invoice.pdf'))->toBeFalse()
        ->and(MediaPlaceholders::narrate('audio', '[voice note]', true))->toContain('customer sent a voice note')
        ->and(MediaPlaceholders::narrate('text', '[image]', false))->toBe('[media: you sent the customer a photo]');
});
