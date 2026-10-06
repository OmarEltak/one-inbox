<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Phase H — Agent audit service (spec §5.2).
 *
 * Computes per-agent performance aggregates for the Deep Analysis "agent_audit"
 * mode. Unlike the customer-themes path (one NaraRouter call per 100-conv
 * chunk), this is a pure SQL aggregation plus a single prose summary call,
 * so DispatchDeepAnalysisJob can skip the chunk loop entirely.
 *
 * Attribution rules:
 *   - Only `direction='outbound'` AND `sender_type != 'ai'` rows count as a
 *     human agent reply. AI rows are deliberately excluded (they're metered
 *     separately in `AiCommand` + the `ai_reply_outbound` ledger).
 *   - Agents are grouped by `handled_by_user_id`. Null = "Unattributed"
 *     (legacy rows before the column existed, or system-generated outbound
 *     messages). Reported as an own group rather than dropped so the operator
 *     sees the shape of their visibility gap.
 *
 * Response-time pairing:
 *   - For each outbound-by-agent row, find the IMMEDIATELY preceding inbound
 *     row on the same conversation within a 24h window and record the delta.
 *   - Pairs > 24h are discarded (they're almost always a conversation that
 *     went cold and came back — not a signal of agent speed).
 *
 * Conversion:
 *   - "Converted" = conversation.sales_stage === 'completed' (see
 *     Conversation::STAGE_COMPLETED). We attribute conversion to EVERY agent
 *     who touched that conversation — an agent doesn't own a sale, but the
 *     team does, and over 1000 convs the shared-credit noise is small.
 */
final class AgentAuditService
{
    /** Max seconds between an inbound and the agent's reply before we drop the pair. */
    public const RESPONSE_PAIR_MAX_SECONDS = 24 * 3600;

    /** Number of outbound snippets stored per agent (PII-sensitive — kept short). */
    public const EXAMPLES_PER_AGENT = 2;

    /** Max characters of each example snippet. */
    public const EXAMPLE_SNIPPET_CHARS = 100;

    /**
     * Per-agent aggregate stats for a cohort of conversations on $team.
     *
     * The cohort filter re-uses DeepAnalysisService's shape:
     *   - page_id      ?int
     *   - contact_ids  ?array<int>
     *   - days         ?int   (window on conversations.last_message_at)
     *   - limit        ?int   (hard cap on conversation rows)
     *
     * @param  array<string, mixed>  $cohortFilter
     * @return array<int, array{
     *   user_id: int|null,
     *   user_name: string,
     *   messages_sent: int,
     *   conversations_touched: int,
     *   avg_response_time_seconds: float|null,
     *   conversion_rate: float|null,
     *   examples: array<int, array{conversation_id: int, snippet: string}>,
     * }>
     */
    public function perAgentStats(Team $team, array $cohortFilter): array
    {
        $conversationIds = $this->cohortConversationIds($team, $cohortFilter);
        if ($conversationIds === []) {
            return [];
        }

        $completedConversationIds = Conversation::query()
            ->whereIn('id', $conversationIds)
            ->where('sales_stage', Conversation::STAGE_COMPLETED)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $completedIndex = array_flip($completedConversationIds);

        $outbound = Message::query()
            ->whereIn('conversation_id', $conversationIds)
            ->where('direction', 'outbound')
            ->where(function ($q) {
                $q->whereNull('sender_type')->orWhere('sender_type', '!=', 'ai');
            })
            ->orderBy('conversation_id')
            ->orderBy('created_at')
            ->get(['id', 'conversation_id', 'handled_by_user_id', 'content', 'created_at']);

        if ($outbound->isEmpty()) {
            return [];
        }

        $inboundByConv = $this->inboundTimestampsByConversation(
            $outbound->pluck('conversation_id')->unique()->map(fn ($id) => (int) $id)->all()
        );

        /** @var array<string, array{user_id: int|null, messages_sent: int, conversations_touched: array<int, true>, response_deltas: array<int, int>, conversions: array<int, true>, examples: array<int, array{conversation_id: int, snippet: string}>}> $groups */
        $groups = [];

        foreach ($outbound as $msg) {
            $userId = $msg->handled_by_user_id !== null ? (int) $msg->handled_by_user_id : null;
            $key    = $userId === null ? 'null' : (string) $userId;
            $convId = (int) $msg->conversation_id;

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'user_id'               => $userId,
                    'messages_sent'         => 0,
                    'conversations_touched' => [],
                    'response_deltas'       => [],
                    'conversions'           => [],
                    'examples'              => [],
                ];
            }

            $groups[$key]['messages_sent']++;
            $groups[$key]['conversations_touched'][$convId] = true;

            if (isset($completedIndex[$convId])) {
                $groups[$key]['conversions'][$convId] = true;
            }

