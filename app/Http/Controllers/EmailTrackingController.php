<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use App\Models\EmailSuppression;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class EmailTrackingController extends Controller
{
    /**
     * 1x1 transparent GIF tracking pixel. Signed URL — Laravel's
     * `signed` middleware enforces signature before this runs.
     */
    public function open(Request $request, CampaignRecipient $recipient): Response
    {
        if ($recipient->status === CampaignRecipient::STATUS_SENT) {
            $recipient->update([
                'status'    => CampaignRecipient::STATUS_OPENED,
                'opened_at' => now(),
            ]);
            DB::table('campaigns')->where('id', $recipient->campaign_id)->increment('opened_count');
        } elseif ($recipient->status === CampaignRecipient::STATUS_OPENED && ! $recipient->opened_at) {
            $recipient->update(['opened_at' => now()]);
        }

        // 1x1 transparent GIF.
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($gif, 200, [
            'Content-Type'  => 'image/gif',
            'Content-Length'=> (string) strlen($gif),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
        ]);
    }

    /**
     * Public unsubscribe page (GET).
     */
    public function unsubscribeShow(Request $request, CampaignRecipient $recipient): Response
    {
        return response(view('emails.unsubscribe', [
            'recipient' => $recipient,
            'already'   => EmailSuppression::isSuppressed(
                $recipient->campaign->team_id,
                $recipient->email,
            ),
        ]));
    }

    /**
     * Confirm unsubscribe (POST). Inserts suppression and marks
     * the recipient. Idempotent.
     */
    public function unsubscribeConfirm(Request $request, CampaignRecipient $recipient): Response
    {
        $campaign = $recipient->campaign;

        EmailSuppression::firstOrCreate(
            [
                'team_id' => $campaign->team_id,
                'email'   => strtolower(trim($recipient->email)),
            ],
            [
                'reason'      => EmailSuppression::REASON_UNSUBSCRIBED,
                'campaign_id' => $campaign->id,
            ]
        );

        if ($recipient->status !== CampaignRecipient::STATUS_UNSUBSCRIBED) {
            $recipient->update(['status' => CampaignRecipient::STATUS_UNSUBSCRIBED]);
            DB::table('campaigns')->where('id', $campaign->id)->increment('unsubscribed_count');
        }

        return response(view('emails.unsubscribed', ['recipient' => $recipient]));
    }
}
