<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialExpiredPaymentDue extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Team $team)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your OT1-Pro trial is up — invoice attached',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.billing.trial-expired-payment-due',
            with: [
                'team' => $this->team,
            ],
        );
    }
}
