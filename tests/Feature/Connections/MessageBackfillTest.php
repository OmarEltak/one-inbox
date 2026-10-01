<?php

use App\Jobs\BackfillPageMessages;
use App\Jobs\SendAiResponse;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Platforms\FacebookPlatform;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    [, $this->team] = makeUserWithTeam();
    $this->page = makeEmailPage($this->team);
    $this->page->update(['platform' => 'facebook', 'platform_page_id' => 'PAGE1', 'name' => 'Mishkah']);
    $this->makeConvs = function (int $n) {
        foreach (range(1, $n) as $i) {
            $c = Contact::create(['team_id' => $this->team->id, 'name' => "Student $i"]);
            Conversation::create(['team_id' => $this->team->id, 'page_id' => $this->page->id, 'contact_id' => $c->id,
                'platform' => 'facebook', 'platform_conversation_id' => "U$i", 'status' => 'open', 'last_message_at' => now()->subDays($i)]);
        }
    };
});

test('imports each chat history with real send dates and never triggers an AI reply', function () {
    Bus::fake([SendAiResponse::class]);
    ($this->makeConvs)(2);
    $reply = fn (string $u) => Http::response(['data' => [['id' => "t_$u", 'messages' => ['data' => [
        ['id' => "m-$u-2", 'message' => 'تمام', 'from' => ['id' => 'PAGE1'], 'created_time' => '2026-09-01T11:00:00+0000'],
        ['id' => "m-$u-1", 'message' => 'عايز اسعار الدبلومة', 'from' => ['id' => $u], 'created_time' => '2026-09-01T10:00:00+0000'],
    ]]]]]);
    Http::fake(['*' => Http::sequence()->pushResponse($reply('U1'))->pushResponse($reply('U2'))]);

    (new BackfillPageMessages($this->page->id))->handle(app(FacebookPlatform::class));

    $inbound = Message::where('platform_message_id', 'm-U1-1')->first();
    expect(Message::count())->toBe(4)
        ->and($inbound->direction)->toBe('inbound')
        ->and($inbound->created_at->toDateTimeString())->toBe('2026-09-01 10:00:00')
        ->and(Conversation::all()->every(fn ($c) => data_get($c->metadata, 'messages_fetched')))->toBeTrue();
    Bus::assertNotDispatched(SendAiResponse::class);
});

test('stops after three refusals from Meta instead of hammering every chat', function () {
    ($this->makeConvs)(5);
    Http::fake(['*' => Http::response(['error' => ['code' => 190]], 400)]);

    (new BackfillPageMessages($this->page->id))->handle(app(FacebookPlatform::class));

    Http::assertSentCount(3);
});
