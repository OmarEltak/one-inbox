<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\AiProviderInterface;
use App\Exceptions\AiQuotaExhausted;
use App\Models\AiConfig;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * NaraRouter provider — OpenAI-compatible chat completions router that fronts
 * multiple upstream models (Claude, GPT, Gemini, etc.). Selected via env:
 *
 *   AI_PROVIDER=nararouter
 *   NARAROUTER_MODEL=claude-sonnet-4.5
 *
 * Because the API speaks OpenAI's chat-completions format, this provider is
 * also the drop-in seed for using OpenAI, OpenRouter, Together, Groq, etc.
 * later — swap only the base URL and model name.
 */
class NaraRouterProvider implements AiProviderInterface
{
    use BuildsConversationPrompts;

    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;
    protected string $scoringModel;

    public function __construct()
    {
        $this->apiKey       = config('services.nararouter.api_key', '');
        $this->baseUrl      = rtrim(config('services.nararouter.base_url', 'https://router.bynara.id/v1'), '/');
        $this->model        = config('services.nararouter.model', 'claude-sonnet-4.5');
        $this->scoringModel = config('services.nararouter.scoring_model', $this->model);
    }

    public function generateResponse(Conversation $conversation, Message $incomingMessage, AiConfig $config): string
    {
        $systemPrompt        = $this->buildSystemPrompt($conversation, $config);
        $conversationHistory = $this->buildConversationHistory($conversation);

        return $this->callChat($this->model, $systemPrompt, $conversationHistory);
    }

    public function scoreMessage(Message $message, Contact $contact): array
    {
        $prompt = "Analyze this customer message and return JSON with lead scoring signals.\n\n"
            . "Customer message: \"{$message->content}\"\n"
            . "Current lead score: {$contact->lead_score}\n"
            . "Current status: {$contact->lead_status}\n\n"
            . "Return a JSON array of events. Each event has: event_type (string), score_change (int, -30 to +30), reason (string).\n"
            . "Scoring rules:\n"
            . "- Asked about pricing: +20\n"
            . "- Asked about availability: +15\n"
            . "- Asked for discount: +15\n"
            . "- Shared contact info (email/phone): +25\n"
            . "- Requested meeting/call: +30\n"
            . "- Mentioned competitor: +10\n"
            . "- Said 'not interested' or declining: -30\n"
            . "- Said 'too expensive' (objection but engaged): +5\n"
            . "- General question/interest: +5\n"
            . "- Greeting/casual: +3\n\n"
            . "Return ONLY valid JSON array, no other text.";

        $result = $this->callChat($this->scoringModel, 'You are a lead scoring AI. Return only valid JSON.', [
            ['role' => 'user', 'content' => $prompt],
        ]);

        try {
            $cleaned = trim($result, " \t\n\r\0\x0B`json");
            $events  = json_decode($cleaned, true, 512, JSON_THROW_ON_ERROR);

            return is_array($events) ? $events : [];
        } catch (\JsonException $e) {
            Log::warning('AI scoring returned invalid JSON', ['response' => $result]);

            return [];
        }
    }

    public function analyzeConversation(Conversation $conversation): array
    {
        $history     = $this->buildConversationHistory($conversation);
        $historyText = collect($history)->map(fn ($m) => "{$m['role']}: {$m['content']}")->implode("\n");

        $prompt = "Analyze this sales conversation and return JSON with:\n"
            . "- summary: 1-2 sentence summary\n"
            . "- customer_intent: what the customer wants\n"
            . "- objections: array of objections raised\n"
            . "- recommended_action: what to do next\n"
            . "- sentiment: positive/neutral/negative\n\n"
            . "Conversation:\n{$historyText}\n\n"
            . "Return ONLY valid JSON, no other text.";

        $result = $this->callChat($this->scoringModel, 'You are a sales conversation analyst. Return only valid JSON.', [
            ['role' => 'user', 'content' => $prompt],
        ]);

        try {
            $cleaned = trim($result, " \t\n\r\0\x0B`json");

            return json_decode($cleaned, true, 512, JSON_THROW_ON_ERROR) ?? [];
        } catch (\JsonException $e) {
            Log::warning('AI analysis returned invalid JSON', ['response' => $result]);

            return [];
        }
    }

