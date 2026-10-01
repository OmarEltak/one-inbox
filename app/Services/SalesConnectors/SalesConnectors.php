<?php

namespace App\Services\SalesConnectors;

use App\Jobs\PushSalesConnectorRow;
use App\Models\AiConfig;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Str;

/**
 * Sends a row to the operator's own sheet / automation when the AI closes a
 * deal or captures the info the Sales Goal asks for. Configured per page in
 * AI Settings → Connectors.
 *
 * Delivery is a plain HTTPS POST of JSON, which covers:
 *  - Google Sheets via a 10-line Apps Script web app (no OAuth / Google app
 *    verification needed — that review is as slow as Meta's),
 *  - Excel Online via Power Automate "When an HTTP request is received",
 *  - Zapier / Make / n8n webhooks into anything else.
 * Plus a CSV download (opens in Excel) from the same tab.
 */
class SalesConnectors
{
    public const EVENT_LEAD_CAPTURED = 'lead_captured';

    public const EVENT_DEAL_CLOSED = 'deal_closed';

    public const EVENT_TEST = 'test';

    public const EVENTS = [self::EVENT_LEAD_CAPTURED, self::EVENT_DEAL_CLOSED];

    public const HEADERS = ['Date', 'Event', 'Contact', 'Phone', 'Email', 'Channel', 'Page', 'Lead status', 'Captured info', 'Last customer message', 'Conversation ID'];

    /** Test seam for DNS resolution (host => list of IPs, or false). */
    public static ?\Closure $resolver = null;

    public static function notify(Conversation $conversation, string $event): void
    {
        $settings = AiConfig::where('page_id', $conversation->page_id)->value('sales_connectors');
        $settings = is_array($settings) ? $settings : (json_decode((string) $settings, true) ?: []);

        if (empty($settings['sheet_url']) && empty($settings['webhook_url'])) {
            return;
        }
        if (! in_array($event, $settings['events'] ?? self::EVENTS, true)) {
            return;
        }

        PushSalesConnectorRow::dispatch($conversation->id, $event)->afterCommit();
    }

    /** JSON body: named fields for automations + ordered headers/row for sheets. */
    public static function payload(Conversation $conversation, string $event): array
    {
        $row = self::row($conversation, $event);

        return [
            'event'   => $event,
            'headers' => self::HEADERS,
            'row'     => array_values($row),
            'fields'  => array_combine(array_map(fn ($h) => Str::snake($h), self::HEADERS), array_values($row)),
            'captured' => (array) ($conversation->captured_data ?? []),
        ];
    }

    /** @return array<string, string> keyed by HEADERS */
    public static function row(Conversation $conversation, string $event, mixed $at = null): array
    {
        $contact = $conversation->contact;
        $captured = (array) ($conversation->captured_data ?? []);

        $lastMessage = Message::where('conversation_id', $conversation->id)
            ->where('direction', 'inbound')->whereNotNull('content')
            ->latest('id')->value('content');

        return array_combine(self::HEADERS, [
            ($at ? \Illuminate\Support\Carbon::parse($at) : now())->toDateTimeString(),
            match ($event) {
                self::EVENT_LEAD_CAPTURED => 'Required info captured',
                self::EVENT_DEAL_CLOSED   => 'Deal closed',
                default                   => 'Test row',
            },
            (string) ($captured['name'] ?? $contact?->name ?? ''),
            (string) ($captured['phone'] ?? $contact?->phone ?? ''),
            (string) ($captured['email'] ?? $contact?->email ?? ''),
            (string) $conversation->platform,
            (string) ($conversation->page?->name ?? ''),
            (string) ($contact?->lead_status ?? ''),
            collect($captured)->map(fn ($v, $k) => Str::headline((string) $k) . ': ' . (is_scalar($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE)))->implode(' · '),
            Str::limit(trim(preg_replace('/\s+/u', ' ', (string) $lastMessage) ?? ''), 300),
            (string) $conversation->id,
        ]);
    }

    /**
     * Only public HTTPS endpoints: the URL is operator-supplied and we POST
     * to it from the server, so loopback / private / link-local targets are
     * refused (SSRF).
     */
    public static function isAllowedUrl(?string $url): bool
    {
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL) || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
            return false;
        }

        $host = strtolower(trim((string) parse_url($url, PHP_URL_HOST), '[]'));
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return false;
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP)
            ? [$host]
            : (self::$resolver ? (self::$resolver)($host) : gethostbynamel($host));

        if (! $ips) {
            return false;
        }

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return true;
    }

    /** Last delivery result, shown in the Connectors tab. */
    public static function recordDelivery(AiConfig $config, string $event, array $errors): void
    {
        $settings = (array) ($config->sales_connectors ?? []);
        $settings['last_delivery'] = [
            'at'    => now()->toIso8601String(),
            'event' => $event,
            'ok'    => $errors === [],
            'error' => $errors ? implode(' | ', $errors) : null,
        ];
        $config->sales_connectors = $settings;
        $config->saveQuietly();
    }
}
