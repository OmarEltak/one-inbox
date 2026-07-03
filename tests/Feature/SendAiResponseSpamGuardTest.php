<?php

declare(strict_types=1);

use App\Contracts\AiProviderInterface;
use App\Jobs\SendAiResponse;
use App\Models\AiConfig;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;

// Pins ARCHITECTURE §9 "Reactivation loop" invariant: once a human operator
// has reactivated a spam-flagged conversation (metadata.reactivated_at set),
// SendAiResponse must NOT auto-re-flag it even if the AI still emits
// [SPAM_DETECTED]. Otherwise the AI's classification overrides the human's
// judgment on every subsequent inbound message.

beforeEach(function () {
    [$this->user, $this->team] = makeUserWithTeam();

    // canDispatchAi() requires ai_enabled=true and plan credits available.
    // The free plan default is fine for credits (used=0), but ai_enabled is
    // false-by-default per the Team model.
    $this->team->update(['ai_enabled' => true]);

    $this->page = makeEmailPage($this->team);

    AiConfig::create([
        'team_id'    => $this->team->id,
        'page_id'    => $this->page->id,
        'is_active'  => true,
        'is_24_7'    => true,
        'model'      => 'auto',
        'tone'       => 'friendly',
        'goal'       => 'sales',
        'company_info' => 'test',
    ]);

    $this->contact = Contact::create([
        'team_id'          => $this->team->id,
        'platform'         => 'email',
        'platform_user_id' => 'contact@example.com',
        'name'             => 'Test Contact',
    ]);
});

function makeConvWithInbound(array $convOverrides = []): array
{
    $conv = Conversation::create(array_merge([
        'team_id'     => test()->team->id,
        'page_id'     => test()->page->id,
        'contact_id'  => test()->contact->id,
        'platform'   => 'email',
        'platform_conversation_id' => 'thread-'.uniqid(),
        'status'     => 'open',
        'sales_stage' => Conversation::STAGE_ACTIVE,
        'ai_paused'  => false,
    ], $convOverrides));

    $trigger = Message::create([
        'conversation_id' => $conv->id,
        'direction'       => 'inbound',
        'sender_type'     => 'contact',
        'content_type'    => 'text',
        'content'         => 'hi',
    ]);

    return [$conv, $trigger];
}

test('without reactivation: [SPAM_DETECTED] auto-flags conversation as spam', function () {
    [$conv, $trigger] = makeConvWithInbound();

    // Fake provider that always emits the spam marker.
    $this->app->bind(AiProviderInterface::class, fn () => new class implements AiProviderInterface {
        public function generateResponse($conversation, $incomingMessage, $config): string { return '[SPAM_DETECTED]'; }
        public function scoreMessage($message, $contact): array { return []; }
        public function analyzeConversation($conversation): array { return []; }
        public function generateText(string $s, string $u): string { return ''; }
        public function processCommand(string $c, int $t): array { return ['response' => '', 'action' => null]; }
    });

    (new SendAiResponse($conv->id, $trigger->id))->handle(app(AiProviderInterface::class));

    $conv->refresh();
    expect($conv->sales_stage)->toBe(Conversation::STAGE_SPAM);
    expect($conv->ai_paused)->toBeTrue();
    expect($conv->metadata['marked_spam_by'] ?? null)->toBe('ai_auto');
});

test('after human reactivation: [SPAM_DETECTED] is suppressed, conversation stays active', function () {
    [$conv, $trigger] = makeConvWithInbound([
        'metadata' => [
            'reactivated_by' => 1,
            'reactivated_at' => now()->subMinutes(5)->toIso8601String(),
        ],
    ]);

    $this->app->bind(AiProviderInterface::class, fn () => new class implements AiProviderInterface {
        public function generateResponse($conversation, $incomingMessage, $config): string { return '[SPAM_DETECTED]'; }
        public function scoreMessage($message, $contact): array { return []; }
        public function analyzeConversation($conversation): array { return []; }
        public function generateText(string $s, string $u): string { return ''; }
        public function processCommand(string $c, int $t): array { return ['response' => '', 'action' => null]; }
    });

    (new SendAiResponse($conv->id, $trigger->id))->handle(app(AiProviderInterface::class));

    $conv->refresh();
    // Must NOT be re-flagged.
    expect($conv->sales_stage)->toBe(Conversation::STAGE_ACTIVE);
    expect($conv->ai_paused)->toBeFalse();
    expect($conv->metadata['marked_spam_by'] ?? null)->toBeNull();

    // The marker must NOT have been sent to the customer either.
    expect(Message::where('conversation_id', $conv->id)->where('direction', 'outbound')->count())->toBe(0);
});

test('after human reactivation: a real (non-spam) AI reply still sends', function () {
    [$conv, $trigger] = makeConvWithInbound([
        'metadata' => [
            'reactivated_by' => 1,
            'reactivated_at' => now()->subMinutes(5)->toIso8601String(),
        ],
    ]);

    $this->app->bind(AiProviderInterface::class, fn () => new class implements AiProviderInterface {
        public function generateResponse($conversation, $incomingMessage, $config): string { return 'Hello, how can I help?'; }
        public function scoreMessage($message, $contact): array { return []; }
        public function analyzeConversation($conversation): array { return []; }
        public function generateText(string $s, string $u): string { return ''; }
        public function processCommand(string $c, int $t): array { return ['response' => '', 'action' => null]; }
    });

    (new SendAiResponse($conv->id, $trigger->id))->handle(app(AiProviderInterface::class));

    // Outbound AI message should exist.
    $outbound = Message::where('conversation_id', $conv->id)->where('direction', 'outbound')->first();
    expect($outbound)->not->toBeNull();
    expect($outbound->content)->toBe('Hello, how can I help?');
});
