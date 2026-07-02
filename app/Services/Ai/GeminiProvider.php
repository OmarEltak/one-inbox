<?php

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

class GeminiProvider implements AiProviderInterface
{
    use BuildsConversationPrompts;

    protected string $apiKey;
    protected string $model;
    protected string $scoringModel;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', '');
        $this->model = config('services.gemini.model', 'gemini-2.5-flash');
        $this->scoringModel = config('services.gemini.scoring_model', 'gemini-2.5-flash');
    }

    public function generateResponse(Conversation $conversation, Message $incomingMessage, AiConfig $config): string
    {
        $systemPrompt = $this->buildSystemPrompt($conversation, $config);
        $conversationHistory = $this->buildConversationHistory($conversation);

        $response = $this->callGemini($this->model, $systemPrompt, $conversationHistory);

        return $response;
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

        $result = $this->callGemini($this->scoringModel, 'You are a lead scoring AI. Return only valid JSON.', [
            ['role' => 'user', 'content' => $prompt],
        ]);

        try {
            $cleaned = trim($result, " \t\n\r\0\x0B`json");
            $events = json_decode($cleaned, true, 512, JSON_THROW_ON_ERROR);

            return is_array($events) ? $events : [];
        } catch (\JsonException $e) {
            Log::warning('AI scoring returned invalid JSON', ['response' => $result]);

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

        $result = $this->callGemini($this->scoringModel, 'You are a sales conversation analyst. Return only valid JSON.', [
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
        // Will be fully implemented in Phase 3.3 (Admin Command Center)
        return [
            'response' => 'Command processing will be available soon.',
            'action' => null,
        ];
    }

    public function generateText(string $systemPrompt, string $userMessage): string
    {
        return $this->callGemini($this->model, $systemPrompt, [
            ['role' => 'user', 'content' => $userMessage],
        ]);
    }

    public function chatWithAdmin(string $message, int $teamId, string $analyticsContext, array $history): string
    {
        $systemPrompt = $this->buildAdminChatSystemPrompt($teamId, $analyticsContext);

        // Use the last entries from history (already limited by caller), skip the current message (last entry)
        $conversationHistory = array_slice($history, 0, -1);
        $conversationHistory[] = ['role' => 'user', 'content' => $message];

        try {
            $response = $this->callGemini($this->model, $systemPrompt, $conversationHistory, 2000);
        } catch (AiQuotaExhausted) {
            return 'The AI service is temporarily unavailable — daily quota reached. Try again after the quota resets, or upgrade your plan.';
        }

        if ($response === '') {
            return 'The AI service is temporarily unavailable (API error). Please try again in a few minutes.';
        }

        return $response;
    }

    protected function callGemini(string $model, string $systemPrompt, array $conversationHistory, int $maxOutputTokens = 1000): string
    {
        $contents = [];

        foreach ($conversationHistory as $msg) {
            $contents[] = [
                'role' => $msg['role'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $msg['content']]],
            ];
        }

        // Ensure conversation starts with a user message
        if (empty($contents) || $contents[0]['role'] !== 'user') {
            array_unshift($contents, [
                'role' => 'user',
                'parts' => [['text' => 'Hello']],
            ]);
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $response = Http::withQueryParameters(['key' => $this->apiKey])
            ->post($url, [
                'system_instruction' => [
                    'parts' => [['text' => $systemPrompt]],
                ],
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => $maxOutputTokens,
                    // Gemini 2.5 burns hidden "thinking" tokens against the output
                    // budget by default. For short customer replies and JSON scoring
                    // we don't want any of that — it adds latency and starves the
                    // actual response. thinkingBudget=0 disables it entirely.
                    'thinkingConfig' => [
                        'thinkingBudget' => 0,
                    ],
                ],
            ]);

        if ($response->failed()) {
            Log::error('Gemini API call failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            if ($response->status() === 429) {
                throw new AiQuotaExhausted('Gemini returned 429 (quota/rate limit).');
            }

            return '';
        }

        $data = $response->json();

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }
}
