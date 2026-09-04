<?php

declare(strict_types=1);

use App\Contracts\AiProviderInterface;
use App\Jobs\ClassifyCommentJob;
use App\Jobs\SendAiCommentReplyJob;
use App\Models\Comment;
use Illuminate\Support\Facades\Queue;

it('dispatches SendAiCommentReplyJob when classifier says Q', function () {
    Queue::fake();
    $comment = Comment::factory()->create();

    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('generateText')->once()->andReturn('Q');

    (new ClassifyCommentJob($comment->id))->handle($ai);

    Queue::assertPushed(SendAiCommentReplyJob::class);
});

it('dispatches SendAiCommentReplyJob when classifier says C', function () {
    Queue::fake();
    $comment = Comment::factory()->create();
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('generateText')->once()->andReturn('C');

    (new ClassifyCommentJob($comment->id))->handle($ai);

    Queue::assertPushed(SendAiCommentReplyJob::class);
});

it('marks decision=filtered_mode when classifier says N', function () {
    Queue::fake();
    $comment = Comment::factory()->create();
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('generateText')->once()->andReturn('N');

    (new ClassifyCommentJob($comment->id))->handle($ai);

    expect($comment->fresh()->decision)->toBe(Comment::DECISION_FILTERED_MODE);
    Queue::assertNotPushed(SendAiCommentReplyJob::class);
});

it('marks decision=error_ai when classifier returns empty string', function () {
    Queue::fake();
    $comment = Comment::factory()->create();
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('generateText')->once()->andReturn('');

    (new ClassifyCommentJob($comment->id))->handle($ai);

    expect($comment->fresh()->decision)->toBe(Comment::DECISION_ERROR_AI);
});

it('treats unexpected letter as N (safety default)', function () {
    Queue::fake();
    $comment = Comment::factory()->create();
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('generateText')->once()->andReturn('yolo');

    (new ClassifyCommentJob($comment->id))->handle($ai);

    expect($comment->fresh()->decision)->toBe(Comment::DECISION_FILTERED_MODE);
});
