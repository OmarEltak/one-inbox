<?php

declare(strict_types=1);

namespace App\Services\Skills;

use App\Models\AiConfig;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;

/**
 * SKILL 4 — Audit Last 24h
 *
 * Standard health report for an operator team. Combines ConversationAuditor
 * rule-check output with higher-level KPIs (conversion rate, vision health,
 * sheet-push rate, blocked confirmations, pages activity). One array per
 * run — easy to render as an email / dashboard.
 */
class Audit24h
{
    public function __construct(private readonly ConversationAuditor $auditor) {}

    public function run(int $teamId, int $hours = 24): array
    {
        $team = Team::findOrFail($teamId);
        $since = now()->subHours($hours);

        $convs = Conversation::whereHas('page', fn ($q) => $q->where('team_id', $teamId))
            ->where('last_message_at', '>=', $since)
            ->with(['contact', 'page'])
            ->get();

        $inbound = Message::whereHas('conversation.page', fn ($q) => $q->where('team_id', $teamId))
            ->where('direction', 'inbound')
            ->where('created_at', '>=', $since)
            ->count();

        $aiReplies = Message::whereHas('conversation.page', fn ($q) => $q->where('team_id', $teamId))
            ->where('sender_type', 'ai')
            ->where('created_at', '>=', $since)
            ->count();

        // Vision pipeline health
        $imgs = Message::whereHas('conversation.page', fn ($q) => $q->where('team_id', $teamId))
            ->where('content_type', 'image')
            ->where('direction', 'inbound')
            ->where('created_at', '>=', $since)
            ->with('mediaAsset')
            ->get();
        $visionOk = $imgs->filter(fn ($m) => ! empty($m->mediaAsset?->metadata['ai_description']))->count();
        $visionFail = $imgs->count() - $visionOk;

        $confirmed = $convs->filter(fn ($c) => ! empty($c->captured_data['confirmed_at']))->count();
        $blocked = $this->countBlockedConfirmations($teamId, $since);

        // Per-platform split
        $byPlatform = $convs->groupBy(fn ($c) => $c->page?->platform)
            ->map(fn ($g) => $g->count())
            ->all();

        $violations = $this->auditor->audit($teamId, $hours);
        $violationsByRule = $violations->groupBy('rule')->map->count()->all();

        return [
            'team'       => $team->name,
            'window_h'   => $hours,
            'since'      => $since->toDateTimeString(),
            'traffic'    => [
                'conversations' => $convs->count(),
                'inbound_msgs'  => $inbound,
                'ai_replies'    => $aiReplies,
                'by_platform'   => $byPlatform,
            ],
            'funnel' => [
                'confirmed_orders'       => $confirmed,
                'blocked_confirmations'  => $blocked,
                'conversion_pct'         => $convs->count() > 0
                    ? round($confirmed / $convs->count() * 100, 1)
                    : 0,
            ],
            'vision' => [
                'customer_images'      => $imgs->count(),
                'with_description'     => $visionOk,
                'missing_description'  => $visionFail,
                'success_pct'          => $imgs->count() > 0
                    ? round($visionOk / $imgs->count() * 100, 1)
                    : 100,
            ],
            'ai_rule_violations' => [
                'total'   => $violations->count(),
                'by_rule' => $violationsByRule,
                'top'     => $violations->sortBy('severity')->take(10)->values()->all(),
            ],
        ];
    }

    private function countBlockedConfirmations(int $teamId, \DateTimeInterface $since): int
    {
        $msgs = Message::whereHas('conversation.page', fn ($q) => $q->where('team_id', $teamId))
            ->where('sender_type', 'ai')
            ->where('content', 'like', '%سجلت الأوردر%')
            ->where('created_at', '>=', $since)
            ->get()
            ->groupBy('conversation_id');

        $blocked = 0;
        foreach ($msgs as $convId => $_) {
            $conv = Conversation::find($convId);
            if ($conv && empty($conv->captured_data['confirmed_at'])) $blocked++;
        }
        return $blocked;
    }
}
