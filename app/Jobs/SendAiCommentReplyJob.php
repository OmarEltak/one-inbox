<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\AiProviderInterface;
use App\Models\AiConfig;
use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendAiCommentReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(public int $commentId)
    {
        $this->onQueue('comments-send');
    }

    public function handle(AiProviderInterface $ai): void
    {
        $comment = Comment::with('page.team', 'page.aiConfig')->find($this->commentId);
        if (! $comment || $comment->decision !== null) {
            return;
        }

        $team   = $comment->page->team;
        $config = $comment->page->aiConfig;

        if (! $team || ! $config || ! $team->canDispatchAi()) {
            $comment->update([
                'decision'        => Comment::DECISION_ERROR_AI,
                'decision_reason' => 'team cannot dispatch AI',
            ]);
            return;
        }

        $settings = $config->comment_settings ?? AiConfig::defaultCommentSettings();
        [$systemPrompt, $userText] = $this->buildPrompt($config, $settings, $comment);
        $reply = trim($ai->generateText($systemPrompt, $userText));

        if ($reply === '') {
            $comment->update([
                'decision'        => Comment::DECISION_ERROR_AI,
                'decision_reason' => 'Nara returned empty string',
            ]);
            return;
        }

        // Public reply via Graph API.
        $graphResp = $this->postPublicReply($comment, $reply);
        if (! $graphResp['ok']) {
            $comment->update([
                'decision'        => Comment::DECISION_ERROR_GRAPH_API,
                'decision_reason' => 'public reply Graph API returned non-2xx',
                'reply_text'      => $reply,
                'graph_error'     => $graphResp['error'] ?? null,
            ]);
            if ($graphResp['status'] >= 500 || $graphResp['status'] === 429) {
                throw new \RuntimeException("Retryable Graph failure: {$graphResp['status']}");
            }
            return;
        }

        $comment->fill([
            'decision'       => Comment::DECISION_REPLIED,
            'reply_text'     => $reply,
            'graph_reply_id' => $graphResp['id'],
        ]);

        $dmMode = $settings['dm_mode'] ?? AiConfig::COMMENT_DM_OFF;
        if ($this->shouldDm($dmMode, $settings, $comment->text)) {
            $dmResp = $this->sendDm($comment, $reply);
            if ($dmResp['ok']) {
                $comment->dm_sent_at = now();
                $comment->dm_graph_message_id = $dmResp['message_id'];
            } else {
                $comment->graph_error = $dmResp['error'] ?? null;
                Log::warning('SendAiCommentReplyJob: DM failed but public reply succeeded', [
                    'comment_id' => $comment->id,
                    'status'     => $dmResp['status'],
                ]);
            }
        }

        $comment->save();
    }

    /** @return array{0: string, 1: string} */
    protected function buildPrompt(AiConfig $config, array $settings, Comment $comment): array
    {
        $persona = "You reply to public comments on the business's " . $comment->page->platform . " page. "
                 . "Keep replies to 1-3 sentences, natural tone, no hard sell. "
                 . "Business: " . mb_substr((string) $config->business_description, 0, 400) . ". "
                 . "Tone: " . ($config->tone ?? 'friendly') . ". "
                 . "Language: " . ($config->language ?? 'en') . ".";
        if (! empty($settings['reply_instructions'])) {
            $persona .= " Extra rules: " . $settings['reply_instructions'];
        }
        $user = "Public comment from {$comment->commenter_name}: \"{$comment->text}\"";

        return [$persona, $user];
    }

    /** @return array{ok: bool, id?: string, status: int, error?: array} */
    protected function postPublicReply(Comment $comment, string $reply): array
    {
        $response = Http::timeout(15)->asJson()->post(
            "https://graph.facebook.com/v21.0/{$comment->platform_comment_id}/comments",
            [
                'message'      => $reply,
                'access_token' => decrypt($comment->page->page_access_token),
            ]
        );

        if ($response->successful()) {
            return ['ok' => true, 'id' => (string) $response->json('id'), 'status' => $response->status()];
        }

        return ['ok' => false, 'status' => $response->status(), 'error' => (array) $response->json()];
    }

    /** @return array{ok: bool, message_id?: string, status: int, error?: array} */
    protected function sendDm(Comment $comment, string $reply): array
    {
        $recipientContainerId = $comment->page->platform === 'instagram'
            ? ($comment->page->metadata['ig_user_id'] ?? $comment->page->platform_page_id)
            : $comment->page->platform_page_id;

        $body = [
            'recipient' => ['comment_id' => $comment->platform_comment_id],
            'message'   => ['text' => $reply],
        ];
        if ($comment->page->platform === 'facebook') {
            $body['messaging_type'] = 'RESPONSE';
        }

        $token = decrypt($comment->page->page_access_token);
        $response = Http::timeout(15)->asJson()->post(
            "https://graph.facebook.com/v21.0/{$recipientContainerId}/messages?access_token=" . urlencode($token),
            $body
        );

        if ($response->successful()) {
            return ['ok' => true, 'message_id' => (string) ($response->json('message_id') ?? ''), 'status' => $response->status()];
        }

        return ['ok' => false, 'status' => $response->status(), 'error' => (array) $response->json()];
    }

    protected function shouldDm(string $mode, array $settings, string $text): bool
    {
        return match ($mode) {
            AiConfig::COMMENT_DM_OFF                => false,
            AiConfig::COMMENT_DM_ALWAYS             => true,
            AiConfig::COMMENT_DM_ON_PURCHASE_INTENT => $this->matchesAny($text, $settings['dm_keywords'] ?? []),
            default                                 => false,
        };
    }

    /** @param array<int, string> $keywords */
    protected function matchesAny(string $text, array $keywords): bool
    {
        $needle = mb_strtolower($text);
        foreach ($keywords as $kw) {
            if ($kw !== '' && mb_strpos($needle, mb_strtolower($kw)) !== false) {
                return true;
            }
        }
        return false;
    }
}
