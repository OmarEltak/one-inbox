<?php

declare(strict_types=1);

use App\Jobs\BackfillPageMessages;
use App\Models\Contact;
use App\Models\Conversation;
use App\Services\Platforms\FacebookPlatform;
use Illuminate\Support\Facades\Http;

/**
 * Regression: when BackfillPageMessages bails after 3 auth failures it MUST
 * clear backfill_in_progress_started_at so the /connections "Loading your
 * messages from X 0/5" progress bar stops lying. Without this, Brandk sat
 * at 0/5 for days (2026-10-27 incident) because the UI query
 * (Connections\Index::backfillStates) filters on that key being not-null.
 */
beforeEach(function () {
    [, $this->team] = makeUserWithTeam();
    $this->page = makeEmailPage($this->team);
    $this->page->update([
        'platform'         => 'facebook',
        'platform_page_id' => 'PAGE1',
        'name'             => 'Brandk',
    ]);
    foreach (range(1, 5) as $i) {
        $c = Contact::create(['team_id' => $this->team->id, 'name' => "C$i"]);
        Conversation::create([
            'team_id'                  => $this->team->id,
            'page_id'                  => $this->page->id,
            'contact_id'               => $c->id,
            'platform'                 => 'facebook',
            'platform_conversation_id' => "U$i",
            'status'                   => 'open',
            'last_message_at'          => now()->subDays($i),
        ]);
    }
});

test('backfill clears in-progress marker and records paused reason when zero chats make forward progress', function () {
    // Simulates the Brandk-stuck case: Meta responds with 400/OAuthException,
    // FacebookPlatform::fetchAndStoreMessages logs + returns 0 (no throw), so
    // no conversation gets metadata.messages_fetched = true. The job should
    // detect zero forward progress and clear the in-progress marker instead
    // of leaving the UI stuck at "Loading your messages from Brandk 0/5".
    Http::fake(['*' => Http::response(['error' => ['code' => 190, 'message' => 'OAuthException']], 400)]);

    (new BackfillPageMessages($this->page->id))->handle(app(FacebookPlatform::class));

    $meta = $this->page->fresh()->metadata ?? [];

    expect(array_key_exists('backfill_in_progress_started_at', $meta))->toBeTrue()
        ->and($meta['backfill_in_progress_started_at'])->toBeNull()
        ->and($meta['backfill_paused_reason'] ?? null)->toBe('no_progress')
        ->and($meta['backfill_paused_at'] ?? null)->not->toBeNull();
});
