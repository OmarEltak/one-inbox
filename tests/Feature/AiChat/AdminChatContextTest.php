<?php

declare(strict_types=1);

use App\Contracts\AiProviderInterface;
use App\Jobs\SendPlatformMessage;
use App\Livewire\AiChat;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Ai\AdminChatContext;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;

// Pins the /ai-chat quality fixes: the assistant sees chat content, resolves
// contacts by name (never asks the operator for an ID), a typed "send"
// confirms the pending action, and replies render as Markdown.

beforeEach(function () {
    Bus::fake([SendPlatformMessage::class]);
    [$this->user, $this->team] = makeUserWithTeam();
    $this->actingAs($this->user);

    $this->page = makeEmailPage($this->team);
    $this->page->update(['platform' => 'whatsapp', 'name' => 'Main WA']);

    $this->wagdy = Contact::create([
        'team_id' => $this->team->id, 'platform' => 'whatsapp',
        'platform_user_id' => '201026361218', 'name' => 'Wagdy🍫',
    ]);
    $conv = Conversation::create([
        'team_id' => $this->team->id, 'page_id' => $this->page->id, 'contact_id' => $this->wagdy->id,
        'platform' => 'whatsapp', 'platform_conversation_id' => '201026361218', 'status' => 'open',
        'last_message_at' => now(),
    ]);
    foreach ([['inbound', 'contact', 'عايز اعرف سعر الكورس كام؟'], ['inbound', 'contact', '[voice note]'], ['outbound', 'ai', 'السعر 1500 جنيه']] as [$dir, $sender, $text]) {
        Message::create(['conversation_id' => $conv->id, 'direction' => $dir, 'sender_type' => $sender, 'content_type' => 'text', 'content' => $text]);
    }

    Contact::create(['team_id' => $this->team->id, 'platform' => 'whatsapp', 'platform_user_id' => '2', 'name' => 'Test Shop']);
});

test('a contact named in an earlier turn resolves with ID, language and transcript', function () {
    $block = app(AdminChatContext::class)->mentionedContacts($this->team->id, 'send him a marketing message', 'can u see wagdy chat');

    expect($block)->toContain("Wagdy🍫 | contact ID:{$this->wagdy->id}")
        ->toContain('writes in Arabic')
        ->toContain('Customer: عايز اعرف سعر الكورس كام؟')
        ->toContain('Customer: (sent a voice note)')
        ->not->toContain('Test Shop');
});

test('the customer digest quotes real customer messages, not placeholders', function () {
    $digest = app(AdminChatContext::class)->customerDigest($this->team->id);

    expect($digest)->toContain('Wagdy🍫 (contact ID:')
        ->toContain('عايز اعرف سعر الكورس كام؟')
        ->not->toContain('[voice note]')
        ->not->toContain('السعر 1500 جنيه'); // outbound is not customer voice
});

test('an action-only reply shows a prompt instead of an empty bubble, and typing "ابعت" confirms it', function () {
    $this->mock(AiProviderInterface::class)->shouldReceive('chatWithAdmin')->once()
        ->andReturn("```pending_action\n{\"action\": \"send_message\", \"contact_id\": {$this->wagdy->id}, \"message\": \"أهلاً يا وجدي\"}\n```");

    Livewire::test(AiChat::class)
        ->set('message', 'respond to wagdy')
        ->call('sendMessage')
        ->assertSee('Ready — review the action below and confirm.')
        ->assertSet('pendingAction.contact_id', $this->wagdy->id)
        ->set('message', 'ابعت')
        ->call('sendMessage')
        ->assertSet('pendingAction', null);

    Bus::assertDispatchedTimes(SendPlatformMessage::class, 1);
});

test('assistant replies render Markdown with raw HTML stripped', function () {
    $this->mock(AiProviderInterface::class)->shouldReceive('chatWithAdmin')->once()
        ->andReturn("**3 of 5** conversations ask about price <script>alert(1)</script>\n\n- one\n- two");

    Livewire::test(AiChat::class)
        ->set('message', 'what do customers want')
        ->call('sendMessage')
        ->assertSeeHtml('<strong>3 of 5</strong>')
        ->assertSeeHtml('<li>one</li>')
        ->assertDontSeeHtml('<script>alert(1)</script>');
});