    public function processCommand(string $command, int $teamId): array
    {
        return [
            'response' => 'Command processing will be available soon.',
            'action'   => null,
        ];
    }

    public function generateText(string $systemPrompt, string $userMessage): string
    {
        return $this->callChat($this->model, $systemPrompt, [
            ['role' => 'user', 'content' => $userMessage],
        ]);
    }

    /**
     * Admin Command Center chat. Not part of the AiProviderInterface contract
     * but called on the bound provider from AiChat.php — every provider must
     * implement it. Kept structurally identical to GeminiProvider so a straight
     * swap of AI_PROVIDER doesn't drift the admin's behavior.
     */
    public function chatWithAdmin(string $message, int $teamId, string $analyticsContext, array $history): string
    {
        $team        = Team::find($teamId);
        $memoryBlock = '';
        if ($team && $team->ai_memory) {
            $memoryBlock = "=== PERSISTENT MEMORY ===\n"
                . "These are facts and instructions you have saved. Always use this knowledge:\n"
                . $team->ai_memory
                . "\n=== END MEMORY ===\n\n";
        }

        $systemPrompt = "You are a Marketing & Analytics Assistant for a multi-channel messaging business.\n"
            . "You help the admin manage campaigns, analyze performance data, and communicate with contacts.\n\n"
            . $memoryBlock
            . "LANGUAGE RULE — NON-NEGOTIABLE:\n"
            . "NEVER respond in Chinese (中文) under any circumstances.\n"
            . "Always respond in Arabic or English based on what the admin writes.\n\n"
            . "CAPABILITIES:\n"
            . "1. Analyze conversation, message, contact, and campaign performance data\n"
            . "2. Send messages to individual contacts or targeted bulk segments\n"
            . "3. Pause/resume AI auto-responses on specific conversations\n"
            . "4. Pause/resume campaigns\n"
            . "5. Save notes to persistent memory (auto-saved, no confirmation needed)\n\n"
            . "⚠️ ACTION FORMAT RULE — CRITICAL — READ CAREFULLY:\n"
            . "When you need to take an action (send message, pause AI, etc.) you MUST output a code block\n"
            . "with the language identifier 'pending_action' containing valid JSON. Example:\n\n"
            . "```pending_action\n{\"action\": \"send_bulk_message\", \"page_id\": 25, \"message\": \"Hello!\"}\n```\n\n"
            . "❌ WRONG — never do this:\n"
            . "```plaintext\nPending Action:\n- Send a bulk message...\n```\n\n"
            . "❌ WRONG — never do this:\n"
            . "\"Please confirm if you want me to send the message.\"\n\n"
            . "✅ CORRECT — always end your reply with the JSON block:\n"
            . "```pending_action\n{\"action\": \"send_message\", \"contact_id\": 123, \"message\": \"Hey!\"}\n```\n\n"
            . "After including the pending_action block, STOP. Do not say 'sent', 'done', or 'completed'.\n"
            . "The system will show the admin a confirmation button. Wait for that.\n\n"
            . "AVAILABLE PENDING ACTIONS (use pending_action block for all):\n"
            . "```pending_action\n{\"action\": \"send_message\", \"contact_id\": 123, \"message\": \"Hey! We have a special offer...\"}\n```\n\n"
            . "```pending_action\n{\"action\": \"send_bulk_message\", \"page_id\": 25, \"message\": \"Hi everyone!\"}\n```\n\n"
            . "```pending_action\n{\"action\": \"send_bulk_message\", \"page_id\": 25, \"min_score\": 25, \"message\": \"Exclusive offer!\"}\n```\n\n"
            . "```pending_action\n{\"action\": \"send_bulk_message\", \"status\": \"hot\", \"message\": \"Don't miss out!\"}\n```\n\n"
            . "```pending_action\n{\"action\": \"pause_ai\", \"contact_id\": 123}\n```\n\n"
            . "```pending_action\n{\"action\": \"resume_ai\", \"contact_id\": 123}\n```\n\n"
            . "```pending_action\n{\"action\": \"pause_campaign\", \"campaign_id\": 1}\n```\n\n"
            . "```pending_action\n{\"action\": \"resume_campaign\", \"campaign_id\": 1}\n```\n\n"
            . "AUTO ACTIONS (execute immediately — use action block, only for save_memory):\n"
            . "```action\n{\"action\": \"save_memory\", \"content\": \"Important fact to remember\"}\n```\n\n"
            . "MEMORY RULES:\n"
            . "- When the admin says 'remember that...' or asks you to save/note something, use save_memory\n"
            . "- Memory persists across sessions\n"
            . "- Save concise, factual notes\n\n"
            . "CAMPAIGN RULES:\n"
            . "- Always reference campaigns by their ID and name from the data below\n"
            . "- When asked to pause/resume a campaign, show the campaign details before the pending_action block\n\n"
            . "MESSAGING RULES:\n"
            . "- When crafting bulk messages, be a creative and persuasive copywriter\n"
            . "- Match the language of the target audience (Arabic contacts → Arabic message)\n"
            . "- For bulk sends to a specific page, use page_id from the Connected Pages list below\n"
            . "- Always state how many contacts will be targeted before the pending_action block\n"
            . "- Be concise and conversational\n\n"
            . $analyticsContext;

        // Use the last entries from history (already limited by caller), skip the current message (last entry)
        $conversationHistory = array_slice($history, 0, -1);
        $conversationHistory[] = ['role' => 'user', 'content' => $message];

        // Admin path: we want a visible message when things break (unlike the
        // customer path which stays silent). Catch quota specifically so the
        // admin knows what happened; treat empty as a generic outage.
        try {
            $response = $this->callChat($this->model, $systemPrompt, $conversationHistory, 2000);
        } catch (AiQuotaExhausted) {
            return 'The AI service is temporarily unavailable — daily quota reached. Try again after the quota resets, or upgrade your plan.';
        }

        if ($response === '') {
            return 'The AI service is temporarily unavailable (API error). Please try again in a few minutes.';
        }

        return $response;
    }

