<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\EmailSuppression;
use App\Services\Email\SmtpMailerFactory;
use App\Services\Email\TemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Email;
use Throwable;

/**
 * Send one email-campaign recipient. Respects campaign status,
 * per-team suppression list, attempt counts, and exponential backoff.
 */
class SendCampaignEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1; // we handle retry ourselves to control backoff + status

    public const MAX_ATTEMPTS = 3;

    public function __construct(public int $recipientId) {}

    public function handle(SmtpMailerFactory $mailerFactory, TemplateRenderer $renderer): void
    {
        $recipient = CampaignRecipient::with(['campaign.senderPage'])->find($this->recipientId);

        if (! $recipient) {
            return;
        }

        if (! in_array($recipient->status, [
            CampaignRecipient::STATUS_PENDING,
            CampaignRecipient::STATUS_SENDING,
        ], true)) {
            return;
        }

        $campaign = $recipient->campaign;
        if (! $campaign || $campaign->status !== Campaign::STATUS_ACTIVE) {
            return;
        }

        // Suppression check (per-team).
        if (EmailSuppression::isSuppressed($campaign->team_id, $recipient->email)) {
            $recipient->update([
                'status'     => CampaignRecipient::STATUS_UNSUBSCRIBED,
                'failed_at'  => null,
                'last_error' => 'Suppressed before send.',
            ]);
            DB::table('campaigns')->where('id', $campaign->id)->increment('unsubscribed_count');
            return;
        }

        $page = $campaign->senderPage;
        if (! $page || ! $page->is_active || $page->platform !== 'email') {
            $this->markFailed($recipient, 'Sender email account is missing or inactive.', terminal: true);
            return;
        }

        $rendered = $renderer->render(
            (string) ($campaign->subject ?? ''),
            (string) ($campaign->message_template ?? ''),
            $recipient,
        );

        // Mark sending to avoid double-dispatch races.
        $recipient->update([
            'status'   => CampaignRecipient::STATUS_SENDING,
            'attempts' => $recipient->attempts + 1,
        ]);

        try {
            $email = (new Email())
                ->from($mailerFactory->senderAddress($page))
                ->to($recipient->email)
                ->subject($rendered['subject'])
                ->text($rendered['body']);

            $mailerFactory->make($page)->send($email);

            $recipient->update([
                'status'     => CampaignRecipient::STATUS_SENT,
                'sent_at'    => now(),
                'last_error' => null,
            ]);
            DB::table('campaigns')->where('id', $campaign->id)->increment('sent_count');
        } catch (Throwable $e) {
            Log::warning('Bulk email send failed', [
                'recipient_id' => $recipient->id,
                'campaign_id'  => $campaign->id,
                'error'        => $e->getMessage(),
            ]);

            $terminal = $recipient->attempts >= self::MAX_ATTEMPTS;
            $this->markFailed($recipient, $e->getMessage(), terminal: $terminal);

            if (! $terminal) {
                $delay = (int) min(3600, 60 * (2 ** $recipient->attempts));
                self::dispatch($recipient->id)->delay(now()->addSeconds($delay));
            }
        }
    }

    private function markFailed(CampaignRecipient $recipient, string $error, bool $terminal): void
    {
        $update = [
            'last_error' => substr($error, 0, 1000),
            'status'     => $terminal
                ? CampaignRecipient::STATUS_FAILED
                : CampaignRecipient::STATUS_PENDING,
        ];

        if ($terminal) {
            $update['failed_at'] = now();
            DB::table('campaigns')->where('id', $recipient->campaign_id)->increment('failed_count');
        }

        $recipient->update($update);
    }
}
