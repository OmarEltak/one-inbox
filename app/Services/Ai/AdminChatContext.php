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
     * The most recent customer conversations (all pages, or one page), newest
     * first, capped by a character budget so the prompt stays small.
     *
     * Chats imported when a Facebook page is connected only carry the
     * last-message preview (the sync stores no message rows), so those are
     * included from the preview instead of being skipped — otherwise a page
     * connected today looks like it has no customers at all.
     */
    public function customerDigest(int $teamId, int $maxConversations = 40, int $perConversation = 6, int $charBudget = 6000, ?int $pageId = null, bool $expanded = false, bool $slim = false): string
    {
        // 2026-10-14: slim mode for 'list the last N people who texted' style
        // questions — we only need breadth (name + first inbound line each),
        // not depth. Packs 100-200 contacts in the same budget expanded mode
        // only fits ~36 of. Short-circuits most of the format logic below.
        if ($slim) {
            return $this->slimContactList($teamId, $pageId, 200);
        }

        // Targeted-page expansion mode: the operator named a specific page, so
        // the chat context deserves a much larger window AND both sides of each
        // thread (agent + customer) so moderator-audit style questions work.
        // Phase C of the AI credit economy spec (§6).
        //
        // 2026-10-10: bumped maxConversations 150 → 300, perConversation 10 → 14,
        // charBudget 30k → 120k.
        // 2026-10-14: 120k turned out to be aspirational — nemotron-3-ultra and
        // laguna free models consistently TIMEOUT at 25s with that payload,
        // AND the queue worker was dying mid-processing without updating the
        // AiCommand row (prompts >100k spike PHP memory enough that systemd
        // kills on --max-time boundary). Capping at 60k gives ~40-60 fully-
        // expanded conversations which is plenty for an audit question and
        // actually completes within the model's throughput budget.
        if ($expanded) {
            $maxConversations = 150;
            $perConversation = 12;
            $charBudget = 60_000;
        }

        $pageName = $pageId ? \App\Models\Page::where('team_id', $teamId)->whereKey($pageId)->value('name') : null;
        $scope = $pageName ? " ON PAGE \"{$pageName}\"" : '';

        $base = Conversation::where('team_id', $teamId)
            ->when($pageId, fn ($q) => $q->where('page_id', $pageId))
            ->where('sales_stage', '!=', Conversation::STAGE_SPAM)
            ->where(fn ($q) => $q->whereHas('messages', fn ($m) => $m->where('direction', 'inbound'))
                ->orWhereNotNull('last_message_preview'));

        $total = (clone $base)->count();
        if ($total === 0) {
            return "=== CUSTOMER CONVERSATIONS{$scope} ===\nNo conversations are stored for this " . ($pageName ? 'page' : 'account')
                . ' yet. Say so plainly. Do NOT suggest exporting chats, Meta Business Suite or any other tool.';
        }

        $conversations = $base->with(['contact:id,name,lead_status', 'page:id,name'])
            ->orderByDesc('last_message_at')
            ->limit($maxConversations)
            ->get();

        $messageQuery = Message::whereIn('conversation_id', $conversations->pluck('id'))
            ->unless($expanded, fn ($q) => $q->where('direction', 'inbound'))
            ->whereNotNull('content')
            ->orderByRaw('COALESCE(platform_sent_at, created_at) DESC')
            ->limit($maxConversations * 20);

        // Expanded mode needs direction + sender_type so we can tag each line
        // (Customer / Agent / AI) when both sides are present.
        $byConversation = $expanded
            ? $messageQuery->get(['conversation_id', 'content', 'direction', 'sender_type'])->groupBy('conversation_id')
            : $messageQuery->get(['conversation_id', 'content'])->groupBy('conversation_id');

        $blocks = [];
        $used = 0;
        $previewOnly = 0;
        foreach ($conversations as $conversation) {
            $rows = ($byConversation[$conversation->id] ?? collect())
                ->reject(fn ($m) => MediaPlaceholders::isPlaceholder($m->content))
                ->take($perConversation)
                ->reverse()
                ->values();
            $raw = $rows->pluck('content');

            if ($expanded) {
                $lines = $rows->map(function ($m) {
                    $who = $m->direction === 'inbound'
                        ? 'Customer'
                        : ($m->sender_type === 'ai' ? 'AI' : 'Agent');

                    return '  • ' . $who . ': ' . $this->clip($m->content, 180);
                });
            } else {
                $lines = $raw->map(fn ($c) => '  • ' . $this->clip($c, 180));
            }

            if ($raw->isEmpty()) {
                $preview = (string) $conversation->last_message_preview;
                if (MediaPlaceholders::isPlaceholder($preview)) {
                    continue;
                }
                $raw = collect([$preview]);
                $lines = collect(['  • (last-message preview, sender unknown) ' . $this->clip($preview, 180)]);
                $previewOnly++;
            }

            $name = $conversation->contact?->name ?? 'Unknown';
            $block = "— {$name} (contact ID:{$conversation->contact_id}, {$conversation->platform}, page: "
                . ($conversation->page?->name ?? '?') . ', status: ' . ($conversation->contact?->lead_status ?? '?')
                . ', last message ' . ($conversation->last_message_at?->toDateString() ?? '?')
                . ', writes in ' . $this->language($raw->all()) . ")\n" . $lines->implode("\n");

            if ($used + strlen($block) > $charBudget) {
                break;
            }
            $blocks[] = $block;
            $used += strlen($block);
        }

        $sampled = count($blocks);

        // 2026-10-13: the preview-only count reflects threads whose message
        // backfill hasn't landed yet (the BackfillPageMessages job runs on a
        // 10s-chained batch of 40 — for a page with thousands of chats this
        // takes ~1 hour to complete; the first few batches land first and
        // the rest fill in as the queue progresses). This is NOT a Meta API
        // limit and NOT something the user needs to opt into — it's our own
        // background work. So we tell the AI to answer from what is here
        // without hedging the whole answer behind "sample is limited" and
        // without inventing a user-facing backfill offer.
        $note = $previewOnly > 0
            ? "Note: {$previewOnly} of these conversations are still being backfilled in the background (we store the last-message preview immediately on page connect, and pull the full 25-message history per thread over the next hour). Answer in depth from the threads that DO have full transcripts, and for the backfilling ones just rely on the preview line. Do NOT tell the user to export chats, use Meta Business Suite, or wait — the backfill is automatic and running right now.\n"
            : '';

        $header = $expanded
            ? "=== EXPANDED CUSTOMER CONVERSATIONS{$scope} (BOTH SIDES OF EACH THREAD) ({$sampled} most recent of {$total} conversations; inbound+outbound, oldest→newest per chat) ==="
            : "=== CUSTOMER CONVERSATIONS{$scope} ({$sampled} most recent of {$total} conversations; customer messages, oldest→newest per chat) ===";

        $guidance = $expanded
            ? 'Use this to audit both customer messages AND how our agents/AI replied. Quote it as evidence.'
            : 'Use this to answer what customers want, ask about, complain about, and object to. Quote it as evidence.';

        return $header . "\n" . $guidance . "\n" . $note . implode("\n", $blocks);
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

    /**
     * Deep-pull offer block appended to the AI system prompt. 2026-10-13:
     * when a page has significantly more unfetched conversations than are
     * currently indexed, we instruct the AI to offer a backfill via a
     * machine-readable token the Livewire layer parses out and renders as
     * a confirm button. Returns '' when no page qualifies.
     *
     * Qualification: at least 50 unfetched convos AND the user's turn mentions
     * analysis / depth (so a casual single-contact question doesn't trigger).
     *
     * Token format: `[PULL_OFFER:page_id:count]` on its own line. The chat
     * component strips the token, stores the structured offer on the AiCommand
     * row, and renders a Yes/No card beneath the bubble.
     */
    public function pullOfferHint(int $teamId, string $userText): string
    {
        if (! $this->looksLikeDepthRequest($userText)) {
            return '';
        }

        $pages = \App\Models\Page::where('team_id', $teamId)
            ->where('is_active', true)
            ->whereIn('platform', ['facebook', 'instagram'])
            ->get(['id', 'name']);

        if ($pages->isEmpty()) {
            return '';
        }

        $totals = Conversation::whereIn('page_id', $pages->pluck('id'))
            ->selectRaw('page_id, count(*) as c')
            ->groupBy('page_id')
            ->pluck('c', 'page_id');
        $fetched = Conversation::whereIn('page_id', $pages->pluck('id'))
            ->whereJsonContains('metadata->messages_fetched', true)
            ->selectRaw('page_id, count(*) as c')
            ->groupBy('page_id')
            ->pluck('c', 'page_id');

        $offers = [];
        foreach ($pages as $page) {
            $total   = (int) ($totals[$page->id] ?? 0);
            $done    = (int) ($fetched[$page->id] ?? 0);
            $missing = $total - $done;
            if ($missing < 50) {
                continue;
            }
            // Suggest a reasonable chunk — min(missing, 500) so a single
            // offer doesn't commit the user to a multi-hour pull.
            $suggest = min($missing, 500);
            $offers[] = "- {$page->name}: {$done} of {$total} indexed; can pull +{$suggest} more. If user wants this, emit on its own last line: [PULL_OFFER:{$page->id}:{$suggest}]";
        }

        if ($offers === []) {
            return '';
        }

        return "\n\n=== DEEP-PULL OPTIONS ===\n"
            . "The user asked for a deeper/wider analysis sample. These pages have unindexed conversations:\n"
            . implode("\n", $offers)
            . "\nONLY emit a [PULL_OFFER:...] token if the user explicitly wants more coverage than you currently have. Never emit one in a casual single-contact answer. One token max per reply.";
    }

    /**
     * Heuristic: does the user's turn ask for an analysis depth we should
     * surface a deep-pull offer for? Deliberately conservative — false
     * positives generate noise.
     */
    private function looksLikeDepthRequest(string $text): bool
    {
        $t = mb_strtolower($text);
        $en = ['analyze', 'analyse', 'audit', 'review all', 'last 100', 'last 500', 'last 1000', 'all chats', 'every chat', 'every customer', 'all customers', 'full history', 'all my chats', 'all conversations'];
        $ar = ['حلل', 'حلّل', 'تحليل', 'كل العملاء', 'جميع العملاء', 'كل المحادثات', 'جميع المحادثات', 'آخر 100', 'آخر 500', 'آخر 1000', 'التاريخ الكامل'];
        foreach (array_merge($en, $ar) as $needle) {
            if (str_contains($t, $needle)) {
                return true;
            }
        }
        return false;
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

        $messages = $conversation->messages()->orderByRaw('COALESCE(platform_sent_at, created_at) DESC')->limit(12)->get(['direction', 'sender_type', 'content', 'content_type', 'created_at'])->reverse();
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

    /**
     * Slim contact list digest — one line per contact (name + first inbound
     * message, clipped to ~180 chars). Used for 'list the last N people who
     * texted us' style questions where the expanded both-sides digest can
     * only fit ~36 contacts in the budget. 2026-10-14.
     */
    private function slimContactList(int $teamId, ?int $pageId, int $maxContacts): string
    {
        $pageName = $pageId ? \App\Models\Page::where('team_id', $teamId)->whereKey($pageId)->value('name') : null;
        $scope = $pageName ? " ON PAGE \"{$pageName}\"" : '';

        $base = Conversation::where('team_id', $teamId)
            ->when($pageId, fn ($q) => $q->where('page_id', $pageId))
            ->where('sales_stage', '!=', Conversation::STAGE_SPAM)
            ->where(fn ($q) => $q->whereHas('messages', fn ($m) => $m->where('direction', 'inbound'))
                ->orWhereNotNull('last_message_preview'));

        $total = (clone $base)->count();
        if ($total === 0) {
            return "=== CUSTOMER CONTACT LIST{$scope} ===\nNo contacts stored for this " . ($pageName ? 'page' : 'account') . ' yet.';
        }

        $conversations = $base->with(['contact:id,name', 'page:id,name'])
            ->orderByDesc('last_message_at')
            ->limit($maxContacts)
            ->get();

        $firstInbound = Message::whereIn('conversation_id', $conversations->pluck('id'))
            ->where('direction', 'inbound')
            ->whereNotNull('content')
            ->orderBy('conversation_id')
            ->orderByRaw('COALESCE(platform_sent_at, created_at) ASC')
            ->get(['conversation_id', 'content'])
            ->groupBy('conversation_id')
            ->map(fn ($msgs) => (string) $msgs->first()->content);

        $lines = [];
        $i = 0;
        foreach ($conversations as $conv) {
            $i++;
            $name = $conv->contact?->name ?? 'Unknown';
            $firstMsg = $firstInbound[$conv->id] ?? (string) $conv->last_message_preview;
            if (MediaPlaceholders::isPlaceholder($firstMsg)) {
                $firstMsg = '[media/image]';
            }
            $lines[] = sprintf('%d. %s (contact ID:%d) — %s', $i, $name, $conv->contact_id, $this->clip($firstMsg, 180));
        }

        $header = "=== CUSTOMER CONTACT LIST{$scope} (" . count($lines) . " most recent of {$total}, slim format: name + first inbound line) ===";
        $guidance = 'Use this to answer list/breadth questions like "last 100 people who texted us". Each line = one contact. Do NOT fabricate contacts not in this list; if the user asks for more than the list contains, say so.';

        return $header . "\n" . $guidance . "\n" . implode("\n", $lines);
    }

    /**
     * Detect "list me N people / contacts / customers" style asks so the
     * caller can switch customerDigest to slim mode. 2026-10-14. Matches
     * English and Arabic phrasing used by Omar's user base.
     */
    public function looksLikeContactListRequest(string $text): bool
    {
        $t = mb_strtolower($text);
        $enPatterns = [
            '/\blist\b.*\b(contacts?|people|customers?|clients?|users?|chats?|conversations?)\b/i',
            '/\b(show|give)\s+me\s+(the\s+)?(last|top|first|recent)\b.*\b(contacts?|people|customers?|clients?|chats?)\b/i',
            '/\bwho\s+(are|texted|wrote|messaged)\b.*\b(last|recent|first)\b/i',
            '/\b(names?|list)\b.*\b(last|top|first|recent)\s+\d+/i',
        ];
        $arPatterns = [
            '/قائم.*(العملاء|الأشخاص|الناس|الزبائن|المحادثات)/u',
            '/اعرض.*قائم/u',
            '/أعطني.*(العملاء|الأشخاص|قائم)/u',
            '/آخر\s*\d+.*(شخص|عميل|محادثة|رسالة)/u',
            '/أسماء.*(آخر|أول|أحدث)/u',
            '/من\s*(الذين|اللي)\s+(كلّم|كلم|راسل|كتب|بعث)/u',
        ];
        foreach (array_merge($enPatterns, $arPatterns) as $re) {
            if (preg_match($re, $t)) {
                return true;
            }
        }
        // Explicit "last/top/first N" with a reasonably large N (>=20).
        if (preg_match('/\b(last|top|first|آخر|أول)\s+(\d+)/u', $t, $m) && (int) $m[2] >= 20) {
            return true;
        }
        return false;
    }
}