    /**
     * OpenAI-compatible chat completions call. Prepends the system prompt as a
     * system-role message (in contrast to Gemini's separate system_instruction
     * field), and reads the reply from choices[0].message.content.
     */
    protected function callChat(string $model, string $systemPrompt, array $conversationHistory, int $maxOutputTokens = 1000): string
    {
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($conversationHistory as $msg) {
            $messages[] = [
                'role'    => $msg['role'] === 'user' ? 'user' : 'assistant',
                'content' => $msg['content'],
            ];
        }

        // Ensure the last user turn exists so the model has something to reply to.
        $lastRole = end($messages)['role'] ?? null;
        if ($lastRole !== 'user') {
            $messages[] = ['role' => 'user', 'content' => 'Continue the conversation naturally.'];
        }

        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout(60)
            ->post("{$this->baseUrl}/chat/completions", [
                'model'       => $model,
                'messages'    => $messages,
                'temperature' => 0.7,
                'max_tokens'  => $maxOutputTokens,
            ]);

        if ($response->failed()) {
            Log::error('NaraRouter API call failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'model'  => $model,
            ]);

            // 429 typically means daily/per-minute quota exhausted or rate limited.
            // Signal it specifically so callers can pause AI and notify the team;
            // any other failure is a silent no-reply so the customer never sees
            // an English apology from the bot.
            if ($response->status() === 429) {
                throw new AiQuotaExhausted('NaraRouter returned 429 (quota/rate limit).');
            }

            return '';
        }

        return $response->json('choices.0.message.content', '');
    }
}
