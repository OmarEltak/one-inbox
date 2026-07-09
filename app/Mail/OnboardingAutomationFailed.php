<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\OnboardingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnboardingAutomationFailed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public OnboardingRequest $request,
        public string $note,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[OT1] Managed onboarding needs human review — request #{$this->request->id}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.onboarding-automation-failed',
            with: [
                'req'  => $this->request,
                'note' => $this->note,
            ],
        );
    }
}
