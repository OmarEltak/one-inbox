<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\OnboardingNudge1MeetAi;
use App\Mail\OnboardingNudge2ConnectPage;
use App\Mail\OnboardingNudge3PersonalFromOmar;
use App\Mail\OnboardingNudge4EmailFounder;
use App\Models\Message;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Phase C — daily nudge sweep.
 *
 * Scheduled once per day from routes/console.php. Walks all teams whose
 * signups qualify for one of the four nudge stages and dispatches at most
 * ONE email per team per day, tracked by the nudge_N_sent_at columns for
 * idempotency (per acceptance criterion — dispatching twice sends once).
 *
 * Kill switches:
 *   - $team->disable_onboarding_nudges (one-click unsubscribe writes this)
 *   - config('services.onboarding_nudges.enabled') global env killswitch
 *
 * NO nudge dispatches until BOTH kill switches are clear + the ledger
 * shows the given stage hasn't already been sent for that team.
 */
class SendOnboardingNudge implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        if (! (bool) config('services.onboarding_nudges.enabled', true)) {
            return;
        }

        Team::query()
            ->where('disable_onboarding_nudges', false)
            ->chunkById(200, function ($teams): void {
                foreach ($teams as $team) {
                    $this->processTeam($team);
                }
            });
    }

    private function processTeam(Team $team): void
    {
        // Kill switch — must run BEFORE queueing per constraint #7.
        if ($team->disable_onboarding_nudges) {
            return;
        }

        $owner = $team->owner()->first();
        if (! $owner instanceof User || ! $owner->email) {
            return;
        }

        $createdAt = $team->created_at;
        if (! $createdAt instanceof Carbon && ! $createdAt instanceof \Carbon\CarbonImmutable) {
            return;
        }
        $ageHours = now()->diffInHours($createdAt, true);
        $ageDays  = now()->diffInDays($createdAt, true);

        // Suppress if user has activated OR replied (any inbound activity =
        // "engaged", we back off). Detected by presence of a message.
        $hasEngaged = $this->hasAnyMessage($team);

        // Nudge 1 — +1h if onboarding not completed
        if ($ageHours >= 1
            && $team->nudge_1_sent_at === null
            && $team->onboarding_completed_at === null
        ) {
            $this->send($team, $owner, 1, new OnboardingNudge1MeetAi($owner, $team, $this->unsubscribeUrl($team)));
            return;
        }

        // Nudge 2 — +1 day if no Page connected
        if ($ageDays >= 1
            && $team->nudge_2_sent_at === null
            && ! $this->hasAnyPage($team)
        ) {
            $this->send($team, $owner, 2, new OnboardingNudge2ConnectPage($owner, $team, $this->unsubscribeUrl($team)));
            return;
        }

        // Nudge 3 — +3 days if no message ever received/sent
        if ($ageDays >= 3
            && $team->nudge_3_sent_at === null
            && ! $hasEngaged
        ) {
            $this->send($team, $owner, 3, new OnboardingNudge3PersonalFromOmar($owner, $team, $this->unsubscribeUrl($team)));
            return;
        }

        // Nudge 4 — +7 days if still not activated
        if ($ageDays >= 7
            && $team->nudge_4_sent_at === null
            && ! $hasEngaged
        ) {
            $this->send($team, $owner, 4, new OnboardingNudge4EmailFounder($owner, $team, $this->unsubscribeUrl($team)));
            return;
        }
    }

    private function send(Team $team, User $owner, int $stage, $mailable): void
    {
        try {
            Mail::to($owner->email)->queue($mailable);
            $team->forceFill(["nudge_{$stage}_sent_at" => now()])->save();
        } catch (\Throwable $e) {
            Log::warning('Onboarding nudge send failed', [
                'team_id' => $team->id,
                'stage'   => $stage,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private function unsubscribeUrl(Team $team): string
    {
        return URL::signedRoute(
            'onboarding.nudges.unsubscribe',
            ['team' => $team->id],
            now()->addDays(30),
        );
    }

    private function hasAnyPage(Team $team): bool
    {
        return Page::query()->where('team_id', $team->id)->exists();
    }

    private function hasAnyMessage(Team $team): bool
    {
        return Message::query()
            ->whereIn('conversation_id', function ($q) use ($team) {
                $q->select('id')->from('conversations')->where('team_id', $team->id);
            })
            ->exists();
    }
}
