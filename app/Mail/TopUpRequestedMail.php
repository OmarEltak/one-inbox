<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Team;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Phase F — sent to Omar when a user clicks "I've sent payment, notify Omar"
 * on /settings/billing/top-up. Payload is intentionally plain-English so Omar
 * can skim it from Gmail and jump straight to /super-admin/billing to grant.
 *
 * The outbound notification is informational only — the actual credit grant
 * happens in Phase E (super-admin screen). This mail must not auto-credit.
 */
final class TopUpRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Team $team,
        public User $requester,
        public string $product,
        public string $productLabel,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[OT1] Top-up request from Team #{$this->team->id}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.top-up-requested',
            with: [
                'team'         => $this->team,
                'requester'    => $this->requester,
                'product'      => $this->product,
                'productLabel' => $this->productLabel,
                'grantUrl'     => route('super-admin.billing', ['team' => $this->team->id]),
                'submittedAt'  => now()->toDateTimeString(),
            ],
        );
    }
}
