<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $teamId,
        public int $conversationId,
        public int $messageId,
        public string $contactName,
        public string $platform,
        public ?string $preview,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("team.{$this->teamId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.received';
    }

    public static function fromMessage(Message $message, Conversation $conversation): self
    {
        return new self(
            teamId: $conversation->team_id,
            conversationId: $conversation->id,
            messageId: $message->id,
            contactName: $conversation->contact?->name ?? 'Unknown',
            platform: $conversation->platform,
            preview: $message->content ? \Illuminate\Support\Str::limit($message->content, 100) : '[Media]',
        );
    }
}
