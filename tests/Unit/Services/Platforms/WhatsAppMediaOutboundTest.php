<?php

declare(strict_types=1);

use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\Team;
use App\Services\Platforms\WhatsAppPlatform;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('media');
    $this->team = Team::factory()->create();
    $this->account = ConnectedAccount::factory()->for($this->team)->create([
        'platform' => 'whatsapp', 'access_token' => 'tok',
    ]);
    $this->page = Page::factory()->for($this->team)->create([
        'platform' => 'whatsapp',
        'platform_page_id' => '1234567890',
        'connected_account_id' => $this->account->id,
    ]);
    // Seed a real file on the fake disk.
    Storage::disk('media')->put('1/2026/09/asset.jpg', 'fakebytes');
    $this->asset = MediaAsset::factory()->for($this->team)->create([
        'path' => '1/2026/09/asset.jpg', 'mime_type' => 'image/jpeg', 'kind' => 'image',
    ]);
});

it('uploads media to WA in one step and returns the WA media id', function () {
    Http::fake([
        'graph.facebook.com/*/media' => Http::response(['id' => 'wa-media-42']),
    ]);

    $platform = app(WhatsAppPlatform::class);
    $waId = $platform->uploadOutboundMedia($this->page, $this->asset);

    expect($waId)->toBe('wa-media-42');
});

it('sends a media message referencing the WA media id', function () {
    Http::fake([
        'graph.facebook.com/*/messages' => Http::response(['messages' => [['id' => 'wamid.abc']]]),
    ]);

    $platform = app(WhatsAppPlatform::class);
    $messageId = $platform->sendMediaMessage(
        page: $this->page,
        recipientPlatformId: '20111234567',
        mediaAsset: $this->asset,
        waMediaId: 'wa-media-42',
        caption: 'here',
    );

    expect($messageId)->toBe('wamid.abc');
});
