<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Page;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Floating, dismissible nudge shown on every authenticated page when one or
 * more of this team's active FB/IG pages has been detected as disconnected
 * by PageHealthCheckCommand (metadata.subscription_error).
 *
 * The /connections screen already surfaces the per-page reconnect banner —
 * this component exists because users spend most of their time in /inbox or
 * /comments, not /connections, so a page can be dead for days before anyone
 * notices.
 *
 * Dismissal is per-page and persisted in Page.metadata.reconnect_notice_dismissed_at.
 * The nudge re-appears automatically if PageHealthCheckCommand later detects a
 * NEW failure (first_seen_at > dismissed_at) — so dismissing one incident does
 * not silence future ones.
 */
class DisconnectedPagesNotice extends Component
{
    /**
     * Pages the current team owns that have an active subscription_error AND
     * have not been dismissed for THIS incident. Returns the shape the view
     * needs — not the raw Page models.
     *
     * @return array<int, array{page_id:int,name:string,platform:string,message:string,first_seen_at:string}>
     */
    #[Computed]
    public function brokenPages(): array
    {
        $team = Auth::user()?->currentTeam;
        if (! $team) {
            return [];
        }

        return Page::query()
            ->where('team_id', $team->id)
            ->where('is_active', true)
            ->whereIn('platform', ['facebook', 'instagram'])
            ->whereNotNull('metadata->subscription_error')
            ->get(['id', 'name', 'platform', 'metadata'])
            ->map(fn (Page $p) => $this->shape($p))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Flatten a page's subscription_error (which may be a legacy string OR the
     * modern array shape written by PageHealthCheckCommand) into a stable row,
     * and drop it if the user has already dismissed THIS incident.
     *
     * @return array{page_id:int,name:string,platform:string,message:string,first_seen_at:string}|null
     */
    protected function shape(Page $page): ?array
    {
        $raw = data_get($page->metadata, 'subscription_error');
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_array($raw)) {
            $message      = (string) ($raw['message'] ?? __('Reconnection needed.'));
            $firstSeenAt  = (string) ($raw['first_seen_at'] ?? now()->toIso8601String());
        } else {
            // Legacy string shapes written by FacebookPlatform.
            $legacy = [
                'subscribe_failed' => __('Meta refused the message subscription for this page. Reconnect to restore DM delivery.'),
                'twofa_required'   => __('Two-factor authentication is blocking this connection. Reconnect to restore DM delivery.'),
                'refresh_failed'   => __('Could not refresh this account\'s token on Meta. Reconnect to restore access.'),
            ];
            $message     = $legacy[(string) $raw] ?? __('Reconnection needed.');
            $firstSeenAt = now()->toIso8601String();
        }

        $dismissedAt = data_get($page->metadata, 'reconnect_notice_dismissed_at');
        if ($dismissedAt && $this->dismissalCoversIncident((string) $dismissedAt, $firstSeenAt)) {
            return null;
        }

        return [
            'page_id'       => (int) $page->id,
            'name'          => (string) $page->name,
            'platform'      => (string) $page->platform,
            'message'       => $message,
            'first_seen_at' => $firstSeenAt,
        ];
    }

    /**
     * A dismissal silences an incident only if the dismissal happened AT or
     * AFTER the incident was first detected. A later-detected re-break (new
     * first_seen_at) must re-surface the nudge — otherwise one click would
     * permanently mute all future failures for the page.
     */
    protected function dismissalCoversIncident(string $dismissedAt, string $firstSeenAt): bool
    {
        try {
            return CarbonImmutable::parse($dismissedAt)
                ->greaterThanOrEqualTo(CarbonImmutable::parse($firstSeenAt));
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Dismiss the nudge for a specific page. Guarded by team scope so a URL
     * crafted with another team's page_id can't flip someone else's state.
     */
    public function acknowledge(int $pageId): void
    {
        $team = Auth::user()?->currentTeam;
        if (! $team) {
            return;
        }

        $page = Page::where('team_id', $team->id)->find($pageId);
        if (! $page) {
            return;
        }

        $page->forceFill([
            'metadata' => array_merge($page->metadata ?? [], [
                'reconnect_notice_dismissed_at' => now()->toIso8601String(),
            ]),
        ])->saveQuietly();

        unset($this->brokenPages);
    }

    public function render()
    {
        return view('livewire.disconnected-pages-notice');
    }
}
