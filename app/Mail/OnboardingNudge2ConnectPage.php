<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Team;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Phase C nudge #2 — +1 day after signup if no Page is connected yet.
 * Points at the concierge managed-onboarding flow (Phase B).
 */
class OnboardingNudge2ConnectPage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Team $team,
        public string $unsubscribeUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('omareltak7@gmail.com', 'Omar from OT1-Pro'),
            replyTo: [new Address('omareltak7@gmail.com', 'Omar from OT1-Pro')],
            subject: 'Ready to connect your first page? We\'ll do it for you.',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.onboarding.nudge-2-connect-page',
            with: [
                'user'           => $this->user,
                'team'           => $this->team,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ],
        );
    }
}