            // Response-time pair: last inbound <= this outbound, within 24h.
            $delta = $this->pairResponseDelta($msg->created_at, $inboundByConv[$convId] ?? []);
            if ($delta !== null) {
                $groups[$key]['response_deltas'][] = $delta;
            }

            if (count($groups[$key]['examples']) < self::EXAMPLES_PER_AGENT && $msg->content !== null) {
                $snippet = Str::limit((string) $msg->content, self::EXAMPLE_SNIPPET_CHARS);
                $groups[$key]['examples'][] = [
                    'conversation_id' => $convId,
                    'snippet'         => $snippet,
                ];
            }
        }

        $userIds = array_values(array_filter(array_map(fn ($g) => $g['user_id'], $groups)));
        $userNames = $userIds === []
            ? collect()
            : User::query()->whereIn('id', $userIds)->pluck('name', 'id');

        $out = [];
        foreach ($groups as $group) {
            $touched = count($group['conversations_touched']);
            $deltas  = $group['response_deltas'];
            $avg     = $deltas === [] ? null : (float) (array_sum($deltas) / count($deltas));

            $conversions = count($group['conversions']);
            $conversion  = $touched === 0 ? null : (float) ($conversions / $touched);

            $uid  = $group['user_id'];
            $name = $uid === null
                ? 'Unattributed'
                : ($userNames[$uid] ?? ('User #' . $uid));

            $out[] = [
                'user_id'                   => $uid,
                'user_name'                 => (string) $name,
                'messages_sent'             => (int) $group['messages_sent'],
                'conversations_touched'     => $touched,
                'avg_response_time_seconds' => $avg,
                'conversion_rate'           => $conversion,
                'examples'                  => array_values($group['examples']),
            ];
        }

        // Deterministic order: highest-volume first, nulls last. Keeps NaraRouter
        // prompt stable across reruns so cache keys could be added later.
        usort($out, function ($a, $b) {
            if ($a['user_id'] === null && $b['user_id'] !== null) {
                return 1;
            }
            if ($b['user_id'] === null && $a['user_id'] !== null) {
                return -1;
            }
            return $b['messages_sent'] <=> $a['messages_sent'];
        });

        return $out;
    }

    /**
     * @param  array<string, mixed>  $cohortFilter
     * @return array<int, int>
     */
    protected function cohortConversationIds(Team $team, array $cohortFilter): array
    {
        $query = Conversation::query()->where('team_id', $team->id);

        if (! empty($cohortFilter['page_id'])) {
            $query->where('page_id', (int) $cohortFilter['page_id']);
        }

        if (! empty($cohortFilter['contact_ids']) && is_array($cohortFilter['contact_ids'])) {
            $query->whereIn('contact_id', array_map('intval', $cohortFilter['contact_ids']));
        }

        if (! empty($cohortFilter['days'])) {
            $query->where('last_message_at', '>=', now()->subDays((int) $cohortFilter['days']));
        }

        $query->orderByDesc('last_message_at');

        $limit = (int) ($cohortFilter['limit'] ?? 0);
        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Build a map conversation_id → sorted list of inbound-message timestamps
     * (as integer unix seconds) so pairResponseDelta can do an O(log n) lookup.
     *
     * @param  array<int, int>  $conversationIds
     * @return array<int, array<int, int>>
     */
    protected function inboundTimestampsByConversation(array $conversationIds): array
    {
        if ($conversationIds === []) {
            return [];
        }

        $rows = Message::query()
            ->whereIn('conversation_id', $conversationIds)
            ->where('direction', 'inbound')
            ->orderBy('conversation_id')
            ->orderBy('created_at')
            ->get(['conversation_id', 'created_at']);

        $out = [];
        foreach ($rows as $r) {
            $cid = (int) $r->conversation_id;
            $out[$cid][] = $r->created_at->timestamp;
        }

        return $out;
    }

    /**
     * Compute seconds between $outboundAt and the most recent $inboundTimestamps
     * entry at or before $outboundAt. Returns null when there is no such pair,
     * or when the pair is further apart than RESPONSE_PAIR_MAX_SECONDS.
     *
     * @param  array<int, int>  $inboundTimestamps  pre-sorted asc
     */
    protected function pairResponseDelta(\DateTimeInterface $outboundAt, array $inboundTimestamps): ?int
    {
        if ($inboundTimestamps === []) {
            return null;
        }

        $outTs = $outboundAt->getTimestamp();

        // Simple scan from the end — list is sorted ascending and most-recent
        // match is almost always the last few entries.
        $prior = null;
        foreach (array_reverse($inboundTimestamps) as $ts) {
            if ($ts <= $outTs) {
                $prior = $ts;
                break;
            }
        }

        if ($prior === null) {
            return null;
        }

        $delta = $outTs - $prior;
        if ($delta < 0 || $delta > self::RESPONSE_PAIR_MAX_SECONDS) {
            return null;
        }

        return $delta;
    }
}
