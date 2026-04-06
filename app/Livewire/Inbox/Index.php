<?php

namespace App\Livewire\Inbox;

use App\Jobs\FetchOlderEmailsForPageJob;
use App\Jobs\SendAiResponse;
use App\Jobs\SendPlatformMessage;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\QuickReply;
use App\Services\Platforms\FacebookPlatform;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class Index extends Component
{
    use WithFileUploads;

    // Available labels with Flux-compatible colors
    public const LABELS = [
        'hot'       => 'red',
        'warm'      => 'orange',
        'cold'      => 'blue',
        'vip'       => 'yellow',
        'new'       => 'green',
        'follow-up' => 'purple',
        'spam'      => 'zinc',
        'escalated' => 'red',
    ];

    #[Url]
    public string $filter = 'all';

    #[Url]
    public string $search = '';

    #[Url]
    public ?int $pageId = null;

    public ?int $selectedConversationId = null;
    public string $messageText = '';

    #[Validate('nullable|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx')]
    public $attachment = null;

    public int $conversationLimit = 30;
    public bool $hasMoreConversations = false;

    public int $messageLimit = 30;
    public bool $hasOlderMessages = false;
    public bool $hasMoreImapEmails = false;

    // Email compose
    public bool $showCompose    = false;
    public string $composeTo      = '';
    public string $composeSubject = '';
    public string $composeBody    = '';

    // Cached in mount() to avoid recomputing on every render
    public $teamMembers;
    public $quickReplies;

    // Lazy-loaded score history for the selected contact
    public array $scoreHistory = [];
    public ?int $scoreHistoryContactId = null;

    public function mount(): void
    {
        $team = Auth::user()->currentTeam;

        $this->teamMembers = $team
            ? $team->members()->get()->push($team->owner)->unique('id')->sortBy('name')->values()
            : collect();

        $this->quickReplies = $team
            ? QuickReply::where('team_id', $team->id)->orderBy('title')->get()
            : collect();

        // If opening a specific page with no conversations yet, trigger a background sync
        if ($this->pageId && $team) {
            $hasConversations = Conversation::where('team_id', $team->id)
                ->where('page_id', $this->pageId)
                ->exists();

            if (! $hasConversations) {
                $page = \App\Models\Page::where('team_id', $team->id)
                    ->where('is_active', true)
                    ->find($this->pageId);

                if ($page) {
                    \App\Jobs\SyncPageConversations::dispatch($page);
                }
            }
        }
    }

    #[Computed]
    public function conversations()
    {
        $team = Auth::user()->currentTeam;

        if (! $team) {
            return collect();
        }

        $query = Conversation::with(['contact', 'page', 'assignedUser'])
            ->where('team_id', $team->id)
            ->where('status', '!=', 'archived')
            ->whereHas('page', fn ($q) => $q->where('is_active', true));

        if ($this->pageId) {
            $query->where('page_id', $this->pageId);
        }

        if ($this->filter === 'unread') {
            $query->where('unread_count', '>', 0);
        } elseif ($this->filter === 'mine') {
            $query->where('assigned_to', Auth::id());
        } elseif (in_array($this->filter, ['facebook', 'instagram', 'whatsapp', 'telegram'])) {
            $query->where('platform', $this->filter);
        } elseif (array_key_exists($this->filter, self::LABELS)) {
            $query->whereJsonContains('labels', $this->filter);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('last_message_preview', 'like', "%{$this->search}%")
                    ->orWhereHas('contact', function ($cq) {
                        $cq->where('name', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%")
                            ->orWhere('phone', 'like', "%{$this->search}%");
                    });
            });
        }

        $conversations = $query->orderByDesc('last_message_at')->limit($this->conversationLimit + 1)->get();

        $this->hasMoreConversations = $conversations->count() > $this->conversationLimit;

        // Check if the current email inbox has older emails not yet imported from IMAP
        $this->hasMoreImapEmails = false;
        if ($this->pageId) {
            $emailPage = \App\Models\Page::where('team_id', $team->id)->find($this->pageId);
            if ($emailPage && $emailPage->platform === 'email') {
                $meta = $emailPage->metadata ?? [];
                $this->hasMoreImapEmails = isset($meta['oldest_fetched_at'])
                    && ($meta['has_more_imap'] ?? false);
            }
        }

        return $conversations->take($this->conversationLimit);
    }

    #[Computed]
    public function unreadCount(): int
    {
        $team = Auth::user()->currentTeam;

        if (! $team) {
            return 0;
        }

        return Conversation::where('team_id', $team->id)
            ->where('unread_count', '>', 0)
            ->count();
    }

    #[Computed]
    public function selectedConversation(): ?Conversation
    {
        if (! $this->selectedConversationId) {
            return null;
        }

        $team = Auth::user()->currentTeam;
        if (! $team) {
            return null;
        }

        $conversation = Conversation::with(['contact', 'page', 'assignedUser'])
            ->where('team_id', $team->id)
            ->find($this->selectedConversationId);

        if (! $conversation) {
            return null;
        }

        // Load latest messages (last N), ordered for display
        $totalMessages = $conversation->messages()->count();
        $this->hasOlderMessages = $totalMessages > $this->messageLimit;

        // For email: also show "load older" when IMAP has un-imported messages
        if (! $this->hasOlderMessages && $conversation->platform === 'email') {
            $pageMeta = $conversation->page?->metadata ?? [];
            // Show button once oldest_fetched_at is set (initial fetch done) and has_more_imap isn't false
            $this->hasOlderMessages = isset($pageMeta['oldest_fetched_at'])
                && ($pageMeta['has_more_imap'] ?? true);
        }

        $messages = $conversation->messages()
            ->with('sentByUser')
            ->orderByRaw('COALESCE(platform_sent_at, created_at) DESC')
            ->limit($this->messageLimit)
            ->get()
            ->reverse()
            ->values();

        $conversation->setRelation('messages', $messages);

        return $conversation;
    }

    public function setContactLeadStatus(int $contactId, string $status): void
    {
        $team = Auth::user()->currentTeam;
        if (! $team) {
            return;
        }

        $contact = \App\Models\Contact::where('team_id', $team->id)->findOrFail($contactId);
        $contact->update(['lead_status' => $status]);
        unset($this->selectedConversation);
    }

    public function loadScoreHistory(int $contactId): void
    {
        if ($this->scoreHistoryContactId === $contactId) {
            return;
        }

        $team = Auth::user()->currentTeam;
        if (! $team || ! \App\Models\Contact::where('team_id', $team->id)->where('id', $contactId)->exists()) {
            return;
        }

        $this->scoreHistoryContactId = $contactId;
        $this->scoreHistory = \App\Models\LeadScoreEvent::where('contact_id', $contactId)
            ->latest()
            ->take(10)
            ->get()
            ->toArray();
    }

    public function selectConversation(int $id): void
    {
        $this->selectedConversationId = $id;
        $this->messageLimit           = 30;

        $teamId = Auth::user()->currentTeam?->id;
        $conversation = Conversation::with('page')->where('team_id', $teamId)->find($id);

        if (! $conversation) {
            return;
        }

        // Lazy-load messages from platform API if none exist and not yet fetched
        if ($conversation->messages()->count() === 0 && ! ($conversation->metadata['messages_fetched'] ?? false)) {
            $this->fetchMessagesFromPlatform($conversation);
        }

        if ($conversation->unread_count > 0) {
            $conversation->markAsRead();
        }

        $this->dispatch('conversation-selected');
    }

    #[On('refreshInbox')]
    public function refreshInbox(): void
    {
        unset($this->conversations);
    }

    public function loadMoreConversations(): void
    {
        $this->conversationLimit += 30;
    }

    public function loadOlderEmailsFromInbox(): void
    {
        if (! $this->pageId) {
            return;
        }

        $emailPage = \App\Models\Page::where('team_id', Auth::user()->currentTeam?->id)->find($this->pageId);
        if (! $emailPage || $emailPage->platform !== 'email') {
            return;
        }

        $meta = $emailPage->metadata ?? [];
        if (isset($meta['oldest_fetched_at']) && ($meta['has_more_imap'] ?? false)) {
            FetchOlderEmailsForPageJob::dispatch($emailPage->id);
        }
    }

    public function openCompose(): void
    {
        $this->composeTo      = '';
        $this->composeSubject = '';
        $this->composeBody    = '';
        $this->showCompose    = true;
    }

    public function sendCompose(): void
    {
        $this->validate([
            'composeTo'      => 'required|email',
            'composeSubject' => 'required|string|max:255',
            'composeBody'    => 'required|string',
        ]);

        $page = \App\Models\Page::where('team_id', Auth::user()->currentTeam?->id)->find($this->pageId);
        if (! $page || $page->platform !== 'email') {
            return;
        }

        $team = Auth::user()->currentTeam;

        $contact = \App\Models\Contact::firstOrCreate(
            ['team_id' => $team->id, 'email' => strtolower($this->composeTo)],
            ['name' => $this->composeTo]
        );

        // Find or create the conversation for this recipient (one thread per email address per inbox)
        $conversation = Conversation::firstOrCreate(
            ['page_id' => $page->id, 'platform_conversation_id' => strtolower($this->composeTo)],
            [
                'team_id'    => $team->id,
                'contact_id' => $contact->id,
                'platform'   => 'email',
                'status'     => 'open',
                'metadata'   => [
                    'subject'       => $this->composeSubject,
                    'contact_email' => strtolower($this->composeTo),
                ],
            ]
        );

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'sender_type'     => 'user',
            'sender_id'       => Auth::id(),
            'content_type'    => 'text',
            'content'         => $this->composeBody,
        ]);

        $conversation->update([
            'last_message_at'      => now(),
            'last_message_preview' => Str::limit($this->composeBody, 100),
            'status'               => 'open',
        ]);

        SendPlatformMessage::dispatch($message->id);

        $this->showCompose    = false;
        $this->composeTo      = '';
        $this->composeSubject = '';
        $this->composeBody    = '';

        unset($this->conversations);
        $this->selectedConversationId = $conversation->id;
    }

    public function loadOlderMessages(): void
    {
        $conversation = Conversation::with('page')->where('team_id', Auth::user()->currentTeam?->id)->find($this->selectedConversationId);

        if (! $conversation) {
            return;
        }

        $totalInDb = $conversation->messages()->count();

        // If DB still has more rows, just expand the window (instant)
        if ($totalInDb > $this->messageLimit) {
            $this->messageLimit += 30;
            return;
        }

        // DB exhausted — for email, dispatch a job to pull the next batch from IMAP
        if ($conversation->platform === 'email' && $conversation->page) {
            $meta = $conversation->page->metadata ?? [];

            if (isset($meta['oldest_fetched_at']) && ($meta['has_more_imap'] ?? true)) {
                FetchOlderEmailsForPageJob::dispatch($conversation->page->id);
            }
        }
    }

    public function setFilter(string $filter): void
    {
        $this->filter                   = $filter;
        $this->selectedConversationId   = null;
        $this->conversationLimit        = 30;
    }

    public function setPage(?int $pageId): void
    {
        $this->pageId = $pageId;
        $this->selectedConversationId = null;
    }

    public function assignConversation(int $conversationId, ?int $userId): void
    {
        $team = Auth::user()->currentTeam;

        $conversation = Conversation::where('team_id', $team->id)->find($conversationId);

        if (! $conversation) {
            return;
        }

        $conversation->update(['assigned_to' => $userId]);
        unset($this->selectedConversation, $this->conversations);
    }

    public function toggleLabel(int $conversationId, string $label): void
    {
        if (! array_key_exists($label, self::LABELS)) {
            return;
        }

        $team = Auth::user()->currentTeam;
        $conversation = Conversation::where('team_id', $team->id)->find($conversationId);

        if (! $conversation) {
            return;
        }

        $labels = $conversation->labels ?? [];

        if (in_array($label, $labels)) {
            $labels = array_values(array_filter($labels, fn ($l) => $l !== $label));
        } else {
            $labels[] = $label;
        }

        $conversation->update(['labels' => $labels]);
        unset($this->selectedConversation, $this->conversations);
    }

    private function fetchMessagesFromPlatform(Conversation $conversation): void
    {
        if (! $conversation->page) {
            return;
        }

        try {
            if (in_array($conversation->platform, ['facebook', 'instagram'])) {
                $platform = new FacebookPlatform;
                $platform->fetchAndStoreMessages($conversation);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to fetch messages from platform', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function removeAttachment(): void
    {
        $this->attachment = null;
    }

    public function sendMessage(): void
    {
        $text = trim($this->messageText);
        $hasAttachment = $this->attachment !== null;

        if ((! $text && ! $hasAttachment) || ! $this->selectedConversationId) {
            return;
        }

        $conversation = Conversation::with('page')->where('team_id', Auth::user()->currentTeam?->id)->find($this->selectedConversationId);

        if (! $conversation || ! $conversation->page) {
            return;
        }

        $mediaUrl = null;
        $mediaType = null;
        $contentType = 'text';

        if ($hasAttachment) {
            $this->validate();
            $teamId = $conversation->team_id;
            $path = $this->attachment->store("chat-media/{$teamId}", 'public');
            $mediaUrl = asset('storage/' . $path);
            $mime = $this->attachment->getMimeType();
            $mediaType = $mime;
            $contentType = str_starts_with($mime, 'image/') ? 'image' : 'file';
        }

        // Store message locally
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'sender_type'     => 'user',
            'sender_id'       => Auth::id(),
            'content_type'    => $contentType,
            'content'         => $text ?: null,
            'media_url'       => $mediaUrl,
            'media_type'      => $mediaType,
        ]);

        $preview = $text ?: ($contentType === 'image' ? '[Image]' : '[File]');
        $conversation->update([
            'last_message_at'      => now(),
            'last_message_preview' => Str::limit($preview, 100),
        ]);

        // Auto-pause AI when a human agent replies
        if (! $conversation->ai_paused) {
            $conversation->pauseAi();
        }

        // Dispatch job to send through the platform API
        SendPlatformMessage::dispatch($message->id);

        $this->dispatch('message-sent');
        $this->messageText = '';
        $this->attachment = null;
    }

    public function toggleAiPause(int $id): void
    {
        if (! Auth::user()->isHeadAdmin() && ! Auth::user()->hasPermission('ai-control')) {
            return;
        }

        $conversation = Conversation::with('page')->where('team_id', Auth::user()->currentTeam?->id)->find($id);

        if (! $conversation) {
            return;
        }

        $wasPaused = $conversation->ai_paused;
        $conversation->update(['ai_paused' => ! $wasPaused]);

        // When resuming AI, dispatch a response for the last inbound message
        if ($wasPaused) {
            $lastInbound = $conversation->messages()
                ->where('direction', 'inbound')
                ->latest()
                ->first();

            if ($lastInbound) {
                $delay = $conversation->page?->aiConfig?->getRandomDelay() ?? 5;
                SendAiResponse::dispatch($conversation->id, $lastInbound->id)
                    ->delay(now()->addSeconds($delay));
            }
        }
    }

    public function render()
    {
        return view('livewire.inbox.index')
            ->layout('layouts.app', ['title' => 'Inbox', 'fullWidth' => true]);
    }
}
