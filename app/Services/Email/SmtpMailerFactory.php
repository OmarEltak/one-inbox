<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Models\Page;
use RuntimeException;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;

/**
 * Build a Symfony Mailer instance from a connected email Page's stored
 * SMTP credentials. The Page's page_access_token holds the encrypted
 * SMTP password; metadata holds host/port/encryption.
 */
class SmtpMailerFactory
{
    public function make(Page $page): Mailer
    {
        return new Mailer($this->makeTransport($page));
    }

    public function makeTransport(Page $page): TransportInterface
    {
        if ($page->platform !== 'email') {
            throw new RuntimeException("Page {$page->id} is not an email account.");
        }

        $meta = $page->metadata ?? [];
        $host = $meta['smtp_host'] ?? null;
        $port = (int) ($meta['smtp_port'] ?? 587);
        $encryption = $meta['smtp_encryption'] ?? 'tls';

        if (! $host) {
            throw new RuntimeException("SMTP host not configured for page {$page->id}.");
        }

        $username = $page->platform_page_id;
        $password = $page->page_access_token; // Cast 'encrypted' decrypts on read.

        $scheme = $encryption === 'ssl' ? 'smtps' : 'smtp';
        $dsn = new Dsn(
            scheme: $scheme,
            host: $host,
            user: $username,
            password: $password,
            port: $port,
        );

        return (new EsmtpTransportFactory())->create($dsn);
    }

    public function senderAddress(Page $page): string
    {
        return $page->platform_page_id;
    }
}
