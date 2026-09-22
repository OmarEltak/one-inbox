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
 * PLAIN TEXT ONLY. No HTML template chrome — this is supposed to look like
 * Omar typed it in Gmail at 11pm. See Phase C spec + acceptance criteria.
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
            from: new Address('omareltak7@gmail.com', 'Omar Eltakrori'),
            replyTo: [new Address('omareltak7@gmail.com', 'Omar Eltakrori')],
            subject: 'quick question about your OT1-Pro setup',
        );
    }

    public function content(): Content
    {
        // text: only — no HTML view. Keeps deliverability high (personal
        // Gmail-shaped mail) and matches the "founder wrote this himself"
        // frame required by the acceptance criteria.
        return new Content(
            text: 'emails.onboarding.nudge-3-personal',
            with: [
                'user'           => $this->user,
                'team'           => $this->team,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ],
        );
    }
}
