<?php

declare(strict_types=1);

namespace App\Livewire\Comments;

use App\Models\Comment;
use App\Models\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Public-comments visibility screen.
 *
 * Lists every Comment the AI has engaged with (replied, DM'd, filtered,
 * or errored) across all of this team's active FB/IG pages. This is the
 * ONLY surface operators have into the AI's public voice — so failed
 * public replies (graph_error set, graph_reply_id null) MUST be rendered
 * as the error state they are, not hidden. Per Phase-0 audit ~70-80%
 * of prod rows are currently in this state (pages_read_user_content
 * FLfB config issue) and the view is designed to make that visible.
 *
 * Team scoping: `page_id IN (team's active page ids)`. Cross-tenant
 * isolation is a tested invariant — see tests/Feature/Comments/.
 */
class Index extends Component
{
    use WithPagination;

    /** Filter chip values accepted. */
    public const CHIP_ALL              = 'all';
    public const CHIP_PUBLIC_SUCCESS   = 'public_reply_success';
    public const CHIP_PUBLIC_FAILED    = 'public_reply_failed';
    public const CHIP_DM_SENT          = 'dm_sent';
    public const CHIP_DM_ONLY          = 'dm_only';
    public const CHIP_FILTERED         = 'filtered';

    public const CHIPS = [
        self::CHIP_ALL,
        self::CHIP_PUBLIC_SUCCESS,
        self::CHIP_PUBLIC_FAILED,
        self::CHIP_DM_SENT,
        self::CHIP_DM_ONLY,
        self::CHIP_FILTERED,
    ];

    #[Url(as: 'chip')]
    public string $chip = self::CHIP_ALL;

    #[Url(as: 'page')]
    public string $pageFilter = 'all';

    #[Url(as: 'decision')]
    public string $decisionFilter = 'all';

    public function updatingChip(): void
    {
        $this->resetPage();
    }

    public function updatingPageFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDecisionFilter(): void
    {
        $this->resetPage();
    }

    public function setChip(string $chip): void
    {
        if (! in_array($chip, self::CHIPS, true)) {
            return;
        }
        $this->chip = $chip;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->chip           = self::CHIP_ALL;
        $this->pageFilter     = 'all';
        $this->decisionFilter = 'all';
        $this->resetPage();
    }

    /**
     * IDs of the current team's active FB/IG pages. Comments only exist
     * on FB/IG — but we don't hard-code the platform filter here; we
     * just scope by the pages the team owns. If a non-FB/IG page ever
     * carries a Comment row, it'd belong to this team and that's fine.
     */
    protected function activePageIds(): Collection
    {
        $team = Auth::user()?->currentTeam;
        if (! $team) {
            return collect();
        }

        return Page::query()
            ->where('team_id', $team->id)
            ->where('is_active', true)
            ->pluck('id');
    }

    #[Computed]
    public function teamPages(): Collection
    {
        $team = Auth::user()?->currentTeam;
        if (! $team) {
            return collect();
        }

        return Page::query()
            ->where('team_id', $team->id)
            ->where('is_active', true)
            ->orderBy('platform')
            ->orderBy('name')
            ->get(['id', 'name', 'platform', 'avatar']);
    }

    /**
     * The distinct `decision` values that appear in this team's comment
     * history. Drives the decision dropdown — we only show operators the
     * decisions that are actually relevant to them, not every enum value.
     */
    #[Computed]
    public function availableDecisions(): array
    {
        $ids = $this->activePageIds();
        if ($ids->isEmpty()) {
            return [];
        }

        return Comment::query()
            ->whereIn('page_id', $ids)
            ->whereNotNull('decision')
            ->distinct()
            ->orderBy('decision')
            ->pluck('decision')
            ->all();
    }

    /**
     * Translate stored `decision_reason` values into user-friendly text.
     * Internal keys (set by jobs) map to localized strings. Legacy / free-form
     * reasons fall through as-is so filter_* reasons remain readable.
     */
    public function prettyDecisionReason(?string $raw): string
    {
        if ($raw === null || $raw === '') {
            return '';
        }

        // Includes legacy raw strings written by jobs before 2026-10-22 so
        // existing DB rows render as translated text rather than raw jargon.
        return match ($raw) {
            'ai_provider_empty_response',
            'Nara returned empty string'                 => __('The AI provider returned an empty response.'),
            'ai_classifier_empty_response',
            'classifier returned empty (Nara failure)'   => __('The AI classifier returned an empty response.'),
            default                                      => $raw,
        };
    }

    #[Computed]
    public function comments(): LengthAwarePaginator
    {
        $pageIds = $this->activePageIds();

        if ($pageIds->isEmpty()) {
            // Return an empty paginator (same shape downstream code expects)
            // for teams with no active connections.
            return new \Illuminate\Pagination\LengthAwarePaginator(
                items: collect(),
                total: 0,
                perPage: 25,
                currentPage: 1,
            );
        }

        $query = Comment::query()
            ->with([
                'page:id,name,platform,avatar',
                'pagesPost:id,platform_post_id,page_id',
            ])
            ->whereIn('page_id', $pageIds);

        // Per-page filter — guarded so a stale URL from another team's
        // page_id can't leak rows.
        if ($this->pageFilter !== 'all' && ctype_digit($this->pageFilter)) {
            $pid = (int) $this->pageFilter;
            if ($pageIds->contains($pid)) {
                $query->where('page_id', $pid);
            }
        }

        if ($this->decisionFilter !== 'all' && $this->decisionFilter !== '') {
            $query->where('decision', $this->decisionFilter);
        }

        $query = $this->applyChip($query);

        return $query
            ->orderByDesc('received_at')
            ->paginate(25);
    }

    /**
     * Translate a chip into predicates. Composed as a sub-method so each
     * chip's semantics live in one place (the view only has the labels).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyChip($query)
    {
        return match ($this->chip) {
            self::CHIP_PUBLIC_SUCCESS => $query->whereNotNull('graph_reply_id'),
            self::CHIP_PUBLIC_FAILED  => $query
                ->whereNotNull('graph_error')
                ->whereNull('graph_reply_id'),
            self::CHIP_DM_SENT        => $query->whereNotNull('dm_sent_at'),
            self::CHIP_DM_ONLY        => $query
                ->where('decision', Comment::DECISION_DM_ONLY),
            self::CHIP_FILTERED       => $query->where('decision', 'like', 'filtered_%'),
            default                   => $query,
        };
    }

    public function render()
    {
        return view('livewire.comments.index')
            ->layout('layouts.app', ['title' => __('Comments')]);
    }
}
