<?php

declare(strict_types=1);

use App\Contracts\AiProviderInterface;
use App\Jobs\SendAiResponse;
use App\Models\AiConfig;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

// Pins ARCHITECTURE §9 "Reactivation loop" invariant for the AUTO-COMPLETE
// side (the spam side is already pinned by SendAiResponseSpamGuardTest):
// once a human operator has manually transitioned a completed conversation
// back to ACTIVE (metadata.reactivated_at set), SendAiResponse must NOT
// re-fire Conversation::complete() even if CaptureExtractor still sees all
// required_capture_fields populated. Otherwise the human's decision is
// undone within seconds of their next inbound message.
//
// Driver: 2026-11-09 Omar reported marking a contact "not completed" twice
// and watching the AI re-mark it immediately. Root cause was the symmetric
// gap in the complete() path that §9 already fixed for the spam() path.

beforeEach(function () {
    [$this->user, $this->team] = makeUserWithTeam();
    $this->team->update(['ai_enabled' => true]);
    $this->page = makeEmailPage($this->team);

    // Config with a single required capture field — any value in captured_data
    // will mark isComplete() true (the field is pre-filled on the Conversation).
    AiConfig::create([
        'team_id'   => $this->team->id,
        'page_id'   => $this->page->id,
        'is_active' => true,
        'is_24_7'   => true,
        'model'     => 'auto',
        'tone'      => 'friendly',
        'goal'      => 'sales',
        'company_info' => 'test',
        'sales_goal_preset'       => AiConfig::GOAL_CAPTURE_DATA,
        'required_capture_fields' => [['key' => 'email', 'label' => 'Email', 'type' => 'email']],
    ]);

    $this->contact = Contact::create([
        'team_id'          => $this->team->id,
        'platform'         => 'email',
        'platform_user_id' => 'contact@example.com',
        'name'             => 'Test Contact',
    ]);
});

function makeConvWithPrefilledCapture(array $convOverrides = []): array
{
    $conv = Conversation::create(array_merge([
        'team_id'                  => test()->team->id,
        'page_id'                  => test()->page->id,
        'contact_id'               => test()->contact->id,
        'platform'                 => 'email',
        'platform_conversation_id' => 'thread-'.uniqid(),
        'status'                   => 'open',
        'sales_stage'              => Conversation::STAGE_ACTIVE,
        'ai_paused'                => false,
        // Pre-fill the required capture field so CaptureExtractor::isComplete()
        // returns true WITHOUT needing the extractor to do any real work.
        'captured_data'            => ['email' => 'already@captured.com'],
    ], $convOverrides));

    $trigger = Message::create([
        'conversation_id' => $conv->id,
        'direction'       => 'inbound',
        'sender_type'     => 'contact',
        'content_type'    => 'text',
        'content'         => 'any follow-up message',
    ]);

    return [$conv, $trigger];
}

test('without reactivation: all-required-fields-captured auto-completes conversation', function () {
    [$conv, $trigger] = makeConvWithPrefilledCapture();

    // Stub provider so generateText (for freeform extraction, if it ran) and
    // generateResponse return empty — we only care about the auto-complete gate.
    $this->app->bind(AiProviderInterface::class, fn () => new class implements AiProviderInterface {
        public function generateResponse($c, $m, $cfg): string { return 'should-not-send'; }
        public function scoreMessage($m, $c): array { return []; }
        public function analyzeConversation($c): array { return []; }
        public function generateText(string $s, string $u): string { return ''; }
        public function processCommand(string $c, int $t): array { return ['response' => '', 'action' => null]; }
    });

    (new SendAiResponse($conv->id, $trigger->id))->handle(app(AiProviderInterface::class));

    $conv->refresh();
    expect($conv->sales_stage)->toBe(Conversation::STAGE_COMPLETED);
    expect($conv->ai_paused)->toBeTrue();
    expect($conv->metadata['completion_reason'] ?? null)->toBe('all_required_fields_captured');
});

test('after human reactivation: auto-complete is suppressed, conversation stays active', function () {
    [$conv, $trigger] = makeConvWithPrefilledCapture([
        'metadata' => [
            'reactivated_by' => 1,
            'reactivated_at' => now()->subMinutes(5)->toIso8601String(),
        ],
    ]);

    // Return a real reply so the job does NOT short-circuit before our gate
    // — we need to prove the auto-complete gate is skipped AND the reply
    // continues through to send.
    $this->app->bind(AiProviderInterface::class, fn () => new class implements AiProviderInterface {
        public function generateResponse($c, $m, $cfg): string { return 'Thanks for coming back!'; }
        public function scoreMessage($m, $c): array { return []; }
        public function analyzeConversation($c): array { return []; }
        public function generateText(string $s, string $u): string { return ''; }
        public function processCommand(string $c, int $t): array { return ['response' => '', 'action' => null]; }
    });

    // Spy on logs so we can assert the suppression log line fires — without this,
    // test 2 proves the outcome but NOT that the guard code path was reached
    // (removing the guard entirely while something else kept the conversation
    // active would still pass the sales_stage assertions). Code-review 2026-11-09.
    Log::spy();

    (new SendAiResponse($conv->id, $trigger->id))->handle(app(AiProviderInterface::class));

    $conv->refresh();
    // Must NOT be re-completed.
    expect($conv->sales_stage)->toBe(Conversation::STAGE_ACTIVE);
    expect($conv->ai_paused)->toBeFalse();
    expect($conv->metadata['completion_reason'] ?? null)->toBeNull();

    // The reply must have been generated and stored (reactivation guard only
    // suppresses the auto-complete; it does NOT silence the AI).
    $outbound = Message::where('conversation_id', $conv->id)->where('direction', 'outbound')->first();
    expect($outbound)->not->toBeNull();
    expect($outbound->content)->toBe('Thanks for coming back!');

    // Prove the guard code path ran — not just that the outcome happened to match.
    Log::shouldHaveReceived('info')->withArgs(function ($message, $context = []) use ($conv) {
        return str_contains((string) $message, 'auto-complete')
            && str_contains((string) $message, 'suppressing')
            && str_contains((string) $message, (string) $conv->id);
    });
});

test('reactivation guard does not block a FRESH auto-complete on a never-reactivated conversation', function () {
    // Regression guard: make sure our new early-return only fires when
    // reactivated_at is actually set. A conversation that has OTHER metadata
    // (e.g. captured_data provenance) but NO reactivated_at must still auto-
    // complete as normal.
    [$conv, $trigger] = makeConvWithPrefilledCapture([
        'metadata' => ['source' => 'ad_campaign_abc'],  // no reactivated_at
    ]);

    $this->app->bind(AiProviderInterface::class, fn () => new class implements AiProviderInterface {
        public function generateResponse($c, $m, $cfg): string { return 'should-not-send'; }
        public function scoreMessage($m, $c): array { return []; }
        public function analyzeConversation($c): array { return []; }
        public function generateText(string $s, string $u): string { return ''; }
        public function processCommand(string $c, int $t): array { return ['response' => '', 'action' => null]; }
    });

    (new SendAiResponse($conv->id, $trigger->id))->handle(app(AiProviderInterface::class));

    $conv->refresh();
    expect($conv->sales_stage)->toBe(Conversation::STAGE_COMPLETED);
});
