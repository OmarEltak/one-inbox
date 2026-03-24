<?php

namespace App\Console\Commands;

use App\Jobs\ProcessIncomingMessage;
use App\Models\Page;
use App\Models\WebhookLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Webklex\PHPIMAP\ClientManager;

class FetchEmails extends Command
{
    protected $signature   = 'emails:fetch';
    protected $description = 'Poll all active email inboxes via IMAP and dispatch ProcessIncomingMessage for new emails';

    public function handle(): void
    {
        $pages = Page::where('platform', 'email')
            ->where('is_active', true)
            ->get();

        if ($pages->isEmpty()) {
            return;
        }

        foreach ($pages as $page) {
            try {
                $this->fetchForPage($page);
            } catch (\Throwable $e) {
                Log::error("FetchEmails: failed for page {$page->id} ({$page->platform_page_id})", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function fetchForPage(Page $page): void
    {
        $meta     = $page->metadata ?? [];
        $email    = $page->platform_page_id;
        $password = decrypt($page->page_access_token);
        $lastUid  = (int) ($meta['last_fetched_uid'] ?? 0);

        $client = $this->buildClient($email, $password, $meta);
        $client->connect();

        try {
            $folder = $client->getFolder('INBOX');

            if (! $folder) {
                Log::warning("FetchEmails: could not open INBOX for {$email}");
                return;
            }

            if ($lastUid > 0) {
                // Incremental: fetch emails from the last 2 hours (safe overlap window).
                // UID-range queries cause "BAD Could not parse command" on Gmail, so we
                // use date-based queries. Duplicates are skipped in processMessage().
                $messages = $folder->query()
                    ->since(\Carbon\Carbon::now()->subHours(2))
                    ->setFetchBody(true)
                    ->leaveUnread()
                    ->get();
            } else {
                // First run: the inbox may contain thousands of messages.
                // Step 1 — fetch headers only (no body) for last 30 days; very lightweight.
                $headers = $folder->query()
                    ->since(\Carbon\Carbon::now()->subDays(30))
                    ->setFetchBody(false)
                    ->leaveUnread()
                    ->get();

                if ($headers->isEmpty()) {
                    return;
                }

                // Take the 50 most recent by UID and find the oldest date in that set.
                $top50      = $headers->sortByDesc(fn ($m) => (int) $m->uid)->take(50);
                $oldestDate = \Carbon\Carbon::parse($top50->last()->date->first()->timestamp)->startOfDay();

                // Step 2 — re-fetch those same ~50 messages with full bodies using the
                // date window. UID-range queries cause "BAD" on Gmail so we use ->since().
                $messages = $folder->query()
                    ->since($oldestDate)
                    ->setFetchBody(true)
                    ->leaveUnread()
                    ->get();
            }

            if ($messages->isEmpty()) {
                return;
            }

            $maxUid = $lastUid;

            foreach ($messages as $message) {
                try {
                    $uid = (int) $message->uid;

                    if ($uid <= $lastUid) {
                        continue;
                    }

                    $this->processMessage($message, $page, $email);
                    $maxUid = max($maxUid, $uid);
                } catch (\Throwable $e) {
                    Log::warning("FetchEmails: failed to process message for {$email}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($maxUid > $lastUid) {
                $updatedMeta                     = $meta;
                $updatedMeta['last_fetched_uid'] = $maxUid;

                // Track the oldest imported email date for backward IMAP pagination
                if (! isset($updatedMeta['oldest_fetched_at'])) {
                    $minTs = $messages->min(fn ($m) => $m->date->first()?->timestamp ?? PHP_INT_MAX);
                    $updatedMeta['oldest_fetched_at'] = $minTs;
                    $updatedMeta['has_more_imap']     = true; // assume there are older emails
                }

                $page->update(['metadata' => $updatedMeta]);
            }
        } finally {
            $client->disconnect();
        }
    }

    public function processMessage(\Webklex\PHPIMAP\Message $message, Page $page, string $inboxEmail): void
    {
        $fromAddress = $message->getFrom()->first();
        $fromEmail   = $fromAddress ? (string) $fromAddress->mail : null;
        $fromName    = $fromAddress ? (string) $fromAddress->personal : $fromEmail;

        if (! $fromEmail) {
            return;
        }

        // Skip our own outbound messages
        if (strtolower($fromEmail) === strtolower($inboxEmail)) {
            return;
        }

        $messageId  = (string) ($message->message_id->first() ?? '');
        $inReplyTo  = (string) ($message->in_reply_to ?? '');
        $subject    = (string) ($message->subject->first() ?? '(no subject)');
        $textBody   = (string) ($message->getTextBody() ?? '');
        $htmlBody   = (string) ($message->getHTMLBody() ?? '');
        $date       = $message->date->first()?->timestamp ?? time();

        // Skip already-imported messages (handles overlap in date-based incremental queries)
        if ($messageId && \App\Models\Message::where('platform_message_id', $messageId)->exists()) {
            return;
        }

        $payload = [
            'message_id'  => $messageId,
            'in_reply_to' => $inReplyTo,
            'from_email'  => $fromEmail,
            'from_name'   => $fromName ?: $fromEmail,
            'to'          => $inboxEmail,
            'subject'     => $subject,
            'text'        => $textBody ?: strip_tags($htmlBody),
            'html'        => $htmlBody,
            'date'        => $date,
            'to_page_id'  => $inboxEmail,
        ];

        $log = WebhookLog::create([
            'team_id'    => $page->team_id,
            'platform'   => 'email',
            'event_type' => 'incoming_email',
            'payload'    => $payload,
        ]);

        ProcessIncomingMessage::dispatch($log->id);
    }

    public function buildClient(string $email, string $password, array $meta): \Webklex\PHPIMAP\Client
    {
        $host       = $meta['imap_host']       ?? 'imap.gmail.com';
        $port       = (int) ($meta['imap_port']       ?? 993);
        $encryption = $meta['imap_encryption'] ?? 'ssl';

        $cm = new ClientManager();

        return $cm->make([
            'host'          => $host,
            'port'          => $port,
            'encryption'    => $encryption === 'none' ? false : $encryption,
            'username'      => $email,
            'password'      => $password,
            'protocol'      => 'imap',
            'validate_cert' => false,
            'timeout'       => 30,
        ]);
    }
}
