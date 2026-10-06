<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\DeepAnalysis;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired by DispatchDeepAnalysisJob when a Deep Analysis run finishes
 * (spec §5.1 step 6). AiChat listens on PrivateChannel("team.{teamId}")
 * and appends a "ready" message to the chat so the operator knows the
 * stored result is available for follow-up questions.
 *
 * Payload intentionally minimal — the AiChat prompt builder reads the
 * actual result_json on the next turn via BuildsConversationPrompts.
 */
class DeepAnalysisCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $teamId,
        public int $deepAnalysisId,
        public string $mode,
        public int $cohortSize,
        public string $completedAt,
    ) {
    }

    public static function fromModel(DeepAnalysis $analysis): self
    {
        return new self(
            teamId: (int) $analysis->team_id,
            deepAnalysisId: (int) $analysis->id,
            mode: (string) $analysis->mode,
            cohortSize: (int) $analysis->cohort_size,
            completedAt: ($analysis->completed_at ?? now())->toIso8601String(),
        );
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("team.{$this->teamId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'DeepAnalysisCompleted';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'deep_analysis_id' => $this->deepAnalysisId,
            'mode'             => $this->mode,
            'cohort_size'      => $this->cohortSize,
            'at'               => $this->completedAt,
        ];
    }
}
