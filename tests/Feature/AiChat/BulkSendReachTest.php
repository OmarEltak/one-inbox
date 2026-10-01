<?php

declare(strict_types=1);

use App\Jobs\SendPlatformMessage;
use App\Livewire\AiChat;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Bus;

// Pins the "AI said sent to 98, Messenger delivered 2" fix: on Messenger /
// Instagram, only contacts whose last inbound is within 24h are counted or
// sent to — in the confirmation summary, the executor, and the per-page
// audience line the AI reads before proposing a broadcast.

beforeEach(function () {
    Bus::fake([SendPlatformMessage::class]);
    [$this->user, $this->team] = makeUserWithTeam();
    $this->actingAs($this->user);

    $this->page = makeEmailPage($this->team);
    $this->page->update(['platform' => 'facebook', 'name' => 'Brandk']);

    $makeConv = function (string $name, ?\Carbon\CarbonInterface $lastInboundAt) {
        $contact = Contact::create([
            'team_id' => $this->team->id, 'platform' => 'facebook',
            'platform_user_id' => 'psid-'.$name, 'name' => $name,
        ]);
        $conv = Conversation::create([
            'team_id' => $this->team->id, 'page_id' => $this->page->id, 'contact_id' => $contact->id,
            'platform' => 'facebook', 'platform_conversation_id' => 't-'.$name, 'status' => 'open',
            'last_message_at' => $lastInboundAt ?? now()->subDays(5),
        ]);
        if ($lastInboundAt) {
            $m = Message::create([
                'conversation_id' => $conv->id, 'direction' => 'inbound', 'sender_type' => 'contact',
                'content_type' => 'text', 'content' => 'hi', 'platform_sent_at' => $lastInboundAt,
            ]);
            $m->forceFill(['created_at' => $lastInboundAt])->save();
        }
    };

    $makeConv('fresh', now()->subHours(2));
    $makeConv('stale', now()->subDays(3));
    $makeConv('silent', null);

    $this->chat = new AiChat;
    $this->call = fn (string $method, ...$args) => (fn () => $this->{$method}(...$args))->call($this->chat);
});

test('only contacts inside the Meta 24h window are bulk targets', function () {
    $targets = ($this->call)('resolveBulkTargets', ['page_id' => $this->page->id, 'message' => 'x'], $this->team->id);

    expect($targets['eligible'])->toHaveCount(1)
        ->and($targets['eligible']->first()->contact->name)->toBe('fresh')
        ->and($targets['stale'])->toBe(2);
});

test('confirmation summary quotes the reachable count and the skipped count', function () {
    $summary = ($this->call)('describeBulkMessage', ['page_id' => $this->page->id, 'message' => 'Sale'], $this->team->id);

    expect($summary)->toContain('to 1 contacts')->toContain('2 more on Messenger/Instagram will NOT receive it');
});

test('executor sends only to reachable contacts and reports the skip', function () {
    $result = ($this->call)('actionSendBulkMessage', ['page_id' => $this->page->id, 'message' => 'Sale'], $this->team->id);

    Bus::assertDispatchedTimes(SendPlatformMessage::class, 1);
    expect($result)->toContain('Queued message to 1 contacts')->toContain('Skipped 2');
});

test('executor sends nothing and says why when nobody is reachable', function () {
    Message::query()->update(['platform_sent_at' => now()->subDays(2), 'created_at' => now()->subDays(2)]);

    $result = ($this->call)('actionSendBulkMessage', ['page_id' => $this->page->id, 'message' => 'Sale'], $this->team->id);

    Bus::assertNotDispatched(SendPlatformMessage::class);
    expect($result)->toStartWith('Nothing sent: all 3 matching contacts');
});

test('AI context spells out per-page reach for Messenger pages', function () {
    $context = ($this->call)('buildAnalyticsContext', $this->team->id);

    expect($context)->toContain("Brandk | facebook | 3 contacts, only 1 reachable now")
        ->toContain('2 outside Meta\'s 24h window');
});
