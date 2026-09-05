<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\CampaignRecipient;
use App\Services\Wuzapi\WhatsAppSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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

        $page = $campaign->senderPage;
        if (! $page || ! $page->is_active) {
            $r->update(['status' => 'failed', 'last_error' => 'sender page unavailable']);
            return;
        }

        $body = $this->renderBody((string) $campaign->message_template, $r);
        $result = $sender->send($page, (string) $r->phone, $body);

        if ($result->sent) {
            $r->update(['status' => 'sent', 'sent_at' => now()]);
            $campaign->increment('sent_count');
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
