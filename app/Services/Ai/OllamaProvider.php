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

    public function generateText(string $systemPrompt, string $userMessage): string
    {
        return $this->callOllama($systemPrompt, [
            ['role' => 'user', 'content' => $userMessage],
        ]);
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

        // Add the current message
        $conversationHistory[] = ['role' => 'user', 'content' => $message];

        $response = $this->callOllama($systemPrompt, $conversationHistory, 2000);

        // Replace customer-facing fallback with admin-appropriate message
        if (str_contains($response, 'connect you with a team member')) {
            return 'The AI service is temporarily unavailable. Please check that Ollama is running and try again.';
        }

        return $response;
    }

    protected function isVisionModel(): bool
    {
        $m = strtolower($this->model);
        return str_contains($m, 'vl') || str_contains($m, 'llava') || str_contains($m, 'vision')
            || str_contains($m, 'moondream') || str_contains($m, 'bakllava');
    }

    protected function callOllama(string $systemPrompt, array $history, int $maxTokens = 500): string
    {
        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        foreach ($history as $msg) {
            $content = $msg['content'];
            if ($this->isVisionModel()
                && ($msg['content_type'] ?? 'text') === 'image'
                && !empty($msg['media_url'])
            ) {
                try {
                    $imageResponse = Http::timeout(10)->get($msg['media_url']);
                    $mime = explode(';', $imageResponse->header('Content-Type') ?? 'image/jpeg')[0];
                    $dataUri = "data:{$mime};base64," . base64_encode($imageResponse->body());
                    $content = [
                        ['type' => 'text', 'text' => 'The customer sent this image:'],
                        ['type' => 'image_url', 'image_url' => ['url' => $dataUri]],
                    ];
                } catch (\Throwable) {
                    $content = '[Image — failed to load]';
                }
            }
            $messages[] = [
                'role'    => $msg['role'] === 'model' ? 'assistant' : 'user',
                'content' => $content,
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
