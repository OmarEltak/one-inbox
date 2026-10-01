<?php

namespace App\Services\Ai;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Chat content for the admin /ai-chat assistant. Before this, the assistant
 * only saw counts and names: it told operators it "cannot read the chat",
 * asked them for a contact ID to message "Wagdy", and could not answer
 * "what do most of our customers want" with real examples.
 *
 *  - mentionedContacts(): contacts the operator names (or referred to a turn
 *    ago, for "send him…") with ID, reachability, language and transcript.
 *  - customerDigest(): recent customer messages across all chats, so insight
 *    questions are answered from real quotes instead of guesses.
 */
class AdminChatContext
{
    /** Name tokens too generic to identify a contact on their own. */
    private const STOPWORDS = [
        'the', 'and', 'for', 'you', 'him', 'her', 'them', 'our', 'all', 'send', 'chat', 'chats', 'message',
        'messages', 'whatsapp', 'facebook', 'instagram', 'telegram', 'email', 'customer', 'customers', 'contact',
        'shop', 'store', 'team', 'page', 'admin', 'user', 'new', 'test', 'mr', 'mrs', 'unknown',
    ];

    private const META_WINDOW_PLATFORMS = ['facebook', 'instagram'];

    /**
     * @param  string  $text  The operator's current message (strongest signal).
     * @param  string  $recent  The last few turns, so "send him" still resolves.
     */
    public function mentionedContacts(int $teamId, string $text, string $recent = '', int $withTranscript = 3): string
    {
        $current = $this->normalize($text);
        $earlier = $this->normalize($recent);
        if ($current === '' && $earlier === '') {
            return '';
        }

        $matches = [];
        Contact::where('team_id', $teamId)
            ->whereNotNull('name')
            ->select(['id', 'name', 'lead_status', 'lead_score'])
            ->orderBy('id')
            ->chunk(2000, function ($contacts) use (&$matches, $current, $earlier) {
                foreach ($contacts as $contact) {
                    $score = $this->matchScore($contact->name, $current) * 3 + $this->matchScore($contact->name, $earlier);
                    if ($score > 0) {
                        $matches[] = [$score, $contact];
                    }
                }
            });

        if ($matches === []) {
            return '';
        }

        usort($matches, fn ($a, $b) => $b[0] <=> $a[0] ?: $b[1]->lead_score <=> $a[1]->lead_score);
        $contacts = collect($matches)->pluck(1);

        $lines = ['=== MENTIONED CONTACTS (resolved from names in the chat — use these IDs, never ask the operator for one) ==='];
        foreach ($contacts->take($withTranscript) as $contact) {
            $lines[] = $this->contactBlock($contact);
        }

        $others = $contacts->slice($withTranscript)->take(8);
        if ($others->isNotEmpty()) {
            $lines[] = 'Other contacts with a matching name (ask which one if the operator was ambiguous): '
                . $others->map(fn ($c) => "{$c->name} (ID:{$c->id})")->implode('; ');
        }

        return implode("\n", $lines);
    }

    /**
     * Recent customer messages across every chat, newest conversations first,
     * capped by a character budget so the prompt stays small.
     */
    public function customerDigest(int $teamId, int $days = 30, int $maxConversations = 40, int $perConversation = 6, int $charBudget = 12000, ?int $pageId = null): string
    {
        $since = now()->subDays($days);
        $pageName = $pageId ? \App\Models\Page::where('team_id', $teamId)->whereKey($pageId)->value('name') : null;
        $scope = $pageName ? " ON PAGE \"{$pageName}\"" : '';

        $base = Conversation::where('team_id', $teamId)
            ->when($pageId, fn ($q) => $q->where('page_id', $pageId))
            ->where('sales_stage', '!=', Conversation::STAGE_SPAM)
            ->where('last_message_at', '>=', $since)
            ->whereHas('messages', fn ($q) => $q->where('direction', 'inbound'));

        $activeTotal = (clone $base)->count();
        if ($activeTotal === 0) {
            return "=== CUSTOMER CONVERSATIONS{$scope} (last {$days} days) ===\nNo customer messages in this period.";
        }

        $conversations = $base->with(['contact:id,name,lead_status', 'page:id,name'])
            ->orderByDesc('last_message_at')
            ->limit($maxConversations)
            ->get();

        $byConversation = Message::whereIn('conversation_id', $conversations->pluck('id'))
            ->where('direction', 'inbound')
            ->where('created_at', '>=', $since)
            ->whereNotNull('content')
            ->orderByDesc('id')
            ->limit($maxConversations * 20)
            ->get(['conversation_id', 'content'])
            ->groupBy('conversation_id');

        $blocks = [];
        $used = 0;
        foreach ($conversations as $conversation) {
            $texts = ($byConversation[$conversation->id] ?? collect())
                ->pluck('content')
                ->reject(fn ($c) => MediaPlaceholders::isPlaceholder($c))
                ->take($perConversation)
                ->reverse()
                ->map(fn ($c) => '  • ' . $this->clip($c, 180));

            if ($texts->isEmpty()) {
                continue;
            }

            $name = $conversation->contact?->name ?? 'Unknown';
            $block = "— {$name} (contact ID:{$conversation->contact_id}, {$conversation->platform}, page: "
                . ($conversation->page?->name ?? '?') . ', status: ' . ($conversation->contact?->lead_status ?? '?')
                . ', writes in ' . $this->language($texts->all()) . ")\n" . $texts->implode("\n");

            if ($used + strlen($block) > $charBudget) {
                break;
            }
            $blocks[] = $block;
            $used += strlen($block);
        }

        $sampled = count($blocks);

        return "=== CUSTOMER CONVERSATIONS{$scope} (last {$days} days — {$sampled} most recent of {$activeTotal} active conversations; customer messages only, oldest→newest) ===\n"
            . 'Use this to answer what customers want, ask about, complain about, and object to. Quote it as evidence.' . "\n"
            . implode("\n", $blocks);
    }

