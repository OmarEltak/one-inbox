<?php

namespace App\Services\Platforms;

use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message as MailMessage;
use Webklex\PHPIMAP\ClientManager;

class EmailPlatform extends AbstractPlatform
{
    /**
     * Email uses form-based connection, no OAuth redirect.
     */
    public function getConnectUrl(): string
    {
        return '';
    }

    /**
     * Validate IMAP credentials by attempting a test connection, then store account + page.
     */
    public function handleCallback(Request $request, int $teamId): ConnectedAccount
    {
        $email           = $request->input('email');
        $password        = $request->input('password');
        $imapHost        = $request->input('imap_host');
        $imapPort        = (int) $request->input('imap_port', 993);
        $imapEncryption  = $request->input('imap_encryption', 'ssl');
        $smtpHost        = $request->input('smtp_host');
        $smtpPort        = (int) $request->input('smtp_port', 587);
        $smtpEncryption  = $request->input('smtp_encryption', 'tls');

        // Test IMAP connection
        $this->testImapConnection($email, $password, $imapHost, $imapPort, $imapEncryption);

        $account = ConnectedAccount::updateOrCreate(
            [
                'team_id'          => $teamId,
                'platform'         => 'email',
                'platform_user_id' => $email,
            ],
            [
                'name'         => $email,
                'access_token' => encrypt($password),
                'scopes'       => ['imap', 'smtp'],
                'is_active'    => true,
                'connected_at' => now(),
                'metadata'     => [
                    'imap_host'       => $imapHost,
                    'imap_port'       => $imapPort,
                    'imap_encryption' => $imapEncryption,
                    'smtp_host'       => $smtpHost,
                    'smtp_port'       => $smtpPort,
                    'smtp_encryption' => $smtpEncryption,
                ],
            ]
        );

        Page::updateOrCreate(
            [
                'team_id'          => $teamId,
                'platform'         => 'email',
                'platform_page_id' => $email,
            ],
            [
                'connected_account_id' => $account->id,
                'name'                 => $email,
                'page_access_token'    => encrypt($password),
                'category'             => 'email_inbox',
                'is_active'            => true,
                'metadata'             => [
                    'imap_host'        => $imapHost,
                    'imap_port'        => $imapPort,
                    'imap_encryption'  => $imapEncryption,
                    'smtp_host'        => $smtpHost,
                    'smtp_port'        => $smtpPort,
                    'smtp_encryption'  => $smtpEncryption,
                    'last_fetched_uid' => 0,
                ],
            ]
        );

        return $account;
    }

    /**
     * Send a reply via SMTP using per-account credentials.
     */
    public function sendMessage(Page $page, Conversation $conversation, string $content, string $contentType = 'text', ?array $media = null): Message
    {
        $meta     = $page->metadata ?? [];
        $fromEmail = $page->platform_page_id;
        $password  = decrypt($page->page_access_token);

        // Determine reply-to address from conversation metadata
        $toEmail = $conversation->metadata['contact_email'] ?? null;
        $subject = $conversation->metadata['subject'] ?? 'Re: (no subject)';
        $inReplyTo = $conversation->metadata['last_message_id'] ?? null;

        if (! $toEmail) {
            Log::error("Email sendMessage: no contact_email for conversation {$conversation->id}");

            // Still create the record so the UI shows the attempt
            return Message::create([
                'conversation_id'    => $conversation->id,
                'direction'          => 'outbound',
                'sender_type'        => 'user',
                'sender_id'          => auth()->id(),
                'content_type'       => 'text',
                'content'            => $content,
                'platform_sent_at'   => now(),
            ]);
        }

        // Configure a one-off mailer with this account's SMTP credentials
        config([
            'mail.mailers.email_platform' => [
                'transport'  => 'smtp',
                'host'       => $meta['smtp_host'] ?? 'smtp.gmail.com',
                'port'       => $meta['smtp_port'] ?? 587,
                'encryption' => $meta['smtp_encryption'] ?? 'tls',
                'username'   => $fromEmail,
                'password'   => $password,
            ],
        ]);

        $headers = [];
        if ($inReplyTo) {
            $headers['In-Reply-To'] = $inReplyTo;
            $headers['References']  = $inReplyTo;
        }

        Mail::mailer('email_platform')
            ->raw($content, function (MailMessage $msg) use ($fromEmail, $toEmail, $subject, $headers) {
                $msg->from($fromEmail)
                    ->to($toEmail)
                    ->subject($subject);

                foreach ($headers as $name => $value) {
                    $msg->getHeaders()->addTextHeader($name, $value);
                }
            });

        $message = Message::create([
            'conversation_id'  => $conversation->id,
            'direction'        => 'outbound',
            'sender_type'      => 'user',
            'sender_id'        => auth()->id(),
            'content_type'     => 'text',
            'content'          => $content,
            'platform_sent_at' => now(),
            'metadata'         => ['subject' => $subject, 'to' => $toEmail],
        ]);

        $conversation->update([
            'last_message_at'      => now(),
            'last_message_preview' => \Illuminate\Support\Str::limit($content, 100),
        ]);

        return $message;
    }

    /**
     * Test IMAP connection using webklex (socket-based, no native ext required) — throws on failure.
     */
    protected function testImapConnection(string $email, string $password, string $host, int $port, string $encryption): void
    {
        $cm     = new ClientManager();
        $client = $cm->make([
            'host'          => $host,
            'port'          => $port,
            'encryption'    => $encryption === 'none' ? false : $encryption,
            'username'      => $email,
            'password'      => $password,
            'protocol'      => 'imap',
            'validate_cert' => false,
            'timeout'       => 30,
        ]);

        $client->connect();
        $client->disconnect();
    }

    // Not used — driven by FetchEmails command
    public function fetchPages(ConnectedAccount $account): Collection
    {
        return collect();
    }

    public function fetchConversations(Page $page): Collection
    {
        return collect();
    }

    public function fetchMessages(Page $page, string $platformConversationId): Collection
    {
        return collect();
    }

    public function handleWebhook(Request $request): void {}

    public function verifyWebhook(Request $request): mixed
    {
        return null;
    }

    public function disconnect(ConnectedAccount $account): void
    {
        $account->pages()->where('platform', 'email')->update(['is_active' => false]);
        $account->update(['is_active' => false]);
    }
}
