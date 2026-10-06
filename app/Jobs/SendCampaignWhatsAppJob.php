<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Services\Billing\AiCredits;
use App\Services\Wuzapi\WhatsAppSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Per-recipient WhatsApp send.
 *
 * Runs on the `campaigns` queue. Never dispatch to `urgent` from here —
 * see docs/superpowers/specs/2026-08-26-bulk-multichannel-campaigns-design.md
 * "Banned patterns".
 */
class SendCampaignWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1; // Retries managed via scheduled_at bump, not queue-level tries.
    public int $timeout = 45;

    public function __construct(public int $recipientId)
    {
        $this->onQueue('campaigns');
    }

    public function handle(WhatsAppSender $sender): void
    {
        /** @var CampaignRecipient|null $r */
        $r = CampaignRecipient::with('campaign.senderPage')->find($this->recipientId);
        if (! $r || $r->status !== 'queued') {
            return;
        }

        $campaign = $r->campaign;
        if (! $campaign || $campaign->status !== 'active') {
            return;
        }

        // Phase 4 per-team throttle (docs/OT1_LIMITS.md §11) — mirrors
        // TranscribeAudio's Cache::add pattern. Caps concurrent sends per
        // team at 3 so one team's 100k-row campaign can't monopolize the
        // single `campaigns` queue worker and delay every other team's
        // campaigns. If we're over the cap, release with a small delay so
        // the recipient goes back to the queue and gets picked up shortly.
        $throttleKey = "campaign:send:inflight:{$campaign->team_id}";
        $current = (int) \Illuminate\Support\Facades\Cache::add($throttleKey, 1, 60) ? 1
            : (int) \Illuminate\Support\Facades\Cache::increment($throttleKey);
        if ($current > 3) {
            \Illuminate\Support\Facades\Cache::decrement($throttleKey);
            $this->release(5);
            return;
        }

        $page = $campaign->senderPage;
        if (! $page || ! $page->is_active) {
            $r->update(['status' => 'failed', 'last_error' => 'sender page unavailable']);
            return;
        }

        // Phase RP (2026-10-06): verify this recipient can be charged BEFORE
        // sending. Half-sending a campaign is a worse UX than failing fast —
        // the operator wants either "all delivered" or "clear reason stopped".
        $team = $campaign->team;
        $credits = app(AiCredits::class);
        if ($team !== null) {
            $cost = $credits->costFor('bulk_campaign_recipient');
            if ($cost > 0 && $credits->balance($team)->total() < $cost) {
                $r->update([
                    'status'     => 'failed',
                    'last_error' => 'insufficient_credits',
                ]);
                return;
            }
        }

        $body = $this->renderBody((string) $campaign->message_template, $r);

        try {
            $result = $sender->send($page, (string) $r->phone, $body);
        } finally {
            \Illuminate\Support\Facades\Cache::decrement($throttleKey);
        }

        if ($result->sent) {
            $r->update(['status' => 'sent', 'sent_at' => now()]);
            $campaign->increment('sent_count');

            // Phase RP: charge AFTER successful send. A ledger failure must
            // NEVER undo a message the customer already received — mirrors
            // SendAiResponse's swallow pattern (CLAUDE.md pin #5).
            if ($team !== null) {
                try {
                    $credits->charge(
                        $team,
                        'bulk_campaign_recipient',
                        [
                            'idempotency_key'  => "campaign:{$campaign->id}:recipient:{$r->id}",
                            'cost_source_type' => Campaign::class,
                            'cost_source_id'   => $campaign->id,
                            'campaign_name'    => $campaign->name,
                            'platform'         => $campaign->platform,
                        ],
                    );
                } catch (Throwable $e) {
                    Log::warning('AiCredits::charge failed for campaign recipient — message was already sent', [
                        'campaign_id'  => $campaign->id,
                        'recipient_id' => $r->id,
                        'error'        => $e->getMessage(),
                    ]);
                }
            }
            return;
        }

        if ($result->transient) {
            $attempts = (int) $r->attempts + 1;
            if ($attempts >= 3) {
                $r->update([
                    'status'     => 'failed',
                    'attempts'   => $attempts,
                    'last_error' => $result->error,
                ]);
                return;
            }
            $r->update([
                'status'       => 'pending',
                'attempts'     => $attempts,
                'last_error'   => $result->error,
                'scheduled_at' => now()->addSeconds((int) pow(2, $attempts) * 60),
            ]);
            return;
        }

        // Permanent.
        $r->update(['status' => 'failed', 'last_error' => $result->error]);
    }

    private function renderBody(string $template, CampaignRecipient $r): string
    {
        $name = trim((string) ($r->name ?? '')) ?: 'there';
        return str_replace(['{{name}}', '{{phone}}'], [$name, (string) $r->phone], $template);
    }
}