    /**
     * Pages the operator named ("customers for mishkah", typo "brandak" →
     * Brandk). The current message wins; earlier operator turns are only
     * used when it names none (follow-ups like "and their complaints?").
     *
     * @return array<int, int>
     */
    public function mentionedPageIds(int $teamId, string $text, string $recentUserText = ''): array
    {
        $pages = \App\Models\Page::where('team_id', $teamId)->where('is_active', true)->get(['id', 'name']);

        foreach ([$this->normalize($text), $this->normalize($recentUserText)] as $haystack) {
            $ids = $pages->filter(fn ($p) => $this->matchScore((string) $p->name, $haystack) > 0)->pluck('id')->take(3)->all();
            if ($ids !== []) {
                return $ids;
            }
        }

        return [];
    }

    private function contactBlock(Contact $contact): string
    {
        $conversation = Conversation::where('contact_id', $contact->id)
            ->with('page:id,name')
            ->orderByDesc('last_message_at')
            ->first();

        $head = "— {$contact->name} | contact ID:{$contact->id} | status: " . ($contact->lead_status ?? '?')
            . ' | score: ' . ($contact->lead_score ?? 0);

        if (! $conversation) {
            return $head . "\n  (no conversation yet — cannot be messaged from here)";
        }
        $head .= " | {$conversation->platform}";

        $messages = $conversation->messages()->orderByDesc('id')->limit(15)->get(['direction', 'sender_type', 'content', 'content_type', 'created_at'])->reverse();
        $lastInbound = $messages->where('direction', 'inbound')->last()?->created_at;

        $reach = 'can be messaged';
        if (in_array($conversation->platform, self::META_WINDOW_PLATFORMS, true)
            && (! $lastInbound || $lastInbound->lt(now()->subHours(24)))) {
            $reach = "CANNOT be messaged now — on {$conversation->platform} and has not written in the last 24h (Meta rule); suggest another channel";
        }

        $inboundTexts = $messages->where('direction', 'inbound')->pluck('content')
            ->reject(fn ($c) => MediaPlaceholders::isPlaceholder($c))->all();

        $lines = [$head . ' | page: ' . ($conversation->page?->name ?? '?')
            . ' | last customer message: ' . ($lastInbound?->diffForHumans() ?? 'never')
            . " | {$reach} | writes in " . $this->language($inboundTexts)];
        $lines[] = '  Recent transcript (oldest→newest):';

        foreach ($messages as $m) {
            $who = $m->direction === 'inbound' ? 'Customer' : ($m->sender_type === 'ai' ? 'AI' : 'Agent');
            $body = MediaPlaceholders::isPlaceholder($m->content)
                ? '(sent ' . MediaPlaceholders::label($m->content_type, $m->content) . ')'
                : $this->clip($m->content, 250);
            $lines[] = "  {$who}: {$body}";
        }

        return implode("\n", $lines);
    }

    /** 2 = full name in text, 1 = a distinctive name token in text, 0 = no match. */
    private function matchScore(string $name, string $haystack): int
    {
        if ($haystack === '') {
            return 0;
        }

        $normalized = $this->normalize($name);
        if (mb_strlen($normalized) < 3) {
            return 0;
        }
        if (str_contains($normalized, ' ') && $this->containsWord($haystack, $normalized)) {
            return 2;
        }

        foreach (explode(' ', $normalized) as $token) {
            if (mb_strlen($token) >= 3 && ! in_array($token, self::STOPWORDS, true)
                && ($this->containsWord($haystack, $token) || $this->nearWord($haystack, $token))) {
                return 1;
            }
        }

        return 0;
    }

    /** One typo away ("brandak" → brandk, "wagdi" → wagdy), Latin names of 5+ letters only. */
    private function nearWord(string $haystack, string $token): bool
    {
        if (strlen($token) < 5 || ! preg_match('/^[a-z0-9]+$/', $token)) {
            return false;
        }

        foreach ($this->words[$haystack] ??= array_unique(explode(' ', $haystack)) as $word) {
            if (abs(strlen($word) - strlen($token)) <= 1 && preg_match('/^[a-z0-9]+$/', $word) && levenshtein($word, $token) <= 1) {
                return true;
            }
        }

        return false;
    }

    /** @var array<string, array<int, string>> haystack => words */
    private array $words = [];

    private function containsWord(string $haystack, string $needle): bool
    {
        return (bool) preg_match('/(?<![\p{L}\p{N}])' . preg_quote($needle, '/') . '(?![\p{L}\p{N}])/u', $haystack);
    }

    /** Lowercase letters/digits/spaces only — drops emoji like "Wagdy🍫". */
    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text) ?? '';

        return trim($text);
    }

    /** Script-level hint; the model infers the dialect from the quotes. */
    private function language(array $texts): string
    {
        $joined = implode(' ', $texts);
        $arabic = preg_match_all('/\p{Arabic}/u', $joined);
        $latin = preg_match_all('/[A-Za-z]/', $joined);

        if ($arabic === 0 && $latin === 0) {
            return 'unknown language';
        }
        if ($arabic >= $latin * 2) {
            return 'Arabic (match their dialect)';
        }
        if ($latin >= $arabic * 2) {
            return $arabic > 0 ? 'mostly English/Franco-Arabic' : 'English';
        }

        return 'mixed Arabic/English';
    }

    private function clip(?string $text, int $max): string
    {
        return Str::limit(trim(preg_replace('/\s+/u', ' ', (string) $text) ?? ''), $max);
    }
}
