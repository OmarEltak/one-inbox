<?php

namespace App\Services\Ai;

use App\Contracts\AiProviderInterface;
use App\Models\AiConfig;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaProvider implements AiProviderInterface
{
    use BuildsConversationPrompts;

    protected string $baseUrl;
    protected string $model;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.ollama.base_url', 'http://localhost:11434'), '/');
        $this->model = config('services.ollama.model', 'qwen2.5:7b');
    }

    public function generateResponse(Conversation $conversation, Message $incomingMessage, AiConfig $config): string
    {
        $systemPrompt = $this->buildSystemPrompt($conversation, $config);
        $history = $this->buildConversationHistory($conversation);

        return $this->callOllama($systemPrompt, $history);
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

        $result = $this->callOllama('You are a lead scoring AI. Return only valid JSON.', [
            ['role' => 'user', 'content' => $prompt],
        ]);

        try {
            $cleaned = trim($result, " \t\n\r\0\x0B`json");
            $events = json_decode($cleaned, true, 512, JSON_THROW_ON_ERROR);

            return is_array($events) ? $events : [];
        } catch (\JsonException $e) {
            Log::warning('Ollama scoring returned invalid JSON', ['response' => $result]);

            return [];
        }
    }

    public function analyzeConversation(Conversation $conversation): array
    {
        $history = $this->buildConversationHistory($conversation);
        $historyText = collect($history)->map(fn ($m) => "{$m['role']}: {$m['content']}")->implode("\n");

        $prompt = "Analyze this sales conversation and return JSON with:\n"
            . "- summary: 1-2 sentence summary\n"
            . "- customer_intent: what the customer wants\n"
            . "- objections: array of objections raised\n"
            . "- recommended_action: what to do next\n"
            . "- sentiment: positive/neutral/negative\n\n"
            . "Conversation:\n{$historyText}\n\n"
            . "Return ONLY valid JSON, no other text.";

        $result = $this->callOllama('You are a sales conversation analyst. Return only valid JSON.', [
            ['role' => 'user', 'content' => $prompt],
        ]);

        try {
            $cleaned = trim($result, " \t\n\r\0\x0B`json");

            return json_decode($cleaned, true, 512, JSON_THROW_ON_ERROR) ?? [];
        } catch (\JsonException $e) {
            Log::warning('Ollama analysis returned invalid JSON', ['response' => $result]);

            return [];
        }
    }

    public function processCommand(string $command, int $teamId): array
    {
        // Will be fully implemented in Phase 3.3 (Admin Command Center)
        return [
            'response' => 'Command processing will be available soon.',
            'action' => null,
        ];
    }

    public function chatWithAdmin(string $message, int $teamId, string $analyticsContext, array $history): string
    {
        $team = Team::find($teamId);
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
            . "CAPABILITIES:\n"
            . "1. Analyze conversation, message, contact, and campaign performance data\n"
            . "2. Send messages to individual contacts or targeted bulk segments\n"
            . "3. Pause/resume AI auto-responses on specific conversations\n"
            . "4. Pause/resume campaigns\n"
            . "5. Save notes to persistent memory (auto-saved, no confirmation needed)\n\n"
            . "⚠️ CONFIRMATION RULE — MANDATORY:\n"
            . "Before ANY action that sends messages, modifies campaigns, or affects contacts, you MUST:\n"
            . "1. Describe exactly what you plan to do: who will receive it, what the message says, how many contacts are affected, or which campaign will change\n"
            . "2. Include ONE `pending_action` block (NOT an `action` block) at the end of your message\n"
            . "3. STOP — do not say 'sent', 'done', or 'completed' — wait for the admin to confirm\n"
            . "4. Only use `action` blocks (auto-execute) for save_memory\n\n"
            . "PENDING ACTIONS (always require confirmation — use pending_action block):\n"
            . "```pending_action\n{\"action\": \"send_message\", \"contact_id\": 123, \"message\": \"Hey! We have a special offer for you...\"}\n```\n\n"
            . "```pending_action\n{\"action\": \"send_bulk_message\", \"min_score\": 25, \"message\": \"Hi! Exclusive offer just for you...\"}\n```\n\n"
            . "```pending_action\n{\"action\": \"send_bulk_message\", \"status\": \"hot\", \"message\": \"Don't miss out on our sale!\"}\n```\n\n"
            . "```pending_action\n{\"action\": \"pause_ai\", \"contact_id\": 123}\n```\n\n"
            . "```pending_action\n{\"action\": \"resume_ai\", \"contact_id\": 123}\n```\n\n"
            . "```pending_action\n{\"action\": \"pause_campaign\", \"campaign_id\": 1}\n```\n\n"
            . "```pending_action\n{\"action\": \"resume_campaign\", \"campaign_id\": 1}\n```\n\n"
            . "AUTO ACTIONS (execute immediately without confirmation — use action block):\n"
            . "```action\n{\"action\": \"save_memory\", \"content\": \"Important fact to remember across sessions\"}\n```\n\n"
            . "MEMORY RULES:\n"
            . "- When the admin says 'remember that...' or asks you to save/note something, use save_memory\n"
            . "- Memory persists across sessions\n"
            . "- Save concise, factual notes\n"
            . "- Each save_memory appends to existing memory, it does not replace it\n\n"
            . "CAMPAIGN RULES:\n"
            . "- Always reference campaigns by their ID and name from the data below\n"
            . "- When asked to pause/resume a campaign, show the campaign details in your summary before the pending_action block\n"
            . "- Suggest campaign improvements based on reply rate and sent/total ratios\n\n"
            . "MESSAGING RULES:\n"
            . "- When crafting bulk messages, be a creative and persuasive copywriter\n"
            . "- Match the language of the target audience (Arabic contacts → Arabic message)\n"
            . "- For bulk sends, always state how many contacts will be targeted before confirming\n"
            . "- Be concise and conversational\n\n"
            . $analyticsContext;

        // Use the last entries from history (already limited by caller), skip the current message (last entry)
        $conversationHistory = array_slice($history, 0, -1);

        // Add the current message
        $conversationHistory[] = ['role' => 'user', 'content' => $message];

        $response = $this->callOllama($systemPrompt, $conversationHistory, 1000);

        // Replace customer-facing fallback with admin-appropriate message
        if (str_contains($response, 'connect you with a team member')) {
            return 'The AI service is temporarily unavailable. Please check that Ollama is running and try again.';
        }

        return $response;
    }

    protected function callOllama(string $systemPrompt, array $history, int $maxTokens = 500): string
    {
        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        foreach ($history as $msg) {
            $messages[] = [
                'role'    => $msg['role'] === 'model' ? 'assistant' : 'user',
                'content' => $msg['content'],
            ];
        }

        $response = Http::timeout(60)->post("{$this->baseUrl}/v1/chat/completions", [
            'model'       => $this->model,
            'messages'    => $messages,
            'temperature' => 0.7,
            'max_tokens'  => $maxTokens,
        ]);

        if ($response->failed()) {
            Log::error('Ollama API call failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return "I apologize, I'm having a moment. Let me connect you with a team member.";
        }

        $data = $response->json();

        return $data['choices'][0]['message']['content'] ?? '';
    }
}
