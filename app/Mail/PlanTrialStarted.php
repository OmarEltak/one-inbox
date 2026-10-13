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
 * Sent once when a user picks a plan on /onboarding/pick-your-plan. Confirms
 * the selection, states the 14-day trial clock, and reminds them we only
 * invoice (bank transfer) when they decide to continue — no auto-charge.
 */
class PlanTrialStarted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Team $team,
        public string $plan,
    ) {}

    public function envelope(): Envelope
    {
        $pretty = ucfirst($this->plan);

        return new Envelope(
            subject: "You're on the {$pretty} plan — 14-day trial started",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.plan-trial-started',
            with: [
                'user' => $this->user,
                'team' => $this->team,
                'plan' => $this->plan,
            ],
        );
    }
}
