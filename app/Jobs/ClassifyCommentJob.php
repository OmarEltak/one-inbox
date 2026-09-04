<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\AiProviderInterface;
use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ClassifyCommentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public array $backoff = [30, 120];

    public function __construct(public int $commentId)
    {
        $this->onQueue('comments-ingest');
    }

    public function handle(AiProviderInterface $ai): void
    {
        $comment = Comment::find($this->commentId);
        if (! $comment || $comment->decision !== null) {
            return;
        }

        $system = 'You classify a single public comment into one of three categories. '
                . 'Respond with EXACTLY ONE letter: Q, C, or N. '
                . 'Q: the comment asks a question (explicit or implied). '
                . 'C: the comment expresses a complaint, problem, or negative sentiment. '
                . 'N: neither — praise, greeting, spam, off-topic.';

        $response = trim($ai->generateText($system, $comment->text));

        // Per CLAUDE.md pin #5: empty string means provider failure. Do NOT send.
        if ($response === '') {
            $comment->update([
                'decision'        => Comment::DECISION_ERROR_AI,
                'decision_reason' => 'classifier returned empty (Nara failure)',
            ]);
            return;
        }

        $letter = strtoupper($response[0] ?? 'N');

        if ($letter === 'Q' || $letter === 'C') {
            SendAiCommentReplyJob::dispatch($comment->id);
            return;
        }

        // 'N' or anything unexpected — safety-default to N.
        $comment->update([
            'decision'        => Comment::DECISION_FILTERED_MODE,
            'decision_reason' => "classifier returned '{$letter}'",
        ]);
    }
}
