<?php

namespace App\Livewire\Inbox;

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
            ->where('status', '!=', 'archived');

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

        $conversation = Conversation::with(['contact', 'page', 'assignedUser'])->find($this->selectedConversationId);

        if (! $conversation) {
            return null;
        }

        // Load latest messages (last N), ordered for display
        $totalMessages = $conversation->messages()->count();
        $this->hasOlderMessages = $totalMessages > $this->messageLimit;

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

    public function loadScoreHistory(int $contactId): void
    {
        if ($this->scoreHistoryContactId === $contactId) {
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
        $this->messageLimit = 30;

        $conversation = Conversation::with('page')->find($id);

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

    public function loadOlderMessages(): void
    {
        $this->messageLimit += 30;
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->selectedConversationId = null;
        $this->conversationLimit = 30;
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

        $conversation = Conversation::with('page')->find($this->selectedConversationId);

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

        $conversation = Conversation::with('page')->find($id);

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
