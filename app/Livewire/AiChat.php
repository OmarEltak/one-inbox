<?php

namespace App\Livewire;

use App\Jobs\SendPlatformMessage;
use App\Models\AiCommand;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;
use App\Models\Team;
use App\Contracts\AiProviderInterface;
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

    public ?array $pendingAction = null;

    public string $pendingActionSummary = '';

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
            $provider = app(AiProviderInterface::class);
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

    public function confirmAction(): void
    {
        if (! $this->pendingAction) {
            return;
        }

        $team = Auth::user()->currentTeam;

        if (! $team) {
            return;
        }

        try {
            $result = $this->runAction($this->pendingAction, $team->id);
        } catch (\Throwable $e) {
            Log::error('AI Chat confirmed action failed', ['error' => $e->getMessage(), 'action' => $this->pendingAction]);
            $result = "Action failed: {$e->getMessage()}";
        }

        $this->pendingAction = null;
        $this->pendingActionSummary = '';

        $this->messages[] = ['role' => 'assistant', 'content' => "Done: {$result}"];
        $this->dispatch('message-sent');
    }

    public function cancelAction(): void
    {
        $this->pendingAction = null;
        $this->pendingActionSummary = '';

        $this->messages[] = ['role' => 'assistant', 'content' => 'Action cancelled.'];
        $this->dispatch('message-sent');
    }

    /**
     * Parse AI response for action blocks and execute them.
     *
     * pending_action blocks: require user confirmation before executing.
     * action blocks: auto-execute immediately (save_memory only).
     */
    protected function executeActions(string &$response, int $teamId): ?string
    {
        $results = [];

        // Handle pending_action blocks — store for confirmation, do not execute yet
        if (preg_match('/```pending_action\s*(\{.+?\})\s*```/s', $response, $match)) {
            try {
                $action = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
                $this->pendingAction = $action;
                $this->pendingActionSummary = $this->describePendingAction($action, $teamId);
            } catch (\JsonException $e) {
                $results[] = 'Failed to parse pending action: invalid JSON.';
            }

            $response = trim(preg_replace('/```pending_action\s*\{.+?\}\s*```/s', '', $response));
        }

        // Handle action blocks — auto-execute (save_memory only)
        if (preg_match_all('/```action\s*(\{.+?\})\s*```/s', $response, $matches)) {
            foreach ($matches[1] as $jsonStr) {
                try {
                    $action = json_decode($jsonStr, true, 512, JSON_THROW_ON_ERROR);

                    if (($action['action'] ?? null) === 'save_memory') {
                        $results[] = $this->runAction($action, $teamId);
                    }
                } catch (\JsonException $e) {
                    $results[] = 'Failed to parse action: invalid JSON.';
                } catch (\Throwable $e) {
                    Log::error('AI Chat action failed', ['error' => $e->getMessage(), 'action' => $jsonStr]);
                    $results[] = "Action failed: {$e->getMessage()}";
                }
            }

            $response = trim(preg_replace('/```action\s*\{.+?\}\s*```/s', '', $response));
        }

        return $results ? implode("\n", $results) : null;
    }

    protected function describePendingAction(array $action, int $teamId): string
    {
        return match ($action['action'] ?? '') {
            'send_message' => $this->describeSendMessage($action, $teamId),
            'send_bulk_message' => $this->describeBulkMessage($action, $teamId),
            'pause_ai' => $this->describeAiToggle($action, $teamId, 'pause'),
            'resume_ai' => $this->describeAiToggle($action, $teamId, 'resume'),
            'pause_campaign' => $this->describeCampaignToggle($action, $teamId, 'pause'),
            'resume_campaign' => $this->describeCampaignToggle($action, $teamId, 'resume'),
            default => 'Execute: ' . json_encode($action),
        };
    }

    protected function describeSendMessage(array $action, int $teamId): string
    {
        $contactId = $action['contact_id'] ?? null;
        $text = $action['message'] ?? '';
        $name = 'Unknown contact';

        if ($contactId) {
            $contact = Contact::where('team_id', $teamId)->find($contactId);
            $name = $contact?->name ?? "Contact #{$contactId}";
        }

        return "Send message to {$name}: \"{$text}\"";
    }

    protected function describeBulkMessage(array $action, int $teamId): string
    {
        $text = $action['message'] ?? '';
        $minScore = $action['min_score'] ?? null;
        $status = $action['status'] ?? null;
        $pageId = $action['page_id'] ?? null;
        $scheduledAtRaw = $action['scheduled_at'] ?? null;

        $query = Conversation::where('team_id', $teamId)
            ->where('status', '!=', 'archived')
            ->whereHas('contact');

        if ($pageId !== null) {
            $query->where('page_id', $pageId);
        }

        if ($minScore !== null) {
            $query->whereHas('contact', fn ($q) => $q->where('lead_score', '>=', $minScore));
        }

        if ($status) {
            $query->whereHas('contact', fn ($q) => $q->where('lead_status', $status));
        }

        $count = $query->distinct('contact_id')->count('contact_id');

        // Lookup page + platform so we can warn about the Meta 24h rule inline,
        // before the operator confirms. If the page is on facebook or instagram
        // and the operator hasn't been told about the window filter yet, showing
        // it here beats surfacing it only after the send attempt.
        $page = null;
        $pageName = null;
        if ($pageId) {
            $page = Page::where('team_id', $teamId)->find($pageId);
            $pageName = $page?->name;
        }

        $filter = $pageName ? "page: {$pageName}" : 'all pages';
        if ($minScore !== null) {
            $filter .= ", score ≥ {$minScore}";
        } elseif ($status) {
            $filter .= ", status: {$status}";
        }

        // Base sentence with scheduling annotation.
        $verb = 'Send bulk message';
        $when = 'now';
        if ($scheduledAtRaw) {
            try {
                $scheduledAt = \Carbon\Carbon::parse($scheduledAtRaw);
                $verb = 'Schedule bulk message';
                $when = $scheduledAt->format('M j, Y g:ia');
            } catch (\Throwable $e) {
                // Fall back to unscheduled sentence; the executor will surface a
                // proper error message during confirm.
            }
        }

        $sentence = "{$verb} to ~{$count} contacts ({$filter}) [{$when}]: \"{$text}\"";

        // Meta 24-hour window warning: show inline for Facebook / Instagram so
        // the operator sees the caveat BEFORE approving. Non-Meta platforms have
        // no window, so no warning needed.
        if ($page && in_array($page->platform, ['facebook', 'instagram'], true)) {
            $sentence .= "  ⚠ Meta only allows sending to contacts who replied within the last 24 hours on {$page->platform} — stale contacts will be automatically skipped when this fires.";
        }

        return $sentence;
    }

    protected function describeAiToggle(array $action, int $teamId, string $mode): string
    {
        $contactId = $action['contact_id'] ?? null;

        if ($contactId) {
            $contact = Contact::where('team_id', $teamId)->find($contactId);
            $name = $contact?->name ?? "Contact #{$contactId}";

            return ucfirst($mode) . " AI responses for {$name}";
        }

        return ucfirst($mode) . ' AI responses for all conversations';
    }

    protected function describeCampaignToggle(array $action, int $teamId, string $mode): string
    {
        $campaignId = $action['campaign_id'] ?? null;

        if ($campaignId) {
            $campaign = Campaign::where('team_id', $teamId)->find($campaignId);
            $name = $campaign?->name ?? "Campaign #{$campaignId}";

            return ucfirst($mode) . " campaign: {$name}";
        }

        return ucfirst($mode) . ' campaign (unknown ID)';
    }

    protected function runAction(array $action, int $teamId): string
    {
        $type = $action['action'] ?? null;

        return match ($type) {
            'send_message' => $this->actionSendMessage($action, $teamId),
            'send_bulk_message' => $this->actionSendBulkMessage($action, $teamId),
            'pause_ai' => $this->actionToggleAi($action, $teamId, true),
            'resume_ai' => $this->actionToggleAi($action, $teamId, false),
            'pause_campaign' => $this->actionToggleCampaign($action, $teamId, 'paused'),
            'resume_campaign' => $this->actionToggleCampaign($action, $teamId, 'active'),
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
        $scheduledAtRaw = $action['scheduled_at'] ?? null;

        if (! $text) {
            return "Bulk message failed: missing message text.";
        }

        $pageId = $action['page_id'] ?? null;

        // Scheduling path: if the AI provided a scheduled_at ISO datetime, create
        // a Campaign row in status='scheduled' rather than dispatching now. The
        // scheduler command (campaigns:dispatch-scheduled) flips it to active at
        // the scheduled time and ProcessCampaign handles the send loop with the
        // same Meta 24h filter applied at dispatch time.
        if ($scheduledAtRaw) {
            try {
                $scheduledAt = \Carbon\Carbon::parse($scheduledAtRaw);
            } catch (\Throwable $e) {
                return "Bulk message failed: could not parse scheduled_at (expected ISO datetime like 2026-09-01T14:30:00Z). Got: {$scheduledAtRaw}";
            }

            if ($scheduledAt->lt(now()->addMinute())) {
                return "Bulk message failed: scheduled_at must be at least 1 minute in the future.";
            }
            if ($scheduledAt->gt(now()->addDays(30))) {
                return "Bulk message failed: scheduled_at must be within the next 30 days.";
            }
            if (! $pageId) {
                return "Bulk message failed: scheduled bulk sends require a page_id (which page to send from).";
            }

            $page = Page::where('team_id', $teamId)->where('is_active', true)->find($pageId);
            if (! $page) {
                return "Bulk message failed: page_id {$pageId} not found or inactive on this team.";
            }

            $criteria = [
                'page_id'       => $pageId,
                'delay_seconds' => 5,
            ];
            if ($status) {
                $criteria['lead_status'] = $status;
            }
            if (in_array($page->platform, ['facebook', 'instagram'], true)) {
                $criteria['meta_24h_filter'] = true;
            }

            $campaign = \App\Models\Campaign::create([
                'team_id'          => $teamId,
                'created_by'       => \Illuminate\Support\Facades\Auth::id(),
                'name'             => 'AI Chat scheduled — ' . now()->format('M j, Y g:ia'),
                'type'             => 'promotion',
                'platform'         => $page->platform,
                'message_template' => $text,
                'target_criteria'  => $criteria,
                'status'           => 'scheduled',
                'scheduled_at'     => $scheduledAt,
            ]);

            $when = $scheduledAt->format('M j, Y g:ia');
            $windowNote = in_array($page->platform, ['facebook', 'instagram'], true)
                ? " On Messenger/Instagram, Meta will only accept sends to contacts who have replied to this Page within 24 hours OF THE SCHEDULED TIME — stale contacts are filtered out then, not now."
                : '';
            return "Campaign scheduled: '{$campaign->name}' will send at {$when} on {$page->name} ({$page->platform}).{$windowNote}";
        }

        $query = Conversation::where('team_id', $teamId)
            ->where('status', '!=', 'archived')
            ->whereHas('contact');

        if ($pageId !== null) {
            $query->where('page_id', $pageId);
        }

        if ($minScore !== null) {
            $query->whereHas('contact', fn ($q) => $q->where('lead_score', '>=', $minScore));
        }

        if ($status) {
            $query->whereHas('contact', fn ($q) => $q->where('lead_status', $status));
        }

        // Get the most recent conversation per contact
        $conversations = $query->orderByDesc('last_message_at')->get()
            ->unique('contact_id');

        // Pre-filter: on Facebook Messenger and Instagram Direct, Meta rejects
        // any outbound sent to a contact whose last inbound is > 24h ago with
        // error code 10 / subcode 2018278 ("outside the allowed time frame").
        // The HUMAN_AGENT tag fallback in SendPlatformMessage requires prior
        // Meta App Review approval, which the current app does NOT have, so
        // both the standard send AND the fallback fail on stale contacts.
        // Skipping them here means the AI report reflects reality instead of
        // "sent to 98" when Meta silently rejected 96 downstream.
        //
        // WhatsApp (Wuzapi / QR gateway) uses a real WhatsApp Web session and
        // does NOT enforce a 24h server-side window, so we do not filter it.
        // Telegram and email have no window at all. Only Meta platforms get
        // this pre-check.
        $metaPlatforms = ['facebook', 'instagram'];
        $now = now();
        $eligible = [];
        $skippedStale = 0;

        foreach ($conversations as $conversation) {
            if (! in_array($conversation->platform, $metaPlatforms, true)) {
                $eligible[] = $conversation;
                continue;
            }

            $lastInboundAt = Message::where('conversation_id', $conversation->id)
                ->where('direction', 'inbound')
                ->latest('id')
                ->value('platform_sent_at')
                ?? Message::where('conversation_id', $conversation->id)
                    ->where('direction', 'inbound')
                    ->latest('id')
                    ->value('created_at');

            if (! $lastInboundAt || \Carbon\Carbon::parse($lastInboundAt)->diffInHours($now) >= 24) {
                $skippedStale++;
                continue;
            }

            $eligible[] = $conversation;
        }

        $sent = 0;
        $failed = 0;

        foreach ($eligible as $conversation) {
            try {
                $this->sendMessageToConversation($conversation, $text);
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        $parts = ["Queued message to {$sent} contacts."];
        if ($skippedStale > 0) {
            $parts[] = "Skipped {$skippedStale} on Messenger/Instagram because Meta will not accept messages to contacts who have not replied within the last 24 hours — this is Meta's rule, not ours, and sends outside it come back with error 2018278 ('outside the allowed time frame'). WhatsApp / Telegram / email do not have this limit; broadcasting to those platforms reaches everyone.";
        }
        if ($failed > 0) {
            $parts[] = "{$failed} failed to queue.";
        }
        $parts[] = "Note: 'queued' means the send job was dispatched to our queue. Actual delivery is confirmed on the message row's platform_message_id — check the inbox for green checkmarks.";

        return implode(' ', $parts);
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

    protected function actionToggleCampaign(array $action, int $teamId, string $status): string
    {
        $campaignId = $action['campaign_id'] ?? null;

        if (! $campaignId) {
            return 'Campaign action failed: missing campaign_id.';
        }

        $campaign = Campaign::where('team_id', $teamId)->find($campaignId);

        if (! $campaign) {
            return "Campaign #{$campaignId} not found.";
        }

        $campaign->update(['status' => $status]);

        $label = $status === 'paused' ? 'paused' : 'resumed';

        return "Campaign '{$campaign->name}' {$label}.";
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

        // Pages (for page_id targeting in bulk messages)
        $lines[] = "\n--- Connected Pages (ID, Name, Platform) ---";
        $pages = Page::where('team_id', $teamId)->get(['id', 'name', 'platform']);
        foreach ($pages as $page) {
            $lines[] = "ID:{$page->id} | {$page->name} | {$page->platform}";
        }

        // Campaigns
        $lines[] = "\n--- Campaigns (ID, Name, Type, Status, Sent/Total, Replies) ---";
        $campaigns = Campaign::where('team_id', $teamId)
            ->orderByDesc('created_at')
            ->get();
        $lines[] = 'Total campaigns: ' . $campaigns->count();
        foreach ($campaigns as $campaign) {
            $replyRate = $campaign->sent_count > 0
                ? round(($campaign->reply_count / $campaign->sent_count) * 100) . '%'
                : '0%';
            $lines[] = "ID:{$campaign->id} | {$campaign->name} | {$campaign->type} | status:{$campaign->status}"
                . " | sent:{$campaign->sent_count}/{$campaign->total_contacts} | replies:{$campaign->reply_count} ({$replyRate})"
                . ($campaign->scheduled_at ? " | scheduled:{$campaign->scheduled_at->format('Y-m-d H:i')}" : '');
        }

        return implode("\n", $lines);
    }

    public function render()
    {
        return view('livewire.ai-chat')
            ->layout('layouts.app', ['title' => 'AI Chat', 'fullWidth' => true]);
    }
}
