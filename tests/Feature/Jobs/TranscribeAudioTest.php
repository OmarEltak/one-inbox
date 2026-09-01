<?php

declare(strict_types=1);

use App\Jobs\SendAiResponse;
use App\Jobs\TranscribeAudio;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\MediaAsset;
use App\Models\Message;
use App\Services\Ai\TranscriptionRouter;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    Bus::fake([SendAiResponse::class]);

    [$this->user, $this->team] = makeUserWithTeam();
    $this->team->update(['ai_enabled' => true]);
    $this->page = makeEmailPage($this->team);

    $this->contact = Contact::create([
        'team_id'          => $this->team->id,
        'platform'         => 'email',
        'platform_user_id' => 'c@example.com',
        'name'             => 'C',
    ]);

    $this->conversation = Conversation::create([
        'team_id'                  => $this->team->id,
        'page_id'                  => $this->page->id,
        'contact_id'               => $this->contact->id,
        'platform'                 => 'email',
        'platform_conversation_id' => 'thread-'.uniqid(),
        'status'                   => 'open',
        'sales_stage'              => Conversation::STAGE_ACTIVE,
        'ai_paused'                => false,
    ]);

    $this->asset = MediaAsset::factory()->for($this->team)->create(['kind' => 'audio']);

    $this->message = Message::create([
        'conversation_id' => $this->conversation->id,
        'direction'       => 'inbound',
        'sender_type'     => 'contact',
        'content_type'    => 'audio',
        'content'         => '[voice note]',
        'media_asset_id'  => $this->asset->id,
    ]);
});

it('updates message.content with transcription and dispatches SendAiResponse', function () {
    $router = Mockery::mock(TranscriptionRouter::class);
    $router->expects('transcribe')->andReturn('This is what the customer said.');
    app()->instance(TranscriptionRouter::class, $router);

    (new TranscribeAudio($this->message->id))->handle($router);

    $this->message->refresh();
    expect($this->message->content)->toBe('This is what the customer said.');
    Bus::assertDispatched(SendAiResponse::class);
});

it('marks message unavailable when all drivers fail and does NOT dispatch AI', function () {
    $router = Mockery::mock(TranscriptionRouter::class);
    $router->expects('transcribe')->andReturn(null);
    app()->instance(TranscriptionRouter::class, $router);

    (new TranscribeAudio($this->message->id))->handle($router);

    $this->message->refresh();
    expect($this->message->content)->toBe('[voice note — transcription unavailable]');
    Bus::assertNotDispatched(SendAiResponse::class);
});

it('releases the job back to the queue when team is at the 5-inflight limit', function () {
    Cache::put("transcribe:inflight:{$this->team->id}", 5, 300);

    $router = Mockery::mock(TranscriptionRouter::class);
    $router->shouldNotReceive('transcribe');
    app()->instance(TranscriptionRouter::class, $router);

    $job = Mockery::mock(TranscribeAudio::class.'[release]', [$this->message->id])->makePartial();
    $job->shouldReceive('release')->once()->with(3);
    $job->handle($router);
});
