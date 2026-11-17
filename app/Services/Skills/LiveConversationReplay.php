<?php

declare(strict_types=1);

namespace App\Services\Skills;

use App\Contracts\AiProviderInterface;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Ai\BuildsConversationPrompts;

/**
 * SKILL 3 — Live Conversation Replay
 *
 * Takes a real conversation + a hypothetical new system prompt, and asks
 * the AI what it WOULD have said. No changes to the conversation; no
 * message sent to the customer. Pure read-only sandbox for prompt tuning.
 *
 * Use case: owner says "the AI is behaving badly on conv#9452". Operator
 * writes a new rule, replays the conv with the new prompt, sees whether
 * the AI would have handled it correctly. If yes, apply to the real
 * AiConfig. If no, iterate on the rule.
 *
 * The replay uses the same prompt-building trait as the production
 * SendAiResponse job, so results match what would happen in prod.
 */
class LiveConversationReplay
{
    use BuildsConversationPrompts;

    public function __construct(private readonly AiProviderInterface $ai) {}

    /**
     * Replay a conversation with an alternative system prompt.
     *
     * @param  int     $conversationId   The conversation to replay
     * @param  string  $altSystemPrompt  New system_prompt to test
     * @param  ?int    $upToMessageId    Replay up to and including this msg;
     *                                   null = replay the whole thing
     * @return array{response: string, context_size: int}
     */
    public function replay(int $conversationId, string $altSystemPrompt, ?int $upToMessageId = null): array
    {
        $conv = Conversation::with(['page.aiConfig', 'contact'])->findOrFail($conversationId);
        $cfg = clone $conv->page->aiConfig;
        $cfg->system_prompt = $altSystemPrompt;

        $query = Message::where('conversation_id', $conversationId)
            ->orderBy('id');
        if ($upToMessageId !== null) {
            $query->where('id', '<=', $upToMessageId);
        }

        $messages = $query->get()->map(function ($m) {
            return [
                'role'    => $m->isInbound() ? 'user' : 'model',
                'content' => $m->content ?? '',
            ];
        })->filter(fn ($m) => trim($m['content']) !== '')->values()->all();

        $systemPrompt = $this->buildSystemPrompt($conv, $cfg);

        // Collapse the message history into one user turn so we can use the
        // simple generateText(systemPrompt, userMessage) interface instead of
        // re-plumbing provider-specific message-array APIs for the replay.
        // Good enough for prompt-tuning previews — not for generating the
        // actual production reply.
        $userBlock = collect($messages)->map(
            fn ($m) => ($m['role'] === 'user' ? 'Customer: ' : 'You: ') . $m['content']
        )->implode("\n\n");

        try {
            $response = $this->ai->generateText($systemPrompt . "\n\n[Replay context — respond as you would to the final customer turn]\n\n" . $userBlock, 'Generate your next reply.');
        } catch (\Throwable $e) {
            return [
                'response'     => '[REPLAY ERROR: ' . $e->getMessage() . ']',
                'context_size' => count($messages),
            ];
        }

        return [
            'response'     => trim((string) $response),
            'context_size' => count($messages),
        ];
    }
}
