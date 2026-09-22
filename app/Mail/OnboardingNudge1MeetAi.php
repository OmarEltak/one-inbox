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
 * Phase C nudge #1 — +1 hour after signup if the user never finished the
 * "Meet Your AI" playground.
 *
 * From address is Omar's personal by policy — see Phase C spec + CLAUDE.md.
 */
class OnboardingNudge1MeetAi extends Mailable
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
            subject: 'Meet your AI (2 min, no page needed)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.onboarding.nudge-1-meet-ai',
            with: [
                'user'            => $this->user,
                'team'            => $this->team,
                'unsubscribeUrl'  => $this->unsubscribeUrl,
            ],
        );
    }
}
