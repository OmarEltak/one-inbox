<?php

namespace App\Livewire;

use App\Jobs\SendPlatformMessage;
use App\Models\AiCommand;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use App\Services\Ai\GeminiProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class AiChat extends Component
{
    use WithFileUploads;

    public string $message = '';

    public array $messages = [];

    #[Validate('nullable|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx')]
    public $attachment = null;

    public function mount(): void
    {
        $team = Auth::user()->currentTeam;

        if (! $team) {
            return;
        }

        $this->messages = AiCommand::where('team_id', $team->id)
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get()
            ->reverse()
            ->flatMap(fn (AiCommand $cmd) => [
                ['role' => 'user', 'content' => $cmd->command],
                ['role' => 'assistant', 'content' => $cmd->response],
            ])
            ->values()
            ->all();
    }

    public function removeAttachment(): void
    {
        $this->attachment = null;
    }

    public function sendMessage(): void
    {
        $text = trim($this->message);
        $hasAttachment = $this->attachment !== null;

        if ($text === '' && ! $hasAttachment) {
            return;
        }

        $mediaUrl = null;
        $mediaType = null;

        if ($hasAttachment) {
            $this->validate();
            $team = Auth::user()->currentTeam;
            $teamId = $team?->id ?? 0;
            $path = $this->attachment->store("chat-media/{$teamId}", 'public');
            $mediaUrl = asset('storage/' . $path);
            $mediaType = $this->attachment->getMimeType();
            $this->attachment = null;
        }

        $this->message = '';
        $msgEntry = ['role' => 'user', 'content' => $text ?: '[Shared a file]'];
        if ($mediaUrl) {
            $msgEntry['media_url'] = $mediaUrl;
            $msgEntry['media_type'] = $mediaType;
        }
        $this->messages[] = $msgEntry;

        $team = Auth::user()->currentTeam;

        if (! $team) {
            $this->messages[] = ['role' => 'assistant', 'content' => 'No team selected.'];

            return;
        }

        $analyticsContext = $this->buildAnalyticsContext($team->id);

        $history = collect($this->messages)
            ->filter(fn ($m) => $m['role'] === 'user' || $m['role'] === 'assistant')
            ->map(fn ($m) => [
                'role' => $m['role'] === 'user' ? 'user' : 'model',
                'content' => $m['content'],
            ])
            ->values()
            ->all();

        try {
            $provider = new GeminiProvider;
            $response = $provider->chatWithAdmin($text, $team->id, $analyticsContext, $history);
        } catch (\Throwable $e) {
            $response = 'Sorry, I encountered an error processing your request. Please try again.';
        }

        // Check for and execute any actions in the response
        $actionResult = $this->executeActions($response, $team->id);
        if ($actionResult) {
            $response .= "\n\n" . $actionResult;
        }

        AiCommand::create([
            'team_id' => $team->id,
            'user_id' => Auth::id(),
            'command' => $text,
            'response' => $response,
            'status' => 'completed',
        ]);

        $this->messages[] = ['role' => 'assistant', 'content' => $response];

        $this->dispatch('message-sent');
    }

    /**
     * Parse AI response for action blocks and execute them.
     * Actions are wrapped in ```action JSON blocks.
     */
    protected function executeActions(string &$response, int $teamId): ?string
    {
        // Look for action blocks: ```action { ... } ```
        if (! preg_match_all('/```action\s*(\{.+?\})\s*```/s', $response, $matches)) {
            return null;
        }

        $results = [];

        foreach ($matches[1] as $i => $jsonStr) {
            try {
                $action = json_decode($jsonStr, true, 512, JSON_THROW_ON_ERROR);
                $result = $this->runAction($action, $teamId);
                $results[] = $result;
            } catch (\JsonException $e) {
                $results[] = "Failed to parse action: invalid JSON.";
            } catch (\Throwable $e) {
                Log::error('AI Chat action failed', ['error' => $e->getMessage(), 'action' => $jsonStr]);
                $results[] = "Action failed: {$e->getMessage()}";
            }
        }

        // Remove action blocks from the visible response
        $response = trim(preg_replace('/```action\s*\{.+?\}\s*```/s', '', $response));

        return implode("\n", $results);
    }

    protected function runAction(array $action, int $teamId): string
    {
        $type = $action['action'] ?? null;

        return match ($type) {
            'send_message' => $this->actionSendMessage($action, $teamId),
            'send_bulk_message' => $this->actionSendBulkMessage($action, $teamId),
            'pause_ai' => $this->actionToggleAi($action, $teamId, true),
            'resume_ai' => $this->actionToggleAi($action, $teamId, false),
            'save_memory' => $this->actionSaveMemory($action, $teamId),
            default => "Unknown action: {$type}",
        };
    }

    /**
     * Send a message to a specific contact's most recent conversation.
     */
    protected function actionSendMessage(array $action, int $teamId): string
    {
        $contactId = $action['contact_id'] ?? null;
        $text = $action['message'] ?? null;

        if (! $contactId || ! $text) {
            return "Send message failed: missing contact_id or message.";
        }

        $conversation = Conversation::where('team_id', $teamId)
            ->where('contact_id', $contactId)
            ->orderByDesc('last_message_at')
            ->first();

        if (! $conversation) {
            return "No conversation found for contact #{$contactId}.";
        }

        return $this->sendMessageToConversation($conversation, $text);
    }

    /**
     * Send a message to multiple contacts matching criteria.
     */
    protected function actionSendBulkMessage(array $action, int $teamId): string
    {
        $text = $action['message'] ?? null;
        $minScore = $action['min_score'] ?? null;
        $status = $action['status'] ?? null;

        if (! $text) {
            return "Bulk message failed: missing message text.";
        }

        $query = Conversation::where('team_id', $teamId)
            ->where('status', '!=', 'archived')
            ->whereHas('contact');

        if ($minScore !== null) {
            $query->whereHas('contact', fn ($q) => $q->where('lead_score', '>=', $minScore));
        }

        if ($status) {
            $query->whereHas('contact', fn ($q) => $q->where('lead_status', $status));
        }

        // Get the most recent conversation per contact
        $conversations = $query->orderByDesc('last_message_at')->get()
            ->unique('contact_id');

        $sent = 0;
        $failed = 0;

        foreach ($conversations as $conversation) {
            try {
                $this->sendMessageToConversation($conversation, $text);
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        return "Sent message to {$sent} contacts." . ($failed > 0 ? " ({$failed} failed)" : '');
    }

    protected function sendMessageToConversation(Conversation $conversation, string $text): string
    {
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'sender_type' => 'ai',
            'content_type' => 'text',
            'content' => $text,
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => Str::limit($text, 100),
        ]);

        SendPlatformMessage::dispatch($message->id);

        $contactName = $conversation->contact?->name ?? 'Unknown';

        return "Sent to {$contactName}.";
    }

    protected function actionToggleAi(array $action, int $teamId, bool $pause): string
    {
        $contactId = $action['contact_id'] ?? null;

        $query = Conversation::where('team_id', $teamId);

        if ($contactId) {
            $query->where('contact_id', $contactId);
        }

        $updated = $query->update(['ai_paused' => $pause]);

        $state = $pause ? 'paused' : 'resumed';

        return "AI {$state} for {$updated} conversation(s).";
    }

    protected function actionSaveMemory(array $action, int $teamId): string
    {
        $content = trim($action['content'] ?? '');

        if (! $content) {
            return 'Save memory failed: no content provided.';
        }

        $team = Team::find($teamId);

        if (! $team) {
            return 'Save memory failed: team not found.';
        }

        $existing = $team->ai_memory ?? '';
        $separator = $existing ? "\n" : '';
        $team->update(['ai_memory' => $existing . $separator . $content]);

        return "Saved to memory.";
    }

    protected function buildAnalyticsContext(int $teamId): string
    {
        $today = now()->startOfDay();
        $weekStart = now()->startOfWeek();

        $conversationsQuery = Conversation::where('team_id', $teamId);
        $messagesQuery = Message::whereHas('conversation', fn ($q) => $q->where('team_id', $teamId));
        $contactsQuery = Contact::where('team_id', $teamId);

        $lines = [];
        $lines[] = '=== BUSINESS ANALYTICS DATA ===';
        $lines[] = 'Current date/time: ' . now()->format('Y-m-d H:i');

        // Conversations
        $lines[] = "\n--- Conversations ---";
        $lines[] = 'Total conversations: ' . (clone $conversationsQuery)->count();
        $lines[] = 'Today: ' . (clone $conversationsQuery)->where('created_at', '>=', $today)->count();
        $lines[] = 'This week: ' . (clone $conversationsQuery)->where('created_at', '>=', $weekStart)->count();

        // By status
        $statuses = (clone $conversationsQuery)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        foreach ($statuses as $status => $count) {
            $lines[] = ucfirst($status) . ': ' . $count;
        }

        // Messages
        $lines[] = "\n--- Messages ---";
        $lines[] = 'Total messages: ' . (clone $messagesQuery)->count();
        $lines[] = 'Today: ' . (clone $messagesQuery)->where('messages.created_at', '>=', $today)->count();
        $lines[] = 'This week: ' . (clone $messagesQuery)->where('messages.created_at', '>=', $weekStart)->count();

        // By platform
        $lines[] = "\n--- Messages by Platform ---";
        $platformCounts = Message::join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->where('conversations.team_id', $teamId)
            ->selectRaw('conversations.platform, count(*) as total')
            ->groupBy('conversations.platform')
            ->pluck('total', 'platform');
        foreach ($platformCounts as $platform => $count) {
            $lines[] = ucfirst($platform) . ': ' . $count;
        }

        // AI vs human responses
        $lines[] = "\n--- Response Types ---";
        $aiCount = Message::whereHas('conversation', fn ($q) => $q->where('team_id', $teamId))
            ->where('sender_type', 'ai')->count();
        $humanCount = Message::whereHas('conversation', fn ($q) => $q->where('team_id', $teamId))
            ->where('sender_type', 'user')->count();
        $lines[] = "AI responses: {$aiCount}";
        $lines[] = "Human responses: {$humanCount}";

        // Contacts — include IDs so the AI can reference them in actions
        $lines[] = "\n--- Contacts ---";
        $lines[] = 'Total contacts: ' . (clone $contactsQuery)->count();
        $lines[] = 'New this week: ' . (clone $contactsQuery)->where('created_at', '>=', $weekStart)->count();

        // All contacts with scores (for action targeting)
        $lines[] = "\n--- All Contacts (ID, Name, Score, Status) ---";
        $allContacts = Contact::where('team_id', $teamId)
            ->orderByDesc('lead_score')
            ->limit(50)
            ->get(['id', 'name', 'lead_score', 'lead_status']);
        foreach ($allContacts as $c) {
            $lines[] = "ID:{$c->id} | {$c->name} | score {$c->lead_score} ({$c->lead_status})";
        }

        // Recent escalated conversations
        $lines[] = "\n--- Recent Escalated/Open Conversations ---";
        $escalated = Conversation::where('team_id', $teamId)
            ->where('status', 'open')
            ->with('contact:id,name')
            ->orderByDesc('last_message_at')
            ->limit(5)
            ->get();
        foreach ($escalated as $conv) {
            $contactName = $conv->contact?->name ?? 'Unknown';
            $lines[] = "{$contactName} ({$conv->platform}) - last message: " . ($conv->last_message_at?->diffForHumans() ?? 'N/A');
        }

        return implode("\n", $lines);
    }

    public function render()
    {
        return view('livewire.ai-chat')
            ->layout('layouts.app', ['title' => 'AI Chat', 'fullWidth' => true]);
    }
}
