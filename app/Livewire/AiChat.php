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
use App\Services\Ai\AdminChatContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class AiChat extends Component
{
    use WithFileUploads;

    /** Platforms where Meta only accepts outbound within 24h of the contact's last message. */
    protected const META_WINDOW_PLATFORMS = ['facebook', 'instagram'];

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

    /**
     * One-tap questions that use what the assistant can actually see (chat
     * content, reach, lead status) — real marketing work, not generic chips.
     *
     * @return array<int, array{label: string, prompt: string}>
     */
    public function suggestions(): array
    {
        return [
            ['label' => __('What customers want'), 'prompt' => 'Read my customer chats from the last 30 days. What do customers ask for most, and which products or services are most requested? Give counts and real quotes with names.'],
            ['label' => __('Top problems & objections'), 'prompt' => 'What are the biggest complaints, problems and objections in my chats? For each: how often it comes up, a real quote, and the exact reply I should use.'],
            ['label' => __('Who is ready to buy'), 'prompt' => 'Which contacts are closest to buying right now? List the top 5 with the reason (quote their message) and the exact message I should send each, in their language.'],
            ['label' => __('Win back quiet leads'), 'prompt' => 'Find interested leads who went quiet in the last 2 weeks. Write a short win-back message for them in the language and dialect they use, and tell me who can be reached on which channel.'],
            ['label' => __('Why deals are lost'), 'prompt' => 'Look at conversations that did not convert. Why did customers drop off? Give the top reasons with real examples and what to change in my offer or AI replies.'],
            ['label' => __('This week vs last week'), 'prompt' => 'How did this week go compared to last week (conversations, messages, AI vs human replies, new contacts)? Give 3 concrete actions to improve next week.'],
        ];
    }

    public function useSuggestion(int $index): void
    {
        $suggestion = $this->suggestions()[$index] ?? null;
        if (! $suggestion) {
            return;
        }

        $this->message = $suggestion['prompt'];
        $this->sendMessage();
    }

    /**
     * Operators think in names: drop "(ID: 11)", ", ID: 11", "contact ID:123"
     * the model copies from its context into prose. Action JSON is already
     * stripped by now, so this never touches what gets executed. A bare
     * "order ID: 5531" in a customer quote is left alone.
     */
    protected static function stripInternalIds(string $text): string
    {
        $text = preg_replace('/\s*\(\s*(?:(?:page|contact|campaign)[\s_]*)?id\s*[:#=]?\s*\d+\s*\)/iu', '', $text) ?? $text;
        $text = preg_replace('/,\s*(?:(?:page|contact|campaign)[\s_]*)?id\s*[:#=]\s*\d+/iu', '', $text) ?? $text;
        $text = preg_replace('/\b(?:page|contact|campaign)[\s_]*id\s*[:#=]?\s*\d+/iu', '', $text) ?? $text;

        return trim(preg_replace('/[ \t]{2,}/', ' ', $text) ?? $text);
    }

    protected static function isConfirmation(string $text): bool
    {
        return (bool) preg_match(
            '/^(send|send it|yes|yep|ok|okay|confirm|go|go ahead|do it|sure|ابعت|ابعتها|ابعته|ارسل|أرسل|ارسلها|تمام|نعم|اه|آه|ايوه|أيوه|موافق|يلا)[\s.!]*$/iu',
            trim($text)
        );
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

        // Typing "send" / "yes" / "ابعت" with an action waiting confirms it,
        // instead of going back to the AI (which used to ask for an ID again).
        if ($this->pendingAction && ! $hasAttachment && self::isConfirmation($text)) {
            $this->message = '';
            $this->messages[] = ['role' => 'user', 'content' => $text];
            $this->confirmAction();

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

        // Chat content: contacts named in this turn or the last few (so "send
        // him…" resolves), plus a digest of recent customer messages so insight
        // questions are answered from real quotes.
        $chatContext = app(AdminChatContext::class);
        $recent = collect($this->messages)->slice(-6, 5);
        $recentTalk = $recent->pluck('content')->implode("\n");
        $recentUserTalk = $recent->where('role', 'user')->pluck('content')->implode("\n");

        // A named page ("last 30 customers on Mishkah") gets that page's own
        // chats; otherwise the digest spans every page.
        $pageIds = $chatContext->mentionedPageIds($team->id, $text, $recentUserTalk);
        $digest = $pageIds
            ? collect($pageIds)->map(fn ($id) => $chatContext->customerDigest(
                $team->id, charBudget: intdiv(14000, count($pageIds)), pageId: $id,
            ))->implode("\n\n")
            : $chatContext->customerDigest($team->id);

        $analyticsContext = $this->buildAnalyticsContext($team->id)
            . "\n\n" . $chatContext->mentionedContacts($team->id, $text, $recentTalk)
            . "\n\n" . $digest;

        // Last 12 turns, each clipped: the page loads 60 past messages and the
        // whole thread used to be re-sent every time, growing until the model
        // rejected the request ("AI service is temporarily unavailable").
        $history = collect($this->messages)
            ->filter(fn ($m) => $m['role'] === 'user' || $m['role'] === 'assistant')
            ->slice(-12)
            ->map(fn ($m) => [
                'role' => $m['role'] === 'user' ? 'user' : 'model',
                'content' => Str::limit((string) $m['content'], 2000),
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

        $response = self::stripInternalIds($response);

        // A reply that was only an action block used to render as an empty bubble.
        if (trim($response) === '') {
            $response = $this->pendingAction
                ? 'Ready — review the action below and confirm.'
                : 'I could not put an answer together for that. Try rephrasing, or ask about a specific contact or campaign.';
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

        // Persist the real outcome ("queued to 2, skipped 96…") on the turn
        // that proposed it, so a reload shows what happened instead of only
        // the AI's pre-confirmation draft.
        $lastCommand = AiCommand::where('team_id', $team->id)->where('user_id', Auth::id())->latest('id')->first();
        $lastCommand?->update(['response' => trim($lastCommand->response . "\n\nDone: {$result}")]);

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

    /** Operators name people, not IDs: accept a unique contact_name too. */
    protected function contactIdFromName(array $action, int $teamId): ?int
    {
        $name = trim((string) ($action['contact_name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $ids = Contact::where('team_id', $teamId)->where('name', 'like', '%' . addcslashes($name, '%_') . '%')->limit(2)->pluck('id');

        return $ids->count() === 1 ? $ids->first() : null;
    }

    protected function describeSendMessage(array $action, int $teamId): string
    {
        $contactId = $action['contact_id'] ?? $this->contactIdFromName($action, $teamId);
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

        ['eligible' => $eligible, 'stale' => $stale] = $this->resolveBulkTargets($action, $teamId);

        $pageName = $pageId ? Page::where('team_id', $teamId)->find($pageId)?->name : null;

        $filter = $pageName ? "page: {$pageName}" : 'all pages';
        if ($minScore !== null) {
            $filter .= ", score ≥ {$minScore}";
        } elseif ($status) {
            $filter .= ", status: {$status}";
        }

        if ($scheduledAtRaw) {
            try {
                $when = \Carbon\Carbon::parse($scheduledAtRaw)->format('M j, Y g:ia');
            } catch (\Throwable $e) {
                $when = $scheduledAtRaw; // the executor surfaces the parse error on confirm
            }

            // The Meta window is re-checked at the scheduled time, so quote the
            // full audience and say that the filter happens then.
            $total = $eligible->count() + $stale;
            $sentence = "Schedule bulk message to up to {$total} contacts ({$filter}) [{$when}]: \"{$text}\"";
            if ($stale > 0 || $eligible->contains(fn ($c) => in_array($c->platform, self::META_WINDOW_PLATFORMS, true))) {
                $sentence .= '  ⚠ Messenger/Instagram contacts who have not messaged within 24h of the send time will be skipped (Meta rule).';
            }

            return $sentence;
        }

        $sentence = "Send bulk message to {$eligible->count()} contacts ({$filter}) [now]: \"{$text}\"";
        if ($stale > 0) {
            $sentence .= "  ⚠ {$stale} more on Messenger/Instagram will NOT receive it — they have not messaged within the last 24 hours and Meta blocks sends outside that window.";
        }

        return $sentence;
    }

    /**
     * Who a send_bulk_message would actually reach. Shared by the confirmation
     * summary and the executor so the number the operator approves is the
     * number that gets sent. Messenger/Instagram conversations whose last
     * inbound is older than 24h are split out: Meta rejects those sends with
     * error 2018278 ("outside the allowed time frame"), and counting them as
     * recipients is how "sent to 98" turned into 2 delivered.
     *
     * @return array{eligible: \Illuminate\Support\Collection<int, Conversation>, stale: int}
     */
    protected function resolveBulkTargets(array $action, int $teamId): array
    {
        $query = Conversation::where('team_id', $teamId)
            ->where('status', '!=', 'archived')
            ->whereHas('contact');

        if (($action['page_id'] ?? null) !== null) {
            $query->where('page_id', $action['page_id']);
        }

        if (($action['min_score'] ?? null) !== null) {
            $query->whereHas('contact', fn ($q) => $q->where('lead_score', '>=', $action['min_score']));
        }

        if (! empty($action['status'])) {
            $query->whereHas('contact', fn ($q) => $q->where('lead_status', $action['status']));
        }

        // Most recent conversation per contact.
        $conversations = $query->orderByDesc('last_message_at')->get()->unique('contact_id')->values();

        $metaIds = $conversations->whereIn('platform', self::META_WINDOW_PLATFORMS)->pluck('id');
        $lastInbound = $metaIds->isEmpty() ? collect() : Message::whereIn('conversation_id', $metaIds)
            ->where('direction', 'inbound')
            ->selectRaw('conversation_id, MAX(COALESCE(platform_sent_at, created_at)) as last_at')
            ->groupBy('conversation_id')
            ->pluck('last_at', 'conversation_id');

        // WhatsApp (Wuzapi session), Telegram and email have no server-side window.
        $cutoff = now()->subHours(24);
        [$eligible, $stale] = $conversations->partition(function (Conversation $c) use ($lastInbound, $cutoff) {
            if (! in_array($c->platform, self::META_WINDOW_PLATFORMS, true)) {
                return true;
            }
            $at = $lastInbound[$c->id] ?? null;

            return $at && \Carbon\Carbon::parse($at)->gt($cutoff);
        });

        return ['eligible' => $eligible->values(), 'stale' => $stale->count()];
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
        $contactId = $action['contact_id'] ?? $this->contactIdFromName($action, $teamId);
        $text = $action['message'] ?? null;

        if (! $contactId || ! $text) {
            return 'Send message failed: could not tell which contact to message — mention them by their full name and try again.';
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

        ['eligible' => $eligible, 'stale' => $skippedStale] = $this->resolveBulkTargets($action, $teamId);

        if ($eligible->isEmpty() && $skippedStale > 0) {
            return "Nothing sent: all {$skippedStale} matching contacts are on Messenger/Instagram and none has messaged within the last 24 hours. Meta blocks sends outside that window (error 2018278) — reach them on WhatsApp / Telegram / email instead, or wait until they message the Page.";
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

        // Pages (for page_id targeting in bulk messages). Reach is spelled out
        // per page so the AI quotes what a broadcast will really hit: on
        // Messenger/Instagram only contacts who messaged within 24h can be
        // reached, and quoting the full audience is how "sent to 98" happened.
        $lines[] = "\n--- Connected Pages (ID, Name, Platform, Audience) ---";
        $pages = Page::where('team_id', $teamId)->get(['id', 'name', 'platform']);
        $audience = Conversation::where('team_id', $teamId)
            ->where('status', '!=', 'archived')
            ->selectRaw('page_id, count(distinct contact_id) as total')
            ->groupBy('page_id')
            ->pluck('total', 'page_id');
        $windowStart = now()->subHours(24);
        $reachable = Conversation::where('team_id', $teamId)
            ->where('status', '!=', 'archived')
            ->whereIn('platform', self::META_WINDOW_PLATFORMS)
            ->whereHas('messages', fn ($q) => $q->where('direction', 'inbound')
                ->whereRaw('COALESCE(platform_sent_at, created_at) >= ?', [$windowStart]))
            ->selectRaw('page_id, count(distinct contact_id) as total')
            ->groupBy('page_id')
            ->pluck('total', 'page_id');
        foreach ($pages as $page) {
            $total = (int) ($audience[$page->id] ?? 0);
            if (in_array($page->platform, self::META_WINDOW_PLATFORMS, true)) {
                $now = (int) ($reachable[$page->id] ?? 0);
                $reach = "{$total} contacts, only {$now} reachable now (messaged within 24h); "
                    . ($total - $now) . ' outside Meta\'s 24h window and CANNOT be messaged';
            } else {
                $reach = "{$total} contacts, all reachable (no messaging window)";
            }
            $lines[] = "ID:{$page->id} | {$page->name} | {$page->platform} | {$reach}";
        }

        // Campaigns
        $lines[] = "\n--- Campaigns (ID, Name, Type, Status, Sent/Total, Replies) ---";
        $campaigns = Campaign::where('team_id', $teamId)
            ->orderByDesc('created_at')
            ->limit(20)
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
