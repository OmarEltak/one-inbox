<?php

declare(strict_types=1);

use App\Contracts\AiProviderInterface;
use App\Livewire\AiChat;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

// Pins Phase C of the AI credit economy spec (§6 "Retry + idempotency"):
// double-clicks and page re-dispatches must not re-charge or re-call
// NaraRouter within the dedup window, and transient failures retry up to
// 3× before the exception escapes to the caller's catch block.

beforeEach(function () {
    [$this->user, $this->team] = makeUserWithTeam();
    $this->actingAs($this->user);
    Cache::flush();
});

test('same (team, user, message) within 60s returns cached response and only calls NaraRouter once', function () {
    $this->mock(AiProviderInterface::class)
        ->shouldReceive('chatWithAdmin')->once()->andReturn('cached answer');

    Livewire::test(AiChat::class)
        ->set('message', 'how many conversations do we have')
        ->call('sendMessage')
        ->assertSee('cached answer');

    // Same operator, same team, same message → cache hit, no second provider call.
    Livewire::test(AiChat::class)
        ->set('message', 'how many conversations do we have')
        ->call('sendMessage')
        ->assertSee('cached answer');
});

test('two throws then success returns the final answer and makes 3 attempts', function () {
    $calls = 0;
    $this->mock(AiProviderInterface::class)
        ->shouldReceive('chatWithAdmin')
        ->times(3)
        ->andReturnUsing(function () use (&$calls) {
            $calls++;
            if ($calls < 3) {
                throw new \RuntimeException('nararouter 503');
            }

            return 'eventually worked';
        });

    Livewire::test(AiChat::class)
        ->set('message', 'first attempt will fail')
        ->call('sendMessage')
        ->assertSee('eventually worked');

    expect($calls)->toBe(3);
});

test('three throws → exception propagates and the Livewire catch surfaces the user-friendly fallback', function () {
    $this->mock(AiProviderInterface::class)
        ->shouldReceive('chatWithAdmin')
        ->times(3)
        ->andThrow(new \RuntimeException('nararouter down'));

    Livewire::test(AiChat::class)
        ->set('message', 'everything is broken')
        ->call('sendMessage')
        ->assertSee('Sorry, I encountered an error processing your request. Please try again.');
});
