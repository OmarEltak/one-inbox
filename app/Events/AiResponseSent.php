<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiResponseSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $teamId,
        public int $conversationId,
        public int $messageId,
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
        return 'ai.response';
    }

    public static function fromMessage(Message $message, Conversation $conversation): self
    {
        return new self(
            teamId: $conversation->team_id,
            conversationId: $conversation->id,
            messageId: $message->id,
            preview: $message->content ? \Illuminate\Support\Str::limit($message->content, 100) : null,
        );
    }
}
