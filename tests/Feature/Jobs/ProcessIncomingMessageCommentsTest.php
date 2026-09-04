<?php

declare(strict_types=1);

use App\Contracts\AiProviderInterface;
use App\Jobs\IngestCommentJob;
use App\Jobs\ProcessIncomingMessage;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Queue;

it('dispatches IngestCommentJob on FB feed comment payload', function () {
    Queue::fake();
    $log = WebhookLog::create([
        'platform'   => 'facebook',
        'event_type' => 'page',
        'payload'    => [
            'object' => 'page',
            'entry'  => [[
                'id'      => 'PAGE_1',
                'changes' => [[
                    'field' => 'feed',
                    'value' => [
                        'item'         => 'comment',
                        'verb'         => 'add',
                        'comment_id'   => 'c1',
                        'post_id'      => 'PAGE_1_POST1',
                        'from'         => ['id' => 'user1', 'name' => 'Ada'],
                        'message'      => 'How much?',
                        'created_time' => 1756636800,
                    ],
                ]],
            ]],
        ],
    ]);

    (new ProcessIncomingMessage($log->id))->handle(app(AiProviderInterface::class));

    Queue::assertPushed(IngestCommentJob::class, function ($job) {
        return $job->parsed['platform_comment_id'] === 'c1'
            && $job->platformPageId === 'PAGE_1';
    });
});

it('dispatches IngestCommentJob on IG comments payload', function () {
    Queue::fake();
    $log = WebhookLog::create([
        'platform'   => 'instagram',
        'event_type' => 'instagram',
        'payload'    => [
            'object' => 'instagram',
            'entry'  => [[
                'id'      => 'IG_1',
                'changes' => [[
                    'field' => 'comments',
                    'value' => [
                        'id'    => 'ig_c1',
                        'text'  => 'love it',
                        'from'  => ['id' => 'ig_user1', 'username' => 'ada'],
                        'media' => ['id' => 'ig_media1'],
                    ],
                ]],
            ]],
        ],
    ]);

    (new ProcessIncomingMessage($log->id))->handle(app(AiProviderInterface::class));

    Queue::assertPushed(IngestCommentJob::class);
});

it('does NOT dispatch IngestCommentJob when no changes are present', function () {
    Queue::fake();
    $log = WebhookLog::create([
        'platform'   => 'facebook',
        'event_type' => 'page',
        'payload'    => ['object' => 'page', 'entry' => [['id' => 'PAGE_1', 'messaging' => []]]],
    ]);

    (new ProcessIncomingMessage($log->id))->handle(app(AiProviderInterface::class));

    Queue::assertNotPushed(IngestCommentJob::class);
});
