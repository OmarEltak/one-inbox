<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\OnboardingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewOnboardingRequestSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public OnboardingRequest $request) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[OT1] Accept Page invitation — {$this->request->business_name} (#{$this->request->id})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.new-onboarding-request',
            with: ['req' => $this->request],
        );
    }
}
