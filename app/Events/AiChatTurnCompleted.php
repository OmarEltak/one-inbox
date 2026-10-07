<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired by ProcessAiChatTurn when the NaraRouter call finishes and the
 * AiCommand row is updated. The Livewire AiChat component listens on the
 * team.{team_id} private channel and swaps the "pending …" placeholder for
 * the real response. If the user has navigated away, the completed row is
 * still in the DB — mount() will load it on their next visit.
 */
class AiChatTurnCompleted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $teamId,
        public int $userId,
        public int $commandId,
        public string $response,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("team.{$this->teamId}");
    }

    public function broadcastAs(): string
    {
        return 'AiChatTurnCompleted';
    }

    /**
     * Keep the payload compact — the full response can be ~5-10 KB and
     * Reverb has a per-message cap. The Livewire listener uses commandId
     * to look up the full row if it needs more than the response text.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'command_id' => $this->commandId,
            'user_id'    => $this->userId,
            'response'   => $this->response,
        ];
    }
}
