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

class GeminiProvider implements AiProviderInterface
{
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

        $systemPrompt = "You are a powerful business assistant for a messaging platform. You can both analyze data AND take actions.\n\n"
            . $memoryBlock
            . "CAPABILITIES:\n"
            . "1. Answer analytics questions using the data below\n"
            . "2. Send messages to contacts (individual or bulk)\n"
            . "3. Pause/resume AI for conversations\n"
            . "4. Save important information to your persistent memory\n\n"
            . "TO TAKE AN ACTION, include an action block in your response like this:\n"
            . "```action\n{\"action\": \"send_message\", \"contact_id\": 123, \"message\": \"Hey! We have a special offer...\"}\n```\n\n"
            . "```action\n{\"action\": \"send_bulk_message\", \"min_score\": 25, \"message\": \"Hi! We have an exclusive offer for you...\"}\n```\n\n"
            . "```action\n{\"action\": \"send_bulk_message\", \"status\": \"hot\", \"message\": \"Don't miss out on our sale!\"}\n```\n\n"
            . "```action\n{\"action\": \"pause_ai\", \"contact_id\": 123}\n```\n\n"
            . "```action\n{\"action\": \"resume_ai\", \"contact_id\": 123}\n```\n\n"
            . "```action\n{\"action\": \"save_memory\", \"content\": \"Important fact to remember across sessions\"}\n```\n\n"
            . "MEMORY RULES:\n"
            . "- When the admin says 'remember that...' or asks you to save/note something, use the save_memory action\n"
            . "- Memory persists across sessions — you will always have access to saved facts\n"
            . "- Save concise, factual notes (e.g. 'Best seller: black oversized tee', 'Sizes: S, M, L, XL')\n"
            . "- Each save_memory appends to existing memory, it does not replace it\n\n"
            . "RULES:\n"
            . "- When the admin asks you to send messages, craft a compelling sales message and include the action block\n"
            . "- For bulk messages, use min_score or status filters to target the right contacts\n"
            . "- Always tell the admin what you're about to do before the action block\n"
            . "- Match the language of the target audience (if contacts are Arabic-speaking, write the message in Arabic)\n"
            . "- Be a creative, persuasive copywriter when crafting promotional messages\n"
            . "- Be concise and conversational\n\n"
            . $analyticsContext;

        // Use the last entries from history (already limited by caller), skip the current message (last entry)
        $conversationHistory = array_slice($history, 0, -1);

        // Add the current message
        $conversationHistory[] = ['role' => 'user', 'content' => $message];

        $response = $this->callGemini($this->model, $systemPrompt, $conversationHistory, 1000);

        // Replace customer-facing fallback with admin-appropriate message
        if (str_contains($response, 'connect you with a team member')) {
            return 'The AI service is temporarily unavailable (API rate limit or error). Please try again in a few minutes.';
        }

        return $response;
    }

    protected function buildSystemPrompt(Conversation $conversation, AiConfig $config): string
    {
        $contact = $conversation->contact;
        $parts = [];

        $parts[] = "You are an elite sales closer for a business. You respond to customer messages in DMs. You are the best salesman in the world — charming, persuasive, and relentless in guiding every conversation toward a sale while keeping it natural and professional.";

        if ($config->business_description) {
            $parts[] = "Business: {$config->business_description}";
        }

        if ($config->product_catalog) {
            $parts[] = "Products/Services: " . json_encode($config->product_catalog);
        }

        if ($config->pricing_info) {
            $parts[] = "Pricing: " . json_encode($config->pricing_info);
        }

        if ($config->faq) {
            $parts[] = "FAQ: " . json_encode($config->faq);
        }

        if ($config->sales_methodology) {
            $parts[] = "Sales approach: " . json_encode($config->sales_methodology);
        }

        $parts[] = "Tone: {$config->tone}";

        // Language mirroring — CRITICAL
        $parts[] = "LANGUAGE RULE (MANDATORY): You MUST detect the language the customer is writing in and respond in EXACTLY the same language. If they write in Arabic, respond in Arabic. If they write in French, respond in French. If they write in English, respond in English. If they mix languages, match their dominant language. NEVER respond in a different language than the customer. This is non-negotiable.";

        if ($contact) {
            $parts[] = "Customer lead score: {$contact->lead_score}/100 ({$contact->lead_status})";

            if ($contact->lead_score < 30) {
                $parts[] = "Strategy: This is a new lead. Build rapport quickly, ask smart qualifying questions to understand their needs. Be warm, approachable, and genuinely interested. Find their pain point.";
            } elseif ($contact->lead_score < 70) {
                $parts[] = "Strategy: This is a warm lead. Create urgency, handle objections confidently, show the product's value clearly. Use social proof, limited offers, and FOMO. Always steer toward next steps (sizing, pricing, ordering).";
            } else {
                $parts[] = "Strategy: This is a HOT lead — CLOSE THE SALE. Be direct, offer to finalize the order, suggest specific products. Create urgency (limited stock, special offer). Ask 'Should I put this aside for you?' or 'What size do you need so I can confirm your order?'";
            }
        }

        $parts[] = "Sales Rules:\n"
            . "- Be concise (DM-appropriate length). No long paragraphs.\n"
            . "- Ask one question at a time to keep the conversation flowing.\n"
            . "- Always push the conversation toward a sale — every message should move closer to closing.\n"
            . "- When a customer asks about a product, ALWAYS follow up with sizing/color/quantity to move toward ordering.\n"
            . "- Handle price objections confidently — reframe as value, offer bundles, highlight quality.\n"
            . "- Never say 'I don't know' — if unsure about product details, offer to check and get back to them.\n"
            . "- If the customer seems frustrated or explicitly asks for a human, say you'll connect them with a team member.\n"
            . "- Never sound robotic or scripted. Sound like a real person chatting.";

        if ($config->system_prompt) {
            $parts[] = "IMPORTANT INSTRUCTIONS (always follow these): {$config->system_prompt}";
        }

        return implode("\n\n", $parts);
    }

    protected function buildConversationHistory(Conversation $conversation, int $limit = 20): array
    {
        $messages = $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse();

        return $messages->map(fn (Message $msg) => [
            'role' => $msg->isInbound() ? 'user' : 'model',
            'content' => $msg->content ?? '[media message]',
        ])->values()->all();
    }

    protected function callGemini(string $model, string $systemPrompt, array $conversationHistory, int $maxOutputTokens = 500): string
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
                ],
            ]);

        if ($response->failed()) {
            Log::error('Gemini API call failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return 'I apologize, I\'m having a moment. Let me connect you with a team member.';
        }

        $data = $response->json();

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }
}
