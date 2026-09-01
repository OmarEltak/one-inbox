<?php

declare(strict_types=1);

use App\Models\ConnectedAccount;
use App\Models\Page;
use App\Models\Team;
use App\Services\Platforms\WhatsAppPlatform;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('media');
    $this->team = Team::factory()->create();
    $this->account = ConnectedAccount::factory()->for($this->team)->create([
        'platform'     => 'whatsapp',
        'access_token' => 'test-token',
    ]);
    $this->page = Page::factory()->for($this->team)->create([
        'platform'                 => 'whatsapp',
        'connected_account_id'     => $this->account->id,
    ]);
});

it('downloads inbound media using two-step WA Cloud API pattern', function () {
    Http::fake([
        'graph.facebook.com/*/MEDIA_ID' => Http::response([
            'url'       => 'https://cdn.example/blob',
            'mime_type' => 'audio/ogg',
            'sha256'    => hash('sha256', 'audiobytes'),
            'file_size' => 10,
            'id'        => 'MEDIA_ID',
        ]),
        'cdn.example/blob' => Http::response('audiobytes', 200, ['Content-Type' => 'audio/ogg']),
    ]);

    $platform = app(WhatsAppPlatform::class);
    $asset = $platform->downloadInboundMedia(
        page: $this->page,
        mediaId: 'MEDIA_ID',
        kind: 'audio',
    );

    expect($asset->mime_type)->toBe('audio/ogg')
        ->and($asset->kind)->toBe('audio')
        ->and($asset->size_bytes)->toBe(10);

    Http::assertSent(fn (Request $r) =>
        str_contains($r->url(), '/MEDIA_ID') && $r->hasHeader('Authorization', 'Bearer test-token')
    );
});
