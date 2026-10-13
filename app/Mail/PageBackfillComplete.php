<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Page;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent once per page per backfill cycle when BackfillPageMessages finishes
 * loading all the chats it planned to pull. Confirms what landed and invites
 * the user to ask the AI about it. Mirrors the in-app completion toast on
 * /connections (which the user may have missed if they weren't on that tab).
 */
class PageBackfillComplete extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Page $page,
        public int $chatsLoaded,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->page->name} — {$this->chatsLoaded} chats loaded",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.page-backfill-complete',
            with: [
                'user'        => $this->user,
                'page'        => $this->page,
                'chatsLoaded' => $this->chatsLoaded,
            ],
        );
    }
}
