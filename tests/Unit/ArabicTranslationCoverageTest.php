<?php

declare(strict_types=1);

// The Arabic UI silently shows English for any __('…') key missing from
// lang/ar.json. These screens are fully translated — keep them that way:
// when you add or change a UI string here, add its Arabic entry too. Add a
// screen to this list once it is fully translated.

dataset('translated_screens', [
    'resources/views/livewire/ai-chat.blade.php',
    'resources/views/livewire/inbox/index.blade.php',
    'resources/views/components/inbox/*.blade.php',
    'resources/views/livewire/settings/ai-config.blade.php',
    'resources/views/livewire/super-admin/customers.blade.php',
    'resources/views/livewire/super-admin/subscriptions.blade.php',
    'app/Livewire/AiChat.php',
    'app/Livewire/Settings/AiConfig.php',
    'app/Livewire/SuperAdmin/Customers.php',
    'app/Livewire/SuperAdmin/Subscriptions.php',
]);

test('every UI string on the screen has an Arabic translation', function (string $pattern) {
    $ar = json_decode(file_get_contents(dirname(__DIR__, 2).'/lang/ar.json'), true, 512, JSON_THROW_ON_ERROR);
    $regex = <<<'RE'
    /__\(\s*(?:'((?:\\.|[^'\\])*)'|"((?:\\.|[^"\\])*)")/
    RE;

    $missing = [];
    foreach (glob(dirname(__DIR__, 2).'/'.$pattern) as $file) {
        preg_match_all($regex, file_get_contents($file), $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $key = isset($m[2]) ? str_replace('\\"', '"', $m[2]) : str_replace(["\\'", '\\\\'], ["'", '\\'], $m[1]);
            if (! array_key_exists($key, $ar)) {
                $missing[] = basename($file).': '.$key;
            }
        }
    }

    expect($missing)->toBe([]);
})->with('translated_screens');
