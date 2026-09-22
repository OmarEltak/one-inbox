<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialEndingSoon extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Team $team, public int $daysLeft)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your OT1-Pro trial ends in {$this->daysLeft} day" . ($this->daysLeft === 1 ? '' : 's'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.billing.trial-ending-soon',
            with: [
                'team'     => $this->team,
                'daysLeft' => $this->daysLeft,
            ],
        );
    }
}
