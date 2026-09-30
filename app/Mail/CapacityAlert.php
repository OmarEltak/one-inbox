<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Alert email sent from capacity:health-check when any signal trips a
 * threshold. See docs/OT1_LIMITS.md §7.
 */
class CapacityAlert extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param string $key      short signal id (cpu_load, ram_free, queue_urgent, …)
     * @param array{tripped: bool, value: mixed, threshold: mixed, message: string} $signal
     */
    public function __construct(
        public string $key,
        public array  $signal,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('omareltak7@gmail.com', 'OT1 Capacity Monitor'),
            subject: sprintf('[OT1] %s', $this->signal['message']),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.ops.capacity-alert',
            with: [
                'key'       => $this->key,
                'value'     => $this->signal['value'],
                'threshold' => $this->signal['threshold'],
                'message'   => $this->signal['message'],
                'host'      => config('app.url'),
                'now'       => now()->toIso8601String(),
            ],
        );
    }
}
