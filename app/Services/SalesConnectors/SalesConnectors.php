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

    // Column order optimised for e-commerce operators: the fields they need
    // to fulfill an order (Name, Phone, Order details, Address) come first,
    // then the ones they look at for context (Channel, Page, Status, Last
    // message), then the audit columns (Event, Conv ID).
    //
    // Status column is kept for schema stability — operators' deployed Apps
    // Scripts write headers ONCE on first POST and never re-sync, so dropping
    // a column mid-flight shifts every subsequent row left by one. Status is
    // low-value (always "hot" for confirmed orders) but safe to leave.
    public const HEADERS = ['Date', 'Name', 'Phone', 'Order', 'Address', 'Channel', 'Page', 'Status', 'Last message', 'Event', 'Conversation ID'];

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

        // Only confirmed orders land in the sheet. captured['confirmed_at']
        // is set ONLY by SendAiResponse::parseAiOrderConfirmation when the
        // AI emits "سجلت الأوردر" — so leads still in discovery never pollute
        // the sheet, even if complete() fires on partial captured_data.
        $captured = (array) ($conversation->captured_data ?? []);
        if (empty($captured['confirmed_at'])) {
            \Illuminate\Support\Facades\Log::info('SalesConnectors::notify: skipping unconfirmed convo', [
                'conversation_id' => $conversation->id,
                'event'           => $event,
            ]);
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

        // Order column: prefer the structured order_summary that SendAiResponse
        // parses from the AI's confirmation message (clean, authoritative format
        // like "شال كبير أسود × 1 + شال صغير بيج × 2"). Falls back to the
        // raw product/color/quantity triple only if the parser hasn't run yet
        // (e.g. older rows pre-this-commit, or convs the AI never confirmed).
        $orderString = (string) ($captured['order_summary'] ?? '');
        if ($orderString === '') {
            $orderParts = [];
            if (! empty($captured['product']))  $orderParts[] = (string) $captured['product'];
            if (! empty($captured['color']))    $orderParts[] = (string) $captured['color'];
            $orderString = implode(' ', $orderParts);
            if (! empty($captured['quantity'])) {
                $orderString = trim($orderString . ' × ' . $captured['quantity']);
            }
        }

        // Google Sheets auto-strips leading zeros from anything that parses as
        // a number ("01091960842" becomes 1091960842). Prefix with apostrophe —
        // Sheets treats apostrophe-prefixed cells as literal text, so the
        // leading zero stays visible. Apps Script's appendRow() honours this.
        //
        // IMPORTANT: NO fallback to $contact->phone. Contact phones on WhatsApp
        // are the sender's WhatsApp number — NOT necessarily their shipping
        // contact number (the customer might type a different one for the
        // delivery). Shipping company needs the number the CUSTOMER typed in
        // their order confirmation. Empty here means "not captured yet" —
        // operator sees the gap and chases via chat.
        $phoneRaw = (string) ($captured['phone'] ?? '');
        $phone = $phoneRaw !== '' ? "'" . $phoneRaw : '';
        $convIdStr = "'" . (string) $conversation->id;

        return array_combine(self::HEADERS, [
            ($at ? \Illuminate\Support\Carbon::parse($at) : now())->toDateTimeString(),
            // Name column: NO fallback to contact->name. Contact names come from
            // WhatsApp display names which are nicknames, emoji, or usernames —
            // useless for shipping. Empty cell forces the operator (or the AI
            // follow-up loop) to capture the real name.
            (string) ($captured['name'] ?? ''),
            $phone,
            $orderString,
            (string) ($captured['address'] ?? ''),
            (string) $conversation->platform,
            (string) ($conversation->page?->name ?? ''),
            (string) ($contact?->lead_status ?? ''),
            Str::limit(trim(preg_replace('/\s+/u', ' ', (string) $lastMessage) ?? ''), 300),
            match ($event) {
                self::EVENT_LEAD_CAPTURED => 'Required info captured',
                self::EVENT_DEAL_CLOSED   => 'Deal closed',
                default                   => 'Test row',
            },
            $convIdStr,
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
