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
 * Phase C nudge #3 — +3 days after signup if no message ever received/sent.
 *
 * Multipart: HTML alternative keeps the Gmail-typed tone but renders the
 * unsubscribe link as a styled button (user feedback 2026-09-28). Plaintext
 * alternative preserves the founder-typed feel for text-only clients.
 */
class OnboardingNudge3PersonalFromOmar extends Mailable
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
            from: new Address('omareltak7@gmail.com', 'Omar Eltak'),
            replyTo: [new Address('omareltak7@gmail.com', 'Omar Eltak')],
            subject: 'quick question about your OT1-Pro setup',
        );
    }

    public function content(): Content
    {
        // Multipart: HTML view carries a styled unsubscribe button (readers
        // asked for a button, not a raw URL, on 2026-09-28), plaintext view
        // is still sent as the multipart alternative for text-only clients.
        return new Content(
            view: 'emails.onboarding.nudge-3-personal-html',
            text: 'emails.onboarding.nudge-3-personal',
            with: [
                'user'           => $this->user,
                'team'           => $this->team,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ],
        );
    }
}
