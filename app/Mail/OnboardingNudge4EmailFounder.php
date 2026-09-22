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
 * Phase C nudge #4 — +7 days after signup if still not activated.
 * Ask: reply to this email. NOT book-a-call.
 */
class OnboardingNudge4EmailFounder extends Mailable
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
            subject: 'want us to help you get set up? just hit reply.',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.onboarding.nudge-4-email-founder',
            with: [
                'user'           => $this->user,
                'team'           => $this->team,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ],
        );
    }
}
