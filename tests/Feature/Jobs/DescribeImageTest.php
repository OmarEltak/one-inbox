<?php

declare(strict_types=1);

use App\Jobs\DescribeImage;
use App\Jobs\SendAiResponse;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\MediaAsset;
use App\Models\Message;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('media');
    Bus::fake([SendAiResponse::class]);

    [$this->user, $this->team] = makeUserWithTeam();
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
    ]);

    $this->asset = MediaAsset::factory()->for($this->team)->create(['kind' => 'image']);

    $this->message = Message::create([
        'conversation_id' => $this->conversation->id,
        'direction'       => 'inbound',
        'sender_type'     => 'contact',
        'content_type'    => 'image',
        'content'         => '[image]',
        'media_asset_id'  => $this->asset->id,
    ]);

    config([
        'services.nararouter.fallback_models' => 'agnes-2.5-flash,mistral-large',
        'services.nararouter.base_url'        => 'https://router.example/v1',
        'services.nararouter.api_key'         => 'test',
    ]);
});

it('caches the AI description on the media_asset and dispatches SendAiResponse', function () {
    Http::fake([
        '*' => Http::response([
            'choices' => [['message' => ['content' => 'A receipt showing a $19.99 total.']]],
        ]),
    ]);

    (new DescribeImage($this->message->id))->handle(
        app(App\Services\Ai\VisionRouter::class),
        app(App\Services\Media\MediaStorage::class),
    );

    $this->asset->refresh();
    expect($this->asset->metadata['ai_description'] ?? null)->toBe('A receipt showing a $19.99 total.');

    Bus::assertDispatched(SendAiResponse::class);
});

it('skips vision call and still dispatches SendAiResponse if no vision-capable model available', function () {
    config(['services.nararouter.fallback_models' => 'mistral-large,deepseek-v4-flash']);

    (new DescribeImage($this->message->id))->handle(
        app(App\Services\Ai\VisionRouter::class),
        app(App\Services\Media\MediaStorage::class),
    );

    $this->asset->refresh();
    expect($this->asset->metadata['ai_description'] ?? null)->toBeNull();

    Bus::assertDispatched(SendAiResponse::class);
});
