<?php

declare(strict_types=1);

use App\Contracts\AiProviderInterface;
use App\Livewire\AiChat;
use App\Livewire\Settings\AiConfig as AiConfigComponent;
use App\Models\AiCommand;
use App\Models\AiConfig;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Livewire\Livewire;

beforeEach(function () {
    [$this->user, $this->team] = makeUserWithTeam();
    $this->actingAs($this->user);

    foreach (['Mishkah University' => 'عايز اعرف مواعيد الكورس', 'Brandk' => 'عندكم مقاس XL؟'] as $pageName => $said) {
        $page = makeEmailPage($this->team, strtolower(str_replace(' ', '', $pageName)).'@example.com');
        $page->update(['platform' => 'facebook', 'name' => $pageName]);
        $contact = Contact::create(['team_id' => $this->team->id, 'platform' => 'facebook', 'platform_user_id' => $pageName, 'name' => "Fan of {$pageName}"]);
        $conv = Conversation::create([
            'team_id' => $this->team->id, 'page_id' => $page->id, 'contact_id' => $contact->id, 'platform' => 'facebook',
            'platform_conversation_id' => $pageName, 'status' => 'open', 'last_message_at' => now(),
        ]);
        Message::create(['conversation_id' => $conv->id, 'direction' => 'inbound', 'sender_type' => 'contact', 'content_type' => 'text', 'content' => $said]);
    }
});

function askAndCapture(string $question, string $reply = 'ok'): array
{
    $captured = [];
    test()->mock(AiProviderInterface::class)->shouldReceive('chatWithAdmin')->once()
        ->withArgs(function ($msg, $teamId, $ctx, $history) use (&$captured) {
            $captured = ['context' => $ctx, 'history' => $history];

            return true;
        })->andReturn($reply);

    $component = Livewire::test(AiChat::class)->set('message', $question)->call('sendMessage');

    return [$captured, $component];
}

test('a named page gets its own chats in the context', function () {
    [$captured] = askAndCapture('What are last 30 customers for mishkah want');

    expect($captured['context'])->toContain('CUSTOMER CONVERSATIONS ON PAGE "Mishkah University"')
        ->toContain('عايز اعرف مواعيد الكورس')
        ->not->toContain('عندكم مقاس XL؟');
});

test('a one-letter typo still finds the page ("brandak" → Brandk)', function () {
    [$captured] = askAndCapture('What are last 30 customers for brandak want');

    expect($captured['context'])->toContain('ON PAGE "Brandk"')->toContain('عندكم مقاس XL؟');
});

test('only the last 12 turns are sent, each clipped', function () {
    foreach (range(1, 30) as $i) {
        AiCommand::create(['team_id' => $this->team->id, 'user_id' => $this->user->id, 'command' => "q{$i}", 'response' => str_repeat('long ', 1000), 'status' => 'completed']);
    }

    [$captured] = askAndCapture('hi');

    expect(count($captured['history']))->toBeLessThanOrEqual(12)
        ->and($captured['history'][0]['role'])->toBe('user')
        ->and(end($captured['history'])['content'])->toBe('hi')
        ->and(collect($captured['history'])->max(fn ($m) => mb_strlen($m['content'])))->toBeLessThanOrEqual(2003);
});

test('internal IDs are scrubbed from reply prose, customer quotes kept', function () {
    [, $component] = askAndCapture('send promo', 'Sending to **Brandk (Facebook, ID: 11)** for Wagdy (contact ID: 5). Customer said: order ID: 5531.');

    $messages = $component->get('messages');
    expect(end($messages)['content'])
        ->toBe('Sending to **Brandk (Facebook)** for Wagdy. Customer said: order ID: 5531.');
});

test('capture fields work without the removed Label input', function () {
    expect(AiConfig::captureFieldLabel(['key' => 'preferred_slot', 'label' => '']))->toBe('Preferred Slot')
        ->and(AiConfig::captureFieldLabel(['key' => 'العنوان']))->toBe('العنوان')
        ->and(AiConfig::captureFieldLabel(['key' => 'email', 'label' => 'Email address']))->toBe('Email address');

    Livewire::test(AiConfigComponent::class)
        ->set('required_capture_fields', [['key' => 'email', 'label' => 'Email address', 'type' => 'email']])
        ->set('required_capture_fields.0.key', 'phone')
        ->assertSet('required_capture_fields.0.label', '');
});
