<?php

use App\Models\Contact;
use App\Models\Conversation;
use App\Services\Platforms\FacebookPlatform;
use Illuminate\Support\Facades\Http;

// Mishkah regression: a >255-char preview aborted the whole import at 28 chats.
test('long previews are truncated, the import continues, archived chats stay archived', function () {
    [, $team] = makeUserWithTeam();
    $page = makeEmailPage($team);
    $page->update(['platform' => 'facebook', 'platform_page_id' => 'PAGE1', 'name' => 'Mishkah']);
    $old = Contact::create(['team_id' => $team->id, 'name' => 'Old']);
    Conversation::create(['team_id' => $team->id, 'page_id' => $page->id, 'contact_id' => $old->id,
        'platform' => 'facebook', 'platform_conversation_id' => 'U2', 'status' => 'archived']);

    $conv = fn ($id, $snippet) => ['id' => "t_$id", 'updated_time' => '2026-09-24T09:31:53+0000', 'snippet' => $snippet,
        'participants' => ['data' => [['id' => $id, 'name' => "Student $id"], ['id' => 'PAGE1', 'name' => 'Mishkah']]]];
    Http::fake(['*' => Http::response(['data' => [$conv('U1', str_repeat('السلام عليكم ', 40)), $conv('U2', 'hi'), $conv('U3', 'سؤال')]])]);

    app(FacebookPlatform::class)->fetchConversations($page);

    expect(Conversation::where('page_id', $page->id)->count())->toBe(3)
        ->and(mb_strlen(Conversation::where('platform_conversation_id', 'U1')->value('last_message_preview')))->toBeLessThanOrEqual(255)
        ->and(Conversation::where('platform_conversation_id', 'U2')->value('status'))->toBe('archived');
});
